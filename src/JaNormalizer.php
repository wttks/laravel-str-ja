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
     * トラブルの原因となる文字にマッチする正規表現パターン。
     *
     * ASCII 制御文字（タブ U+0009・LF U+000A・CR U+000D は通常テキストで使用されるため除外）:
     *   - U+0000-U+0008: NULL, SOH, STX, ETX, EOT, ENQ, ACK, BEL, BS
     *   - U+000B: VT（垂直タブ）
     *   - U+000C: FF（フォームフィード）
     *   - U+000E-U+001F: SO〜US（各種制御文字）
     *   - U+007F: DEL
     *
     * Unicode 不可視文字・制御文字:
     *   - U+200B〜U+200F: ゼロ幅スペース等
     *   - U+202A〜U+202E: 双方向制御文字
     *   - U+2060〜U+2064: Word Joiner 等
     *   - U+2066〜U+206F: 双方向分離制御文字等
     *   - U+FEFF: BOM / Zero Width No-Break Space
     *   - U+FFF9〜U+FFFB: Interlinear Annotation 制御文字
     */
    private const TROUBLE_CHARS_PATTERN = '/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2064}\x{2066}-\x{206F}\x{FEFF}\x{FFF9}-\x{FFFB}]/u';

    /**
     * @deprecated TROUBLE_CHARS_PATTERN を使うこと
     * normalize() 内で後方互換のため残す
     */
    private const CONTROL_CHARS_PATTERN = self::TROUBLE_CHARS_PATTERN;

    /**
     * トラブルの原因となる文字（ASCII制御文字・Unicode不可視文字・BOM等）が含まれるか判定する。
     * 対象文字の詳細は TROUBLE_CHARS_PATTERN のコメントを参照。
     * タブ（U+0009）・LF（U+000A）・CR（U+000D）は通常テキストで使用されるため対象外。
     */
    public static function hasTroubleChars(string $str): bool
    {
        if ($str === '') {
            return false;
        }

        return (bool) preg_match(self::TROUBLE_CHARS_PATTERN, $str);
    }

    /**
     * トラブルの原因となる文字（ASCII制御文字・Unicode不可視文字・BOM等）を削除して返す。
     * 対象文字の詳細は TROUBLE_CHARS_PATTERN のコメントを参照。
     * タブ（U+0009）・LF（U+000A）・CR（U+000D）は通常テキストで使用されるため対象外。
     */
    public static function removeTroubleChars(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return preg_replace(self::TROUBLE_CHARS_PATTERN, '', $str) ?? $str;
    }

    /**
     * 日本語入力値の総合サニタイズ。
     * normalize() の全処理に加えて、連続空白の正規化・前後トリムを行う。
     *
     * 処理順序:
     *   1. トラブル文字（制御文字・不可視文字・BOM等）を削除
     *   2. 半角カナ → 全角カナ・濁音半濁音を1文字に結合（ｶﾞ → ガ、ﾊﾟ → パ）
     *   3. 一般句読点を ASCII 相当に変換（punctuation: true の場合）
     *   4. NFKC正規化で全角ASCII → 半角（Ａ → A、１ → 1 等）
     *   5. 連続した空白（全角・半角・特殊スペース等）を半角スペース1つに置換
     *   6. 前後の空白をトリム
     *
     * @param string $str        対象文字列
     * @param bool $punctuation  true にすると一般句読点を ASCII 相当に変換する
     */
    public static function sanitize(string $str, bool $punctuation = false): string
    {
        if ($str === '') {
            return '';
        }

        // Step 1〜4: normalizeJa の処理（制御文字削除・半角カナ→全角・NFKC等）
        $str = static::normalize($str, $punctuation);

        // Step 5: 連続した空白（全角・半角・特殊スペース等）を半角スペース1つに置換
        $str = (string) preg_replace('/[\p{Z}\s]+/u', ' ', $str);

        // Step 6: 前後のトリム
        return trim($str);
    }

    /**
     * トラブル文字を削除し、連続した空白文字を半角スペース1つに正規化して返す。
     *
     * 処理順序:
     *   1. トラブル文字（制御文字・不可視文字・BOM等）を削除
     *   2. 全角スペース・NBSP・ゼロ幅スペース等を含む連続空白を半角スペース1つに置換
     *   3. 前後の空白をトリム
     */
    public static function squish(string $str): string
    {
        if ($str === '') {
            return '';
        }

        // Step 1: トラブル文字を削除
        $str = static::removeTroubleChars($str);

        // Step 2: 連続した空白（全角・半角・特殊スペース等）を半角スペース1つに置換
        // \p{Z}: Unicode Space Separator（全角スペース・NBSP・細いスペース等）
        // \s: 半角スペース・タブ・改行（CR/LF）
        $str = (string) preg_replace('/[\p{Z}\s]+/u', ' ', $str);

        // Step 3: 前後をトリム
        return trim($str);
    }

    /**
     * 文字列の単語数を返す。
     * 全角・半角・特殊スペースで区切られた単語をカウントする。
     * 空文字列は 0 を返す。
     */
    public static function countWords(string $str): int
    {
        return count(static::splitByWhitespace($str));
    }

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
