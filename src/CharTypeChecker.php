<?php

namespace Wttks\StrJa;

/**
 * 日本語文字種判定クラス
 *
 * 判定に使用するUnicodeプロパティ:
 *   \p{Hiragana}       : U+3041-U+309F（ひらがなブロック）
 *   U+30A0-U+30FF     : カタカナブロック（全角のみ）、長音符 U+30FC・中点 U+30FB を含む
 *   \p{Han}           : CJK統合漢字・互換漢字等
 *
 * 注意: \p{Katakana} は半角カタカナ（U+FF65-U+FF9F）も含むため isKatakana / hasKatakana では使わない。
 *
 * 長音符（ー U+30FC）と中点（・ U+30FB）:
 *   isKatakana では U+30A0-U+30FF に含まれるため自動的に許容される。
 *   isHiragana では明示的に許容（フリガナ用途で実用的）。
 */
class CharTypeChecker
{
    // =========================================================================
    // is 系: 文字列全体が指定の文字種か
    // =========================================================================

    /**
     * 文字列全体がひらがなか判定する。
     * 長音符（ー）・中点（・）を許容する。
     * 空文字列は false を返す。
     */
    public static function isHiragana(string $str): bool
    {
        if ($str === '') {
            return false;
        }

        // \p{Hiragana}: ひらがな
        // \x{30FC}: 長音符（ー）※Katakanaブロックだがひらがな文字列でも使用される
        // \x{30FB}: 中点（・）※同上
        return (bool) preg_match('/\A[\p{Hiragana}\x{30FC}\x{30FB}]+\z/u', $str);
    }

    /**
     * 文字列全体が全角カタカナか判定する。
     * 長音符（ー）・中点（・）は \p{Katakana} に含まれるため自動的に許容される。
     * 空文字列は false を返す。
     */
    public static function isKatakana(string $str): bool
    {
        if ($str === '') {
            return false;
        }

        // U+30A0-U+30FF: カタカナブロック（全角のみ）
        // 長音符（U+30FC）・中点（U+30FB）を含む
        // \p{Katakana} は半角カタカナ（U+FF65-U+FF9F）も含んでしまうため使わない
        return (bool) preg_match('/\A[\x{30A0}-\x{30FF}]+\z/u', $str);
    }

    // =========================================================================
    // has 系: 文字列に指定の文字種が含まれるか
    // =========================================================================

    /**
     * ひらがなを含むか判定する。
     */
    public static function hasHiragana(string $str): bool
    {
        return (bool) preg_match('/\p{Hiragana}/u', $str);
    }

    /**
     * 全角カタカナを含むか判定する。
     * 半角カタカナは対象外。
     */
    public static function hasKatakana(string $str): bool
    {
        // U+30A0-U+30FF: カタカナブロック（全角のみ）
        // \p{Katakana} は半角カタカナ（U+FF65-U+FF9F）も含んでしまうため使わない
        return (bool) preg_match('/[\x{30A0}-\x{30FF}]/u', $str);
    }

    /**
     * 漢字を含むか判定する。
     * CJK統合漢字・互換漢字・拡張漢字を含む。
     */
    public static function hasKanji(string $str): bool
    {
        return (bool) preg_match('/\p{Han}/u', $str);
    }

    /**
     * 日本語文字（ひらがな・カタカナ・漢字のいずれか）を含むか判定する。
     */
    public static function hasJapanese(string $str): bool
    {
        // \p{Hiragana}: ひらがな
        // U+30A0-U+30FF: カタカナブロック（全角のみ）
        // \p{Han}: 漢字
        return (bool) preg_match('/[\p{Hiragana}\x{30A0}-\x{30FF}\p{Han}]/u', $str);
    }
}
