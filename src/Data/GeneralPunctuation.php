<?php

/**
 * 一般句読点の変換テーブル
 *
 * punctuation オプションが true の場合に適用される。
 * NFKC正規化では変換されない Unicode 一般句読点を
 * ASCII相当の文字に変換する。
 *
 * @return array<string, string>
 */
return [
    // =========================================================================
    // 引用符類
    // =========================================================================

    // 左右ダブルクォート → "
    "\u{201C}" => '"', // LEFT DOUBLE QUOTATION MARK
    "\u{201D}" => '"', // RIGHT DOUBLE QUOTATION MARK
    "\u{201E}" => '"', // DOUBLE LOW-9 QUOTATION MARK
    "\u{201F}" => '"', // DOUBLE HIGH-REVERSED-9 QUOTATION MARK

    // 左右シングルクォート → '
    "\u{2018}" => "'", // LEFT SINGLE QUOTATION MARK
    "\u{2019}" => "'", // RIGHT SINGLE QUOTATION MARK
    "\u{201A}" => "'", // SINGLE LOW-9 QUOTATION MARK
    "\u{201B}" => "'", // SINGLE HIGH-REVERSED-9 QUOTATION MARK

    // 山括弧引用符 → " '
    "\u{2039}" => "'", // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
    "\u{203A}" => "'", // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK

    // プライム → ' "
    "\u{2032}" => "'", // PRIME
    "\u{2033}" => '"', // DOUBLE PRIME
    "\u{2034}" => '"', // TRIPLE PRIME（ダブルで代用）
    "\u{2035}" => "'", // REVERSED PRIME
    "\u{2036}" => '"', // REVERSED DOUBLE PRIME

    // =========================================================================
    // ハイフン・ダッシュ類
    // =========================================================================

    "\u{2010}" => '-', // HYPHEN
    "\u{2011}" => '-', // NON-BREAKING HYPHEN
    "\u{2012}" => '-', // FIGURE DASH
    "\u{2013}" => '-', // EN DASH
    "\u{2014}" => '-', // EM DASH
    "\u{2015}" => '-', // HORIZONTAL BAR
    "\u{2043}" => '-', // HYPHEN BULLET

    // =========================================================================
    // 省略記号類
    // =========================================================================

    "\u{2026}" => '...', // HORIZONTAL ELLIPSIS
    "\u{2025}" => '..', // TWO DOT LEADER
    "\u{205D}" => ':', // TRICOLON（コロンで代用）

    // =========================================================================
    // スラッシュ類
    // =========================================================================

    "\u{2044}" => '/', // FRACTION SLASH
    "\u{2052}" => '%', // COMMERCIAL MINUS SIGN（パーセントで代用）
];
