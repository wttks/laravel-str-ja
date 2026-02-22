<?php

namespace Wttks\StrJa;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class StrJaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // UTF-8 → SJIS-win 変換（正規化込み）
        Str::macro('toSjis', function (string $str): string {
            return SjisConverter::toSjis($str);
        });

        // SJIS-win → UTF-8 変換
        Str::macro('fromSjis', function (string $str): string {
            return SjisConverter::fromSjis($str);
        });

        // SJIS-win変換前の正規化のみ実行（変換はしない）
        Str::macro('normalizeForSjis', function (string $str): string {
            return SjisConverter::normalize($str);
        });

        // SJIS-win に変換したときのバイト数を返す
        // normalize: true にすると正規化後のバイト数を返す
        Str::macro('sjisBytes', function (string $str, bool $normalize = false): int {
            return SjisConverter::byteLength($str, $normalize);
        });

        // UTF-8 → eucJP-win 変換（正規化込み）
        Str::macro('toEuc', function (string $str): string {
            return EucConverter::toEuc($str);
        });

        // eucJP-win → UTF-8 変換
        Str::macro('fromEuc', function (string $str): string {
            return EucConverter::fromEuc($str);
        });

        // eucJP-win変換前の正規化のみ実行（変換はしない）
        Str::macro('normalizeForEuc', function (string $str): string {
            return EucConverter::normalize($str);
        });

        // 日本語文字列の正規化
        // - 不可視制御文字・BOM を削除（常時）
        // - 半角カナ → 全角カナ（濁音・半濁音を1文字に結合）
        // - 全角ASCII（英数字・記号）→ 半角
        // - punctuation: true で一般句読点を ASCII 相当に変換
        Str::macro('normalizeJa', function (string $str, bool $punctuation = false): string {
            return JaNormalizer::normalize($str, $punctuation);
        });

        // カタカナ（全角・半角）→ ひらがな変換
        Str::macro('toHiragana', function (string $str): string {
            return KanaConverter::toHiragana($str);
        });

        // ひらがな・半角カナ → 全角カタカナ変換
        Str::macro('toKatakana', function (string $str): string {
            return KanaConverter::toKatakana($str);
        });
    }
}
