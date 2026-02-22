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

        // 文字列のエンコーディングを判定して返す（'UTF-8' / 'SJIS-win' / 'eucJP-win' / 'ASCII' / false）
        Str::macro('detectEncoding', function (string $str): string|false {
            return EncodingDetector::detectEncoding($str);
        });

        // 文字列が UTF-8 かどうか判定する（ASCIIのみも true）
        Str::macro('isUtf8', function (string $str): bool {
            return EncodingDetector::isUtf8($str);
        });

        // 文字列が SJIS-win かどうか判定する（ASCIIのみも true）
        Str::macro('isSjis', function (string $str): bool {
            return EncodingDetector::isSjis($str);
        });

        // 文字列が eucJP-win かどうか判定する（ASCIIのみも true）
        Str::macro('isEuc', function (string $str): bool {
            return EncodingDetector::isEuc($str);
        });

        // 全角・半角・特殊スペースで文字列を単語に分割する
        // \p{Z}（全角スペース・NBSP・細いスペース等）・\s・ゼロ幅スペースに対応
        Str::macro('splitWords', function (string $str): array {
            return JaNormalizer::splitByWhitespace($str);
        });

        // 文字幅を返す（全角=2、半角=1）
        // mb_strwidth() のラッパー。半角カナは 1 としてカウントする
        Str::macro('strWidth', function (string $str): int {
            return mb_strwidth($str, 'UTF-8');
        });

        // SJIS-win 変換後のバイト数が maxBytes 以内になるよう末尾を切り捨てる
        // 全角文字（2バイト）の途中では切らない
        // normalize: true にすると正規化後の文字列に対して切り捨てる
        Str::macro('truncateSjis', function (string $str, int $maxBytes, bool $normalize = false): string {
            return SjisConverter::truncateByBytes($str, $maxBytes, $normalize);
        });

        // 文字列全体がひらがなか判定（長音符・中点を許容）
        Str::macro('isHiragana', function (string $str): bool {
            return CharTypeChecker::isHiragana($str);
        });

        // 文字列全体が全角カタカナか判定（長音符・中点を許容）
        Str::macro('isKatakana', function (string $str): bool {
            return CharTypeChecker::isKatakana($str);
        });

        // ひらがなを含むか判定
        Str::macro('hasHiragana', function (string $str): bool {
            return CharTypeChecker::hasHiragana($str);
        });

        // 全角カタカナを含むか判定
        Str::macro('hasKatakana', function (string $str): bool {
            return CharTypeChecker::hasKatakana($str);
        });

        // 漢字を含むか判定
        Str::macro('hasKanji', function (string $str): bool {
            return CharTypeChecker::hasKanji($str);
        });

        // 日本語文字（ひらがな・カタカナ・漢字）を含むか判定
        Str::macro('hasJapanese', function (string $str): bool {
            return CharTypeChecker::hasJapanese($str);
        });
    }
}
