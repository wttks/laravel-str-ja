<?php

namespace Wttks\StrJa\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wttks\StrJa\EucConverter;

/**
 * EucConverter の正確性テスト
 */
class EucConverterTest extends TestCase
{
    protected function setUp(): void
    {
        EucConverter::clearTableCache();
    }

    // =========================================================================
    // toEuc: 基本変換
    // =========================================================================

    #[Test]
    public function 空文字列はそのまま返す(): void
    {
        $this->assertSame('', EucConverter::toEuc(''));
        $this->assertSame('', EucConverter::fromEuc(''));
    }

    #[Test]
    public function asciiのみの文字列はEUCに変換できる(): void
    {
        $result = EucConverter::toEuc('Hello World 123');
        $this->assertSame('Hello World 123', EucConverter::fromEuc($result));
    }

    #[Test]
    public function 通常の日本語はEUC変換してUTF8に戻せる(): void
    {
        $original = '日本語のテスト文字列です。';
        $euc = EucConverter::toEuc($original);
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame($original, $restored);
    }

    #[Test]
    public function 全角英数字はNFKC正規化で半角に変換されてからEUC変換される(): void
    {
        // NFKC正規化により全角→半角に変換されるため、ラウンドトリップでは半角で戻る
        $euc = EucConverter::toEuc('ＡＢＣＤ１２３４！＠＃');
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame('ABCD1234!@#', $restored);
    }

    #[Test]
    public function toEucの戻り値はeucJPwin_エンコードになっている(): void
    {
        $result = EucConverter::toEuc('テスト');
        // eucJP-win はUTF-8ではない
        $this->assertNotSame('テスト', $result);
        // mb_detect_encoding でEUC-JP系として検出される
        $detected = mb_detect_encoding($result, ['eucJP-win', 'UTF-8'], true);
        $this->assertSame('eucJP-win', $detected);
    }

    // =========================================================================
    // normalize: IBM拡張文字・異体字の変換
    // =========================================================================

    #[Test]
    #[DataProvider('ibmExtendedCharProvider')]
    public function IBM拡張文字が代替文字に置換される(string $input, string $expected): void
    {
        $this->assertSame($expected, EucConverter::normalize($input));
    }

    public static function ibmExtendedCharProvider(): array
    {
        return [
            'はしご高' => ['髙', '高'],
            'たつさき' => ['﨑', '崎'],
            'EMダッシュ' => ['—', '-'],
            'ENダッシュ' => ['–', '-'],
            'マイナス記号' => ['−', '－'],
            '平行記号' => ['∥', '‖'],
            // 全角チルダ(U+FF5E)はNFKC正規化で半角チルダ(~)になる
            '全角チルダ' => ['～', '~'],
            'はしご高を含む文字列' => ['髙橋さんの住所は﨑山です', '高橋さんの住所は崎山です'],
        ];
    }

    // =========================================================================
    // normalize: Unicode NFKC 互換等価変換
    // =========================================================================

    #[Test]
    #[DataProvider('nfkcCompatProvider')]
    public function Unicode互換等価文字が正規化される(string $input, string $expected): void
    {
        $this->assertSame($expected, EucConverter::normalize($input));
    }

    public static function nfkcCompatProvider(): array
    {
        return [
            '丸囲み株式会社' => ['㈱', '(株)'],
            '丸囲み有限会社' => ['㈲', '(有)'],
            '丸付き数字1' => ['①', '1'],
            '丸付き数字10' => ['⑩', '10'],
            'センチメートル' => ['㎝', 'cm'],
            'キログラム' => ['㎏', 'kg'],
            'ローマ数字Ⅰ' => ['Ⅰ', 'I'],
            'ローマ数字Ⅱ' => ['Ⅱ', 'II'],
            '全角アルファベットA' => ['Ａ', 'A'],
            '全角数字1' => ['１', '1'],
        ];
    }

    // =========================================================================
    // ラウンドトリップ: 異体字を含まない文字列は元に戻る
    // =========================================================================

    #[Test]
    #[DataProvider('roundTripPassProvider')]
    public function 異体字なし文字列はラウンドトリップで元に戻る(string $original): void
    {
        $euc = EucConverter::toEuc($original);
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame($original, $restored);
    }

    public static function roundTripPassProvider(): array
    {
        return [
            'ASCII' => ['Hello World'],
            '日本語' => ['日本語のテスト'],
            'ひらがな' => ['あいうえおかきくけこ'],
            'カタカナ' => ['アイウエオカキクケコ'],
            '数字と記号' => ['1234567890!@#$%'],
            '混在' => ['ABC あいう アイウ 123'],
            '句読点' => ['日本語、テスト。改行なし'],
            '長文' => [str_repeat('日本語テスト', 100)],
        ];
    }

    // =========================================================================
    // ラウンドトリップ: 異体字を含む場合は正規化後の文字で戻る
    // =========================================================================

    #[Test]
    #[DataProvider('roundTripNormalizedProvider')]
    public function 異体字ありはラウンドトリップで正規化後の文字になる(
        string $input,
        string $expectedAfterRoundTrip
    ): void {
        $euc = EucConverter::toEuc($input);
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame($expectedAfterRoundTrip, $restored);
    }

    public static function roundTripNormalizedProvider(): array
    {
        return [
            'はしご高' => ['髙', '高'],
            'たつさき' => ['﨑', '崎'],
            'はしご高を含む氏名' => ['髙橋一郎', '高橋一郎'],
            'EMダッシュ' => ['A—B', 'A-B'],
            '丸囲み株式会社' => ['㈱テスト', '(株)テスト'],
        ];
    }

    // =========================================================================
    // eucJP-win の SJIS-win との差異テスト
    // =========================================================================

    #[Test]
    public function コピーライト記号はEUC変換してUTF8に戻せる(): void
    {
        // © (U+00A9) は eucJP-win で表現可能
        $original = '© 2024 テスト株式会社';
        $euc = EucConverter::toEuc($original);
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame($original, $restored);
    }

    #[Test]
    public function 商標記号はEUC変換でTMに変換される(): void
    {
        // ™ (U+2122) は eucJP-win で「TM」（2文字）として表現される
        $euc = EucConverter::toEuc('テスト™ブランド');
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame('テストTMブランド', $restored);
    }

    #[Test]
    public function ユーロ記号はEUC変換で疑問符になる(): void
    {
        // € (U+20AC) は eucJP-win でも表現不可のため ? に置き換わる
        $euc = EucConverter::toEuc('100€');
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame('100?', $restored);
    }

    // =========================================================================
    // エッジケース
    // =========================================================================

    #[Test]
    public function 改行コードは変換しても保持される(): void
    {
        $original = "1行目\n2行目\r\n3行目";
        $euc = EucConverter::toEuc($original);
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame($original, $restored);
    }

    #[Test]
    public function タブ文字は変換しても保持される(): void
    {
        $original = "列1\t列2\t列3";
        $euc = EucConverter::toEuc($original);
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame($original, $restored);
    }

    #[Test]
    public function 長大な文字列でも正しく変換できる(): void
    {
        $original = str_repeat('日本語テスト髙橋﨑山', 1000);
        $expected = str_repeat('日本語テスト高橋崎山', 1000);
        $euc = EucConverter::toEuc($original);
        $restored = EucConverter::fromEuc($euc);
        $this->assertSame($expected, $restored);
    }

    #[Test]
    public function normalizeは異体字と互換文字が混在していても正しく変換する(): void
    {
        $input = '髙橋㈱の﨑山支店';
        $expected = '高橋(株)の崎山支店';
        $this->assertSame($expected, EucConverter::normalize($input));
    }
}
