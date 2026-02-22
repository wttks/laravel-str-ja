<?php

namespace Wttks\StrJa;

use Normalizer;

/**
 * 日本語文字列の正規化クラス
 *
 * 変換内容:
 *   1. 半角カナ → 全角カナ（濁音・半濁音を1文字に結合）
 *      例: ｶﾞ → ガ、ﾊﾟ → パ、ｱｲｳ → アイウ
 *   2. 全角ASCII文字 → 半角
 *      例: Ａ → A、１ → 1、！ → !、＠ → @
 *
 * 処理順序の理由:
 *   NFKC正規化は半角カナも全角カナに変換するが、先にmb_convert_kana('KV')で
 *   濁音結合を行い、その後NKFCで全角ASCIIを半角化する。
 *   全角カタカナ（ア等）はNFKCで変換されないため順序を問わず保持される。
 */
class JaNormalizer
{
    /**
     * 日本語文字列を正規化する。
     *
     * - 半角カナ → 全角カナ
     * - 濁音・半濁音を1文字に結合（ｶﾞ → ガ）
     * - 全角ASCII（英数字・記号・スペース）→ 半角
     */
    public static function normalize(string $str): string
    {
        if ($str === '') {
            return '';
        }

        // Step 1: 半角カナ → 全角カナ・濁音半濁音を1文字に結合
        // K: 半角カタカナ → 全角カタカナ
        // V: 濁点・半濁点を前の文字に統合（ｶﾞ → ガ、ﾊﾟ → パ）
        $str = mb_convert_kana($str, 'KV', 'UTF-8');

        // Step 2: NFKC正規化で全角ASCII → 半角
        // Ａ → A、１ → 1、！ → !、㈱ → (株) 等
        // ※全角カタカナ（ア等）はNFKCの対象外なので変換されない
        $str = Normalizer::normalize($str, Normalizer::NFKC);

        return $str;
    }
}
