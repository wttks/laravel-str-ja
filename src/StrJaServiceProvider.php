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

        // 日本語文字列の正規化
        // - 半角カナ → 全角カナ（濁音・半濁音を1文字に結合）
        // - 全角ASCII（英数字・記号）→ 半角
        Str::macro('normalizeJa', function (string $str): string {
            return JaNormalizer::normalize($str);
        });
    }
}
