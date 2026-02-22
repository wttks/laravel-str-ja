<?php

namespace Wttks\StrJa\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wttks\StrJa\SjisConverter;

/**
 * SjisConverter の正確性テスト
 */
class SjisConverterTest extends TestCase
{
    // =========================================================================
    // toSjis: 基本変換
    // =========================================================================

    #[Test]
    public function 空文字列はそのまま返す(): void
    {
        $this->assertSame('', SjisConverter::toSjis(''));
        $this->assertSame('', SjisConverter::fromSjis(''));
    }

    #[Test]
    public function asciiのみの文字列はSJISに変換できる(): void
    {
        $result = SjisConverter::toSjis('Hello World 123');
        $this->assertSame('Hello World 123', SjisConverter::fromSjis($result));
    }

    #[Test]
    public function 通常の日本語はSJIS変換してUTF8に戻せる(): void
    {
        $original = '日本語のテスト文字列です。';
        $sjis = SjisConverter::toSjis($original);
        $restored = SjisConverter::fromSjis($sjis);
        $this->assertSame($original, $restored);
    }

    #[Test]
    public function 全角英数字はNFKC正規化で半角に変換されてからSJIS変換される(): void
    {
        // NFKC正規化により全角→半角に変換されるため、ラウンドトリップでは半角で戻る
        $sjis = SjisConverter::toSjis('ＡＢＣＤ１２３４！＠＃');
        $restored = SjisConverter::fromSjis($sjis);
        $this->assertSame('ABCD1234!@#', $restored);
    }

    #[Test]
    public function toSjisの戻り値はSJIS_winエンコードになっている(): void
    {
        $result = SjisConverter::toSjis('テスト');
        // SJIS-winはUTF-8ではない
        $this->assertNotSame('テスト', $result);
        // mb_detect_encoding でSJIS系として検出される
        $detected = mb_detect_encoding($result, ['SJIS-win', 'UTF-8'], true);
        $this->assertSame('SJIS-win', $detected);
    }

    // =========================================================================
    // normalize: IBM拡張文字・異体字の変換
    // =========================================================================

    #[Test]
    #[DataProvider('ibmExtendedCharProvider')]
    public function IBM拡張文字が代替文字に置換される(string $input, string $expected): void
    {
        $this->assertSame($expected, SjisConverter::normalize($input));
    }

