<?php

namespace Wttks\StrJa\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wttks\StrJa\EncodingDetector;
use Wttks\StrJa\SjisConverter;
use Wttks\StrJa\EucConverter;

/**
 * EncodingDetector の文字コード判定テスト
 *
 * テストでは実際に mb_convert_encoding で変換したバイナリを使って判定する。
 */
class EncodingDetectorTest extends TestCase
{
    // =========================================================================
    // detectEncoding
    // =========================================================================

    #[Test]
    public function detectEncoding_空文字列はASCIIを返す(): void
    {
        $this->assertSame('ASCII', EncodingDetector::detectEncoding(''));
    }

    #[Test]
    public function detectEncoding_ASCIIのみはASCIIを返す(): void
    {
        $this->assertSame('ASCII', EncodingDetector::detectEncoding('Hello World 123'));
        $this->assertSame('ASCII', EncodingDetector::detectEncoding('abc!@#'));
    }

    #[Test]
    public function detectEncoding_UTF8日本語はUTF8を返す(): void
    {
        $this->assertSame('UTF-8', EncodingDetector::detectEncoding('日本語のテスト'));
        $this->assertSame('UTF-8', EncodingDetector::detectEncoding('あいうえお'));
        $this->assertSame('UTF-8', EncodingDetector::detectEncoding('アイウエオ'));
    }

    #[Test]
    public function detectEncoding_SJIS変換済みバイナリはSJISwinを返す(): void
    {
        $sjis = SjisConverter::toSjis('日本語のテスト');
        $this->assertSame('SJIS-win', EncodingDetector::detectEncoding($sjis));
    }

    #[Test]
    public function detectEncoding_EUC変換済みバイナリはeucJPwinを返す(): void
    {
        $euc = EucConverter::toEuc('日本語のテスト');
        $this->assertSame('eucJP-win', EncodingDetector::detectEncoding($euc));
    }

    #[Test]
    public function detectEncoding_各種日本語文字列をSJIS変換して判定(): void
    {
        $inputs = ['ひらがな', 'カタカナ', '漢字混じり文', '山田太郎'];
        foreach ($inputs as $input) {
            $sjis = mb_convert_encoding($input, 'SJIS-win', 'UTF-8');
            $this->assertSame('SJIS-win', EncodingDetector::detectEncoding($sjis), "'{$input}' のSJIS判定失敗");
        }
    }

    #[Test]
    public function detectEncoding_各種日本語文字列をEUC変換して判定(): void
    {
        $inputs = ['ひらがな', 'カタカナ', '漢字混じり文', '山田太郎'];
        foreach ($inputs as $input) {
            $euc = mb_convert_encoding($input, 'eucJP-win', 'UTF-8');
            $this->assertSame('eucJP-win', EncodingDetector::detectEncoding($euc), "'{$input}' のEUC判定失敗");
        }
    }

    // =========================================================================
    // isUtf8
    // =========================================================================

    #[Test]
    public function isUtf8_UTF8文字列はtrue(): void
    {
        $this->assertTrue(EncodingDetector::isUtf8('日本語のテスト'));
        $this->assertTrue(EncodingDetector::isUtf8('あいうえお'));
    }

    #[Test]
    public function isUtf8_ASCIIのみはtrue(): void
    {
        // ASCII は UTF-8 と互換があるため true
        $this->assertTrue(EncodingDetector::isUtf8('Hello World'));
        $this->assertTrue(EncodingDetector::isUtf8(''));
    }

    #[Test]
    public function isUtf8_SJISバイナリはfalse(): void
    {
        $sjis = SjisConverter::toSjis('日本語');
        $this->assertFalse(EncodingDetector::isUtf8($sjis));
    }

    #[Test]
    public function isUtf8_EUCバイナリはfalse(): void
    {
        $euc = EucConverter::toEuc('日本語');
        $this->assertFalse(EncodingDetector::isUtf8($euc));
    }

    // =========================================================================
    // isSjis
    // =========================================================================

    #[Test]
    public function isSjis_SJISバイナリはtrue(): void
    {
        $sjis = SjisConverter::toSjis('日本語のテスト');
        $this->assertTrue(EncodingDetector::isSjis($sjis));
    }

    #[Test]
    public function isSjis_ASCIIのみはtrue(): void
    {
        // ASCII は SJIS-win と互換があるため true
        $this->assertTrue(EncodingDetector::isSjis('Hello World'));
        $this->assertTrue(EncodingDetector::isSjis(''));
    }

    #[Test]
    public function isSjis_UTF8日本語はfalse(): void
    {
        $this->assertFalse(EncodingDetector::isSjis('日本語'));
    }

    #[Test]
    public function isSjis_EUCバイナリはfalse(): void
    {
        $euc = EucConverter::toEuc('日本語');
        $this->assertFalse(EncodingDetector::isSjis($euc));
    }

    // =========================================================================
    // isEuc
    // =========================================================================

    #[Test]
    public function isEuc_EUCバイナリはtrue(): void
    {
        $euc = EucConverter::toEuc('日本語のテスト');
        $this->assertTrue(EncodingDetector::isEuc($euc));
    }

    #[Test]
    public function isEuc_ASCIIのみはtrue(): void
    {
        // ASCII は eucJP-win と互換があるため true
        $this->assertTrue(EncodingDetector::isEuc('Hello World'));
        $this->assertTrue(EncodingDetector::isEuc(''));
    }

    #[Test]
    public function isEuc_UTF8日本語はfalse(): void
    {
        $this->assertFalse(EncodingDetector::isEuc('日本語'));
    }

    #[Test]
    public function isEuc_SJISバイナリはfalse(): void
    {
        $sjis = SjisConverter::toSjis('日本語');
        $this->assertFalse(EncodingDetector::isEuc($sjis));
    }

    // =========================================================================
    // 実用シナリオ: ファイル受信後の振り分け
    // =========================================================================

    #[Test]
    public function 実用_受信バイナリをUTF8に変換する(): void
    {
        // どのエンコーディングで来ても UTF-8 に変換するパターン
        $original = '日本語テスト';

        $cases = [
            'UTF-8'     => $original,
            'SJIS-win'  => SjisConverter::toSjis($original),
            'eucJP-win' => EucConverter::toEuc($original),
        ];

        foreach ($cases as $label => $binary) {
            $encoding = EncodingDetector::detectEncoding($binary);
            $this->assertNotFalse($encoding, "{$label}: 判定失敗");

            $utf8 = ($encoding === 'UTF-8' || $encoding === 'ASCII')
                ? $binary
                : mb_convert_encoding($binary, 'UTF-8', $encoding);

            $this->assertSame($original, $utf8, "{$label} → UTF-8 変換失敗");
        }
    }
}
