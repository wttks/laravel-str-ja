<?php

namespace Wttks\StrJa;

use Normalizer;

/**
 * UTF-8 ↔ eucJP-win 変換クラス
 *
 * 変換フロー（UTF-8 → eucJP-win）:
 *   1. Unicode NFKC正規化（㈱→(株) 等の互換等価文字を展開）
 *   2. IBM拡張文字・異体字を代替文字に置換（髙→高 等）
 *   3. mb_convert_encoding で eucJP-win に変換
 *
 * SJIS-win との主な差異:
 *   - © (U+00A9) ・ ™ (U+2122) は eucJP-win で表現可能（SJIS-win は mb_convert_encoding で欠落）
 *   - € (U+20AC) は eucJP-win でも表現不可（変換時に欠落）
 *   - 変換テーブル自体は SjisConverter と共通（IbmExtendedChars）
 *
 * パフォーマンス:
 *   strtr が最速のため採用（SjisConverter と同様）。
 */
class EucConverter
{
    /** @var array<string, string>|null キャッシュ済み変換テーブル */
    private static ?array $table = null;

    // =========================================================================
    // 公開 API
    // =========================================================================

    /**
     * UTF-8 文字列を eucJP-win に変換する。
     * 変換前に NFKC 正規化と異体字置換を自動適用する。
     */
    public static function toEuc(string $str): string
    {
        if ($str === '') {
            return '';
        }

        $normalized = static::normalize($str);

        return mb_convert_encoding($normalized, 'eucJP-win', 'UTF-8');
    }

    /**
     * eucJP-win 文字列を UTF-8 に変換する。
     */
    public static function fromEuc(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return mb_convert_encoding($str, 'UTF-8', 'eucJP-win');
    }

    /**
     * eucJP-win への変換前に行う正規化処理のみを実行する。
     * 変換テーブルの確認やデバッグ用途に使用可能。
     */
    public static function normalize(string $str): string
    {
        if ($str === '') {
            return '';
        }

        // Step 1: Unicode NFKC 正規化（互換等価を展開）
        // 例: ㈱ → (株)、① → 1、㎝ → cm
        $str = Normalizer::normalize($str, Normalizer::NFKC);

        // Step 2: IBM拡張文字・異体字を代替文字に置換
        $str = strtr($str, static::getTable());

        return $str;
    }

    // =========================================================================
    // 内部処理
    // =========================================================================

    /**
     * 変換テーブルを返す（初回のみファイルをロードしてキャッシュ）。
     *
     * SjisConverter と同じ IbmExtendedChars テーブルを使用する。
     * ©・™ は IbmExtendedChars に含まれないため eucJP-win の優位性はそのまま活かされる。
     *
     * @return array<string, string>
     */
    protected static function getTable(): array
    {
        if (static::$table === null) {
            static::$table = require __DIR__ . '/Data/IbmExtendedChars.php';
        }

        return static::$table;
    }

    /**
     * テスト用: キャッシュをクリアする。
     *
     * @internal
     */
    public static function clearTableCache(): void
    {
        static::$table = null;
    }
}