    public static function ibmExtendedCharProvider(): array
    {
        return [
            'はしご高' => ['髙', '高'],
            'たつさき' => ['﨑', '崎'],
            'EMダッシュ' => ['—', '-'],
            'ENダッシュ' => ['–', '-'],
            'マイナス記号' => ['−', '－'],
            // U+2225 平行記号はSJIS-win変換可能なためテーブル不要（変換されない）
            '平行記号' => ['∥', '∥'],
            // 全角チルダ(U+FF5E)はNFKC正規化で半角チルダ(~)になる（strtrより先にNFKCが動作するため）
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
        $this->assertSame($expected, SjisConverter::normalize($input));
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
        $sjis = SjisConverter::toSjis($original);
        $restored = SjisConverter::fromSjis($sjis);
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
        $sjis = SjisConverter::toSjis($input);
        $restored = SjisConverter::fromSjis($sjis);
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
    // byteLength: SJIS-win 変換後バイト数
    // =========================================================================

    #[Test]
    public function 空文字列のバイト数は0(): void
    {
        $this->assertSame(0, SjisConverter::byteLength(''));
    }

    #[Test]
    public function ASCII文字は1文字1バイト(): void
    {
        $this->assertSame(3, SjisConverter::byteLength('ABC'));
    }

    #[Test]
    public function 日本語は1文字2バイト(): void
    {
        // 「日本語」= 3文字 × 2バイト = 6バイト
        $this->assertSame(6, SjisConverter::byteLength('日本語'));
    }

    #[Test]
    public function 混在文字列のバイト数を正しく計算する(): void
    {
        // 'AB' (2) + '日本' (4) = 6バイト
        $this->assertSame(6, SjisConverter::byteLength('AB日本'));
    }

    #[Test]
    public function normalizeオプションなしは変換前のバイト数を返す(): void
    {
        // 髙（はしご高）はSJIS変換前は髙のまま → 2バイト
        // normalize: false（デフォルト）
        $this->assertSame(2, SjisConverter::byteLength('髙'));
    }

    #[Test]
    public function normalizeオプションありは正規化後のバイト数を返す(): void
    {
        // 髙 → normalize → 高 → SJIS → 2バイト（どちらも2バイトだが正規化が通ることを確認）
        $this->assertSame(2, SjisConverter::byteLength('髙', normalize: true));

        // ㈱ → normalize → (株) → SJIS → 4バイト
        $this->assertSame(4, SjisConverter::byteLength('㈱', normalize: true));
        // normalize: false では ㈱ はSJIS-win（NEC拡張）で2バイトのまま変換される
        $this->assertSame(2, SjisConverter::byteLength('㈱', normalize: false));
    }

    // =========================================================================
    // エッジケース
    // =========================================================================

    #[Test]
    public function 改行コードは変換しても保持される(): void
    {
        $original = "1行目\n2行目\r\n3行目";
        $sjis = SjisConverter::toSjis($original);
        $restored = SjisConverter::fromSjis($sjis);
        $this->assertSame($original, $restored);
    }

    #[Test]
    public function タブ文字は変換しても保持される(): void
    {
        $original = "列1\t列2\t列3";
        $sjis = SjisConverter::toSjis($original);
        $restored = SjisConverter::fromSjis($sjis);
        $this->assertSame($original, $restored);
    }

    #[Test]
    public function 長大な文字列でも正しく変換できる(): void
    {
        // 1万文字のテスト
        $original = str_repeat('日本語テスト髙橋﨑山', 1000);
        $expected = str_repeat('日本語テスト高橋崎山', 1000);
        $sjis = SjisConverter::toSjis($original);
        $restored = SjisConverter::fromSjis($sjis);
        $this->assertSame($expected, $restored);
    }

    #[Test]
    public function normalizeは異体字と互換文字が混在していても正しく変換する(): void
    {
        $input = '髙橋㈱の﨑山支店';
        $expected = '高橋(株)の崎山支店';
        $this->assertSame($expected, SjisConverter::normalize($input));
    }

    // =========================================================================
    // truncateByBytes: SJISバイト数上限での切り捨て
    // =========================================================================

    #[Test]
    public function truncateByBytes_空文字はそのまま返す(): void
    {
        $this->assertSame('', SjisConverter::truncateByBytes('', 10));
    }

    #[Test]
    public function truncateByBytes_maxBytesが0の場合は空文字を返す(): void
    {
        $this->assertSame('', SjisConverter::truncateByBytes('日本語', 0));
    }

    #[Test]
    public function truncateByBytes_バイト数以内の文字列はそのまま返す(): void
    {
        // 'ABC' = 3バイト
        $this->assertSame('ABC', SjisConverter::truncateByBytes('ABC', 10));
        // '日本語' = 6バイト
        $this->assertSame('日本語', SjisConverter::truncateByBytes('日本語', 6));
    }

    #[Test]
    public function truncateByBytes_ASCII文字を1バイト単位で切り捨てる(): void
    {
        // 'ABCDE' = 5バイト → 3バイトで切ると 'ABC'
        $this->assertSame('ABC', SjisConverter::truncateByBytes('ABCDE', 3));
    }

    #[Test]
    public function truncateByBytes_全角文字を2バイト単位で切り捨てる(): void
    {
        // '日本語' = 6バイト → 4バイトで切ると '日本'
        $this->assertSame('日本', SjisConverter::truncateByBytes('日本語', 4));
        // '日本語' = 6バイト → 2バイトで切ると '日'
        $this->assertSame('日', SjisConverter::truncateByBytes('日本語', 2));
    }

    #[Test]
    public function truncateByBytes_全角文字の途中で切り捨てない(): void
    {
        // '日本語' = 6バイト → 奇数バイト指定（3, 5）でも文字の途中では切らない
        // 3バイト → '日'（2バイト）で止まる（3バイト目は '本' の1バイト目）
        $this->assertSame('日', SjisConverter::truncateByBytes('日本語', 3));
        // 5バイト → '日本'（4バイト）で止まる（5バイト目は '語' の1バイト目）
        $this->assertSame('日本', SjisConverter::truncateByBytes('日本語', 5));
    }

    #[Test]
    public function truncateByBytes_混在文字列を正しく切り捨てる(): void
    {
        // 'AB日本' = 2 + 4 = 6バイト
        $this->assertSame('AB日本', SjisConverter::truncateByBytes('AB日本', 6));
        // → 4バイトで切ると 'AB日'（2 + 2 = 4）
        $this->assertSame('AB日', SjisConverter::truncateByBytes('AB日本', 4));
        // → 3バイトで切ると 'AB'（2 + 途中の全角は入らない）
        $this->assertSame('AB', SjisConverter::truncateByBytes('AB日本', 3));
    }

    #[Test]
    public function truncateByBytes_半角カナは1バイトとしてカウントする(): void
    {
        // SJIS-winでは半角カナは1バイト（0xA1-0xDF）
        // 'ｱｲｳ' = 1 + 1 + 1 = 3バイト
        $this->assertSame('ｱｲｳ', SjisConverter::truncateByBytes('ｱｲｳ', 3));
        // → 2バイトで切ると 'ｱｲ'
        $this->assertSame('ｱｲ', SjisConverter::truncateByBytes('ｱｲｳ', 2));
        // → 1バイトで切ると 'ｱ'
        $this->assertSame('ｱ', SjisConverter::truncateByBytes('ｱｲｳ', 1));
    }

    #[Test]
    public function truncateByBytes_normalizeオプションで正規化後に切り捨てる(): void
    {
        // '㈱テスト' → normalize → '(株)テスト'
        // '(株)テスト' = 4 + 4 = 8バイト
        // normalize: false では '㈱' = 2バイト → '㈱テ' = 4バイト
        $this->assertSame('㈱テ', SjisConverter::truncateByBytes('㈱テスト', 4, normalize: false));
        // normalize: true では '(株)テスト' → 4バイトで '(株' = 3バイト → '(株' の実際は '(' + '株' + ')' の順
        // '(株)テスト' の各文字: '(' = 1, '株' = 2, ')' = 1, 'テ' = 2, 'ス' = 2, 'ト' = 2
        // → 4バイト: '(' + '株' + ')' = 4バイト → '(株)'
        $this->assertSame('(株)', SjisConverter::truncateByBytes('㈱テスト', 4, normalize: true));
    }

    #[Test]
    public function truncateByBytes_切り捨て後のバイト数はmaxBytes以内に収まる(): void
    {
        $inputs = ['日本語テストABCDE', str_repeat('あ', 50), 'ｱｲｳｴｵ日本語ABC'];
        foreach ($inputs as $input) {
            for ($max = 1; $max <= 20; $max++) {
                $result = SjisConverter::truncateByBytes($input, $max);
                $bytes = strlen(mb_convert_encoding($result, 'SJIS-win', 'UTF-8'));
                $this->assertLessThanOrEqual($max, $bytes, "'{$input}' を {$max} バイトで切った結果 '{$result}' が {$bytes} バイトで超過");
            }
        }
    }

    #[Test]
    public function truncateByBytes_切り捨て後の文字列はSJIS変換してUTF8に戻せる(): void
    {
        // 文字の途中で切られていなければラウンドトリップが成立する
        $input = '日本語テストABC半角ｱｲｳ';
        for ($max = 1; $max <= 30; $max++) {
            $result = SjisConverter::truncateByBytes($input, $max);
            $sjis = mb_convert_encoding($result, 'SJIS-win', 'UTF-8');
            $restored = mb_convert_encoding($sjis, 'UTF-8', 'SJIS-win');
            $this->assertSame($result, $restored, "{$max} バイト切り捨て後のラウンドトリップ失敗: '{$result}'");
        }
    }

    // =========================================================================
    // 変換テーブル全エントリの網羅確認
    // =========================================================================

    #[Test]
    public function 変換テーブルの全変換先文字はSJIS変換できる(): void
    {
        // toSjis() はNFKC正規化込みのため、変換先文字のみ mb_convert_encoding で直接確認する
        $table = require __DIR__ . '/../../src/Data/IbmExtendedChars.php';
        foreach ($table as $from => $to) {
            $sjis = mb_convert_encoding($to, 'SJIS-win', 'UTF-8');
            $restored = mb_convert_encoding($sjis, 'UTF-8', 'SJIS-win');
            $this->assertSame($to, $restored, "変換先文字 '{$to}' (from: '{$from}') のラウンドトリップが失敗");
        }
    }

    // =========================================================================
    // 3実装の出力一致確認
    // =========================================================================

    #[Test]
    #[DataProvider('implementationConsistencyProvider')]
    public function 三種の実装は同じ結果を返す(string $input): void
    {
        $expected = SjisConverter::normalize($input);
        $withStrReplace = SjisConverter::normalizeWithStrReplace($input);
        $withPregReplace = SjisConverter::normalizeWithPregReplace($input);

        $this->assertSame($expected, $withStrReplace, 'strtr と str_replace の結果が一致しない');
        $this->assertSame($expected, $withPregReplace, 'strtr と preg_replace の結果が一致しない');
    }

    public static function implementationConsistencyProvider(): array
    {
        return [
            '通常文字列' => ['日本語のテスト'],
            'IBM拡張文字' => ['髙橋﨑山'],
            'Unicode互換文字' => ['㈱㈲①⑩㎝'],
            '混在' => ['髙橋㈱の﨑山支店、①号室'],
            '空文字' => [''],
            'ASCII' => ['Hello World'],
        ];
    }
}
