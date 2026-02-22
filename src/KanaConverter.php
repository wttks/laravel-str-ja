<?php

namespace Wttks\StrJa;

/**
 * ひらがな・カタカナ変換クラス
 *
 * 変換仕様:
 *   - 半角カナは全角カナに変換してから処理する（KV オプション）
 *   - 全角カタカナ ↔ ひらがなの相互変換
 */
class KanaConverter
{
    /**
     * カタカナ（全角・半角）をひらがなに変換する。
     *
     * 変換フロー:
     *   1. KV: 半角カナ → 全角カタカナ・濁点半濁点を結合（ｶﾞ → ガ）
     *   2. c:  全角カタカナ → ひらがな
     *
     * ※ KVc を1回で指定すると c が正常に動作しないため2ステップで処理する
     */
    public static function toHiragana(string $str): string
    {
        if ($str === '') {
            return '';
        }

        $str = mb_convert_kana($str, 'KV', 'UTF-8');

        return mb_convert_kana($str, 'c', 'UTF-8');
    }

    /**
     * ひらがな・半角カナを全角カタカナに変換する。
     *
     * 変換フロー:
     *   1. KV: 半角カナ → 全角カタカナ・濁点半濁点を結合（ｶﾞ → ガ）
     *   2. C:  ひらがな → 全角カタカナ
     *
     * ※ KVC を1回で指定すると C が正常に動作しないため2ステップで処理する
     */
    public static function toKatakana(string $str): string
    {
        if ($str === '') {
            return '';
        }

        $str = mb_convert_kana($str, 'KV', 'UTF-8');

        return mb_convert_kana($str, 'C', 'UTF-8');
    }
}
