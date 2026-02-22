<?php

namespace Wttks\StrJa;

/**
 * ひらがな・カタカナ変換クラス
 *
 * 変換仕様:
 *   - 半角カナは全角カナに変換してから処理する（KV オプション）
 *   - 全角カタカナ ↔ ひらがなの相互変換
 *
 * mb_convert_kana の c/C オプションでは変換されない文字を strtr で補完する:
 *   ヴ(U+30F4) ↔ ゔ(U+3094)
 *   ヵ(U+30F5) ↔ ゕ(U+3095)  小文字カ
 *   ヶ(U+30F6) ↔ ゖ(U+3096)  小文字ケ
 */
class KanaConverter
{
    /** カタカナ → ひらがな の補完テーブル（mb_convert_kana で変換されない文字） */
    private const KATA_TO_HIRA = [
        'ヴ' => 'ゔ',
        'ヵ' => 'ゕ',
        'ヶ' => 'ゖ',
    ];

    /** ひらがな → カタカナ の補完テーブル（mb_convert_kana で変換されない文字） */
    private const HIRA_TO_KATA = [
        'ゔ' => 'ヴ',
        'ゕ' => 'ヵ',
        'ゖ' => 'ヶ',
    ];
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
        $str = mb_convert_kana($str, 'c', 'UTF-8');

        return strtr($str, self::KATA_TO_HIRA);
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
        $str = strtr($str, self::HIRA_TO_KATA);

        return mb_convert_kana($str, 'C', 'UTF-8');
    }
}
