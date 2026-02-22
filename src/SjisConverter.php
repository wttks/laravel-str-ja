<?php

namespace Wttks\StrJa;

use Normalizer;
use ValueError;

/**
 * UTF-8 ↔ SJIS-win 変換クラス
 *
 * 変換フロー（UTF-8 → SJIS-win）:
 *   1. Unicode NFKC正規化（㈱→(株) 等の互換等価文字を展開）
 *   2. IBM拡張文字・異体字を代替文字に置換（髙→高 等）
 *   3. mb_convert_encoding で SJIS-win に変換
 *
 * パフォーマンス:
 *   strtr / str_replace / preg_replace の3実装をベンチマーク比較済み。
 *   strtr が最速のため採用。
 */
class SjisConverter
{
    /** @var array<string, string>|null キャッシュ済み変換テーブル */
    private static ?array $table = null;

    // =========================================================================
    // 公開 API
    // =========================================================================

    /**
     * UTF-8 文字列を SJIS-win に変換する。
     * 変換前に NFKC 正規化と異体字置換を自動適用する。
     */
    public static function toSjis(string $str): string
    {
        if ($str === '') {
            return '';
        }

        $normalized = static::normalize($str);

        return mb_convert_encoding($normalized, 'SJIS-win', 'UTF-8');
    }

    /**
     * SJIS-win 文字列を UTF-8 に変換する。
     */
    public static function fromSjis(string $str): string
    {
        if ($str === '') {
            return '';
        }

        return mb_convert_encoding($str, 'UTF-8', 'SJIS-win');
    }

    /**
     * SJIS-win への変換前に行う正規化処理のみを実行する。
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
    // ベンチマーク比較用の別実装
    // 通常使用は normalize() / toSjis() を使うこと
    // =========================================================================

    /**
     * str_replace を使った正規化実装（ベンチマーク比較用）
     *
     * @internal
     */
    public static function normalizeWithStrReplace(string $str): string
    {
        if ($str === '') {
            return '';
        }

        $str = Normalizer::normalize($str, Normalizer::NFKC);

        $table = static::getTable();

        return str_replace(array_keys($table), array_values($table), $str);
    }

    /**
     * preg_replace を使った正規化実装（ベンチマーク比較用）
     *
     * @internal
     */
    public static function normalizeWithPregReplace(string $str): string
    {
        if ($str === '') {
            return '';
        }

        $str = Normalizer::normalize($str, Normalizer::NFKC);

        $table = static::getTable();
        $pattern = '/[' . implode('', array_map('preg_quote', array_keys($table))) . ']/u';
        $replacements = $table;

        return preg_replace_callback($pattern, function (array $m) use ($replacements): string {
            return $replacements[$m[0]] ?? $m[0];
        }, $str) ?? $str;
    }

    // =========================================================================
    // 内部処理
    // =========================================================================

    /**
     * 変換テーブルを返す（初回のみファイルをロードしてキャッシュ）。
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
