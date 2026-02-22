<?php

namespace Wttks\StrJa;

use Normalizer;

/**
 * 日本語文字列の正規化クラス
 *
 * 変換内容:
 *   常時:
 *     1. 不可視制御文字・BOMを削除（ゼロ幅スペース等。コピペ混入対策）
 *     2. 半角カナ → 全角カナ（濁音・半濁音を1文字に結合）
 *     3. 全角ASCII文字 → 半角（NFKC正規化）
 *
 *   punctuation: true の場合に追加:
 *     4. 一般句読点を ASCII相当に変換
 *        例: " " → "、' ' → '、… → ...、– → -
 *
 * 処理順序の理由:
 *   先に mb_convert_kana('KV') で半角カナ→全角カナ・濁音結合を行い、
 *   その後 NFKC で全角ASCII を半角化する。
 *   全角カタカナ（ア等）は NFKC の対象外なので変換されない。
 */
class JaNormalizer
{
    /** @var array<string, string>|null キャッシュ済み句読点変換テーブル */
    private static ?array $punctuationTable = null;

    /**
     * 不可視制御文字・BOMにマッチする正規表現パターン。
     * - U+200B〜U+200F: ゼロ幅スペース等
     * - U+202A〜U+202E: 双方向制御文字
     * - U+2060〜U+2064: Word Joiner 等
     * - U+2066〜U+206F: 双方向分離制御文字等
     * - U+FEFF: BOM / Zero Width No-Break Space
     * - U+FFF9〜U+FFFB: Interlinear Annotation 制御文字
     */
    private const CONTROL_CHARS_PATTERN = '/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{206F}\x{FEFF}\x{FFF9}-\x{FFFB}]/u';

    /**
     * 文字列を空白文字で分割して配列を返す。
     * 全角・半角・特殊スペースを全てカバーする。
     *
     * 対象空白:
     *   \p{Z} : Unicode Space Separator カテゴリ
     *           全角スペース（U+3000）・NBSP（U+00A0）・細いスペース（U+2009）等を含む
     *   \s    : 半角スペース・タブ・改行（CR/LF）
     *   \x{200B}: ゼロ幅スペース（コピペで混入しやすい不可視の空白）
     *
     * 連続した空白は1つの区切りとして扱い、空要素は除去する。
     * 空文字列を渡すと空配列を返す。
     *
     * @return string[]
     */
    public static function splitByWhitespace(string $str): array
    {
        if ($str === '') {
            return [];
        }

        $parts = preg_split('/[\p{Z}\s\x{200B}]+/u', $str);

        // 文字列が空白で始まる・終わる場合に生じる空文字列を除去
        return array_values(array_filter($parts, fn(string $s): bool => $s !== ''));
    }

    /**
     * 日本語文字列を正規化する。
     *
     * @param string $str     対象文字列
     * @param bool $punctuation true にすると一般句読点を ASCII 相当に変換する
     */
    public static function normalize(string $str, bool $punctuation = false): string
    {
        if ($str === '') {
            return '';
        }

        // Step 1: 不可視制御文字・BOM を削除（常時）
        // ゼロ幅スペース等はコピペで混入しやすくセキュリティリスクにもなる
        $str = preg_replace(self::CONTROL_CHARS_PATTERN, '', $str) ?? $str;

        // Step 2: 半角カナ → 全角カナ・濁音半濁音を1文字に結合（常時）
        // K: 半角カタカナ → 全角カタカナ
        // V: 濁点・半濁点を前の文字に統合（ｶﾞ → ガ、ﾊﾟ → パ）
        $str = mb_convert_kana($str, 'KV', 'UTF-8');

        // Step 3: 一般句読点を ASCII 相当に変換（オプション）
        // NFKC より先に実行する理由:
        //   ″（U+2033 ダブルプライム）はNFKCで′′（プライム×2）に分解されるため、
        //   NKFCより先に変換しないと ″→" のマッピングが効かなくなる。
        if ($punctuation) {
            $str = strtr($str, static::getPunctuationTable());
        }

        // Step 4: NFKC正規化で全角ASCII → 半角（常時）
        // Ａ → A、１ → 1、！ → !、㈱ → (株) 等
        // ※全角カタカナ（ア等）は NFKC の対象外なので変換されない
        $str = Normalizer::normalize($str, Normalizer::NFKC);

        return $str;
    }

    // =========================================================================
    // 内部処理
    // =========================================================================

    /**
     * 句読点変換テーブルを返す（初回のみファイルをロードしてキャッシュ）。
     *
     * @return array<string, string>
     */
    protected static function getPunctuationTable(): array
    {
        if (static::$punctuationTable === null) {
            static::$punctuationTable = require __DIR__ . '/Data/GeneralPunctuation.php';
        }

        return static::$punctuationTable;
    }

    /**
     * テスト用: キャッシュをクリアする。
     *
     * @internal
     */
    public static function clearPunctuationCache(): void
    {
        static::$punctuationTable = null;
    }
}
