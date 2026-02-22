<?php

namespace Wttks\StrJa;

/**
 * 文字コード判定クラス
 *
 * mb_detect_encoding() のラッパー。
 * 判定順序: ASCII → UTF-8 → SJIS-win → eucJP-win
 *
 * 注意:
 *   短い文字列や ASCII のみの文字列は誤判定が起きやすい。
 *   ASCII のみの文字列は 'ASCII' を返す（UTF-8/SJIS/EUC いずれでも有効なため）。
 *   バイナリが不正・判定不能の場合は false を返す。
 */
class EncodingDetector
{
    /**
     * 判定順序。ASCII を最初に置くことで純粋な ASCII 文字列を早期に確定させる。
     * strict: true で厳密判定を有効化する。
     */
    private const DETECT_ORDER = ['ASCII', 'UTF-8', 'SJIS-win', 'eucJP-win'];

    // =========================================================================
    // 公開 API
    // =========================================================================

    /**
     * 文字列のエンコーディングを判定して返す。
     *
     * @return 'ASCII'|'UTF-8'|'SJIS-win'|'eucJP-win'|false
     *         判定不能な場合は false
     */
    public static function detectEncoding(string $str): string|false
    {
        if ($str === '') {
            return 'ASCII';
        }

        return mb_detect_encoding($str, self::DETECT_ORDER, strict: true);
    }

    /**
     * 文字列が UTF-8 かどうか判定する。
     * ASCII のみの文字列は UTF-8 として扱う（UTF-8 と互換があるため）。
     */
    public static function isUtf8(string $str): bool
    {
        $encoding = static::detectEncoding($str);

        return $encoding === 'UTF-8' || $encoding === 'ASCII';
    }

    /**
     * 文字列が SJIS-win かどうか判定する。
     * ASCII のみの文字列は SJIS-win として扱う（SJIS-win と互換があるため）。
     */
    public static function isSjis(string $str): bool
    {
        $encoding = static::detectEncoding($str);

        return $encoding === 'SJIS-win' || $encoding === 'ASCII';
    }

    /**
     * 文字列が eucJP-win かどうか判定する。
     * ASCII のみの文字列は eucJP-win として扱う（eucJP-win と互換があるため）。
     */
    public static function isEuc(string $str): bool
    {
        $encoding = static::detectEncoding($str);

        return $encoding === 'eucJP-win' || $encoding === 'ASCII';
    }
}
