<?php

namespace Wttks\StrJa\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * mb_strwidth() の動作確認テスト
 *
 * 全角=2、半角=1 として文字幅を計算する。
 * 半角カタカナは SJIS では 2 バイトだが、表示幅としては 1 として扱う。
 */
class StrWidthTest extends TestCase
{
    // =========================================================================
    // 基本動作
    // =========================================================================

    #[Test]
    public function 空文字列の幅は0(): void
    {
        $this->assertSame(0, mb_strwidth('', 'UTF-8'));
    }

    #[Test]
    public function ASCII文字は1文字幅1(): void
    {
        $this->assertSame(3, mb_strwidth('ABC', 'UTF-8'));
        $this->assertSame(5, mb_strwidth('Hello', 'UTF-8'));
    }

    #[Test]
    public function 数字は1文字幅1(): void
    {
        $this->assertSame(3, mb_strwidth('123', 'UTF-8'));
    }

    #[Test]
    public function 全角ひらがなは1文字幅2(): void
    {
        // 'あいう' = 3文字 × 2 = 6
        $this->assertSame(6, mb_strwidth('あいう', 'UTF-8'));
    }

    #[Test]
    public function 全角カタカナは1文字幅2(): void
    {
        // 'アイウ' = 3文字 × 2 = 6
        $this->assertSame(6, mb_strwidth('アイウ', 'UTF-8'));
    }

    #[Test]
    public function 漢字は1文字幅2(): void
    {
        // '日本語' = 3文字 × 2 = 6
        $this->assertSame(6, mb_strwidth('日本語', 'UTF-8'));
    }

    #[Test]
    public function 半角カタカナは1文字幅1(): void
    {
        // SJIS-win では 2 バイトだが、表示幅としては 1
        // 'ｱｲｳ' = 3文字 × 1 = 3
        $this->assertSame(3, mb_strwidth('ｱｲｳ', 'UTF-8'));
    }

    // =========================================================================
    // 混在文字列
    // =========================================================================

    #[Test]
    #[DataProvider('strWidthProvider')]
    public function 混在文字列の幅を正しく計算する(string $input, int $expectedWidth): void
    {
        $this->assertSame($expectedWidth, mb_strwidth($input, 'UTF-8'));
    }

    public static function strWidthProvider(): array
    {
        return [
            'ASCII + 全角'      => ['AB日本', 2 + 4],       // 2 + 2 + 2 = 6
            '全角 + 半角カナ'   => ['アイｱｲ', 2 + 2 + 1 + 1], // 6
            '全角 + ASCII'      => ['テストABC', 6 + 3],    // 9
            'ひらがな + 漢字'   => ['あいう漢字', 6 + 4],   // 10
            '全角記号'          => ['。、「」', 8],          // 4文字 × 2
            '長音符'            => ['ガイドー', 8],          // 4文字 × 2
        ];
    }

    // =========================================================================
    // SJISバイト数との差異確認
    // =========================================================================

    #[Test]
    public function 半角カナは文字幅もSJISも1(): void
    {
        $str = 'ｱｲｳ';

        // 文字幅（mb_strwidth）: 半角カナ = 1 なので 3
        $this->assertSame(3, mb_strwidth($str, 'UTF-8'));

        // SJISバイト数: 半角カナは 0xA1-0xDF の1バイト表現なので 3
        $sjisBytes = strlen(mb_convert_encoding($str, 'SJIS-win', 'UTF-8'));
        $this->assertSame(3, $sjisBytes);
    }

    #[Test]
    public function ASCII文字は文字幅もSJISも1(): void
    {
        $str = 'ABC';

        $this->assertSame(3, mb_strwidth($str, 'UTF-8'));
        $sjisBytes = strlen(mb_convert_encoding($str, 'SJIS-win', 'UTF-8'));
        $this->assertSame(3, $sjisBytes);
    }

    #[Test]
    public function 全角文字は文字幅2でSJISも2バイト(): void
    {
        $str = '日本語';

        $this->assertSame(6, mb_strwidth($str, 'UTF-8'));
        $sjisBytes = strlen(mb_convert_encoding($str, 'SJIS-win', 'UTF-8'));
        $this->assertSame(6, $sjisBytes);
    }
}
