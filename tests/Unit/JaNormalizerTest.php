<?php

namespace Wttks\StrJa\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wttks\StrJa\JaNormalizer;

/**
 * JaNormalizer の正確性テスト
 */
class JaNormalizerTest extends TestCase
{
    // =========================================================================
    // 半角カナ → 全角カナ
    // =========================================================================

    #[Test]
    #[DataProvider('halfKanaToFullKanaProvider')]
    public function 半角カナが全角カナに変換される(string $input, string $expected): void
    {
        $this->assertSame($expected, JaNormalizer::normalize($input));
    }

    public static function halfKanaToFullKanaProvider(): array
    {
        return [
            '半角ア行' => ['ｱｲｳｴｵ', 'アイウエオ'],
            '半角カ行' => ['ｶｷｸｹｺ', 'カキクケコ'],
            '半角サ行' => ['ｻｼｽｾｿ', 'サシスセソ'],
            '半角タ行' => ['ﾀﾁﾂﾃﾄ', 'タチツテト'],
            '半角ナ行' => ['ﾅﾆﾇﾈﾉ', 'ナニヌネノ'],
            '半角ハ行' => ['ﾊﾋﾌﾍﾎ', 'ハヒフヘホ'],
            '半角マ行' => ['ﾏﾐﾑﾒﾓ', 'マミムメモ'],
            '半角ヤ行' => ['ﾔﾕﾖ', 'ヤユヨ'],
            '半角ラ行' => ['ﾗﾘﾙﾚﾛ', 'ラリルレロ'],
            '半角ワ行' => ['ﾜｦﾝ', 'ワヲン'],
            '半角小文字' => ['ｧｨｩｪｫｬｭｮｯ', 'ァィゥェォャュョッ'],
            '半角句読点' => ['｡｢｣､･', '。「」、・'],
            '半角長音' => ['ｰ', 'ー'],
        ];
    }

    // =========================================================================
    // 濁音・半濁音を1文字に結合
    // =========================================================================

    #[Test]
    #[DataProvider('dakutenCombineProvider')]
    public function 濁音半濁音が1文字に結合される(string $input, string $expected): void
    {
        $this->assertSame($expected, JaNormalizer::normalize($input));
    }

    public static function dakutenCombineProvider(): array
    {
        return [
            '半角ガ（ｶ+ﾞ）' => ['ｶﾞ', 'ガ'],
            '半角ギ（ｷ+ﾞ）' => ['ｷﾞ', 'ギ'],
            '半角グ（ｸ+ﾞ）' => ['ｸﾞ', 'グ'],
            '半角ゲ（ｹ+ﾞ）' => ['ｹﾞ', 'ゲ'],
            '半角ゴ（ｺ+ﾞ）' => ['ｺﾞ', 'ゴ'],
            '半角ザ（ｻ+ﾞ）' => ['ｻﾞ', 'ザ'],
            '半角パ（ﾊ+ﾟ）' => ['ﾊﾟ', 'パ'],
            '半角ピ（ﾋ+ﾟ）' => ['ﾋﾟ', 'ピ'],
            '半角プ（ﾌ+ﾟ）' => ['ﾌﾟ', 'プ'],
            '半角ペ（ﾍ+ﾟ）' => ['ﾍﾟ', 'ペ'],
            '半角ポ（ﾎ+ﾟ）' => ['ﾎﾟ', 'ポ'],
            '混在文字列' => ['ｶﾞｷﾞｸﾞﾊﾟﾋﾟ', 'ガギグパピ'],
            '文章中の半角カナ' => ['ｶﾞｲﾄﾞの名前はﾀﾅｶです', 'ガイドの名前はタナカです'],
        ];
    }

    // =========================================================================
    // 全角ASCII → 半角
    // =========================================================================

    #[Test]
    #[DataProvider('fullAsciiToHalfProvider')]
    public function 全角ASCII文字が半角に変換される(string $input, string $expected): void
    {
        $this->assertSame($expected, JaNormalizer::normalize($input));
    }

    public static function fullAsciiToHalfProvider(): array
    {
        return [
            '全角大文字' => ['ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
            '全角小文字' => ['ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ', 'abcdefghijklmnopqrstuvwxyz'],
            '全角数字' => ['０１２３４５６７８９', '0123456789'],
            '全角記号' => ['！＂＃＄％＆＇（）＊＋，－．／', '!"#$%&\'()*+,-./'],
            '全角コロン等' => ['：；＜＝＞？＠', ':;<=>?@'],
            '全角括弧等' => ['［＼］＾＿｀', '[\\]^_`'],
            '全角スペース' => ['　', ' '],
        ];
    }

    // =========================================================================
    // 全角カタカナは変換されない（保持される）
    // =========================================================================

    #[Test]
    #[DataProvider('fullKanaPreservedProvider')]
    public function 全角カタカナは変換されず保持される(string $input): void
    {
        $this->assertSame($input, JaNormalizer::normalize($input));
    }

    public static function fullKanaPreservedProvider(): array
    {
        return [
            '全角ア行' => ['アイウエオ'],
            '全角カ行' => ['カキクケコ'],
            '全角濁音' => ['ガギグゲゴザジズゼゾ'],
            '全角半濁音' => ['パピプペポ'],
            '全角長音' => ['ター'],
        ];
    }

    // =========================================================================
    // ひらがな・漢字は変換されない
    // =========================================================================

    #[Test]
    public function ひらがな漢字は変換されず保持される(): void
    {
        $input = 'あいうえお日本語テスト';
        $this->assertSame($input, JaNormalizer::normalize($input));
    }

    // =========================================================================
    // エッジケース
    // =========================================================================

    #[Test]
    public function 空文字列はそのまま返す(): void
    {
        $this->assertSame('', JaNormalizer::normalize(''));
    }

    #[Test]
    public function ASCIIのみの文字列はそのまま返す(): void
    {
        $input = 'Hello World 123 !@#';
        $this->assertSame($input, JaNormalizer::normalize($input));
    }

    #[Test]
    public function 混在文字列を正しく正規化する(): void
    {
        // 全角英数・半角カナ・濁音・日本語が混在
        $input = 'ＡＢＣ１２３ｶﾞｲﾄﾞ日本語テスト！';
        $expected = 'ABC123ガイド日本語テスト!';
        $this->assertSame($expected, JaNormalizer::normalize($input));
    }

    #[Test]
    public function 長大な文字列でも正しく正規化できる(): void
    {
        $input = str_repeat('ＡＢＣ１２３ｶﾞｲﾄﾞ日本語！', 1000);
        $expected = str_repeat('ABC123ガイド日本語!', 1000);
        $this->assertSame($expected, JaNormalizer::normalize($input));
    }

    #[Test]
    public function 改行コードは保持される(): void
    {
        $input = "ＡＢＣ\nｶﾞｲﾄﾞ\r\n１２３";
        $expected = "ABC\nガイド\r\n123";
        $this->assertSame($expected, JaNormalizer::normalize($input));
    }

    // =========================================================================
    // 制御文字削除（常時）
    // =========================================================================

    #[Test]
    #[DataProvider('controlCharsProvider')]
    public function 不可視制御文字が削除される(string $input, string $expected): void
    {
        $this->assertSame($expected, JaNormalizer::normalize($input));
    }

    public static function controlCharsProvider(): array
    {
        return [
            'ゼロ幅スペース' => ["テスト\u{200B}文字列", 'テスト文字列'],
            'ゼロ幅非結合子' => ["テスト\u{200C}文字列", 'テスト文字列'],
            'ゼロ幅結合子' => ["テスト\u{200D}文字列", 'テスト文字列'],
            'LTRマーク' => ["テスト\u{200E}文字列", 'テスト文字列'],
            'RTLマーク' => ["テスト\u{200F}文字列", 'テスト文字列'],
            'BOM' => ["\u{FEFF}テスト文字列", 'テスト文字列'],
            'Word Joiner' => ["テスト\u{2060}文字列", 'テスト文字列'],
            '複数混在' => ["\u{FEFF}テスト\u{200B}文字\u{200D}列", 'テスト文字列'],
            '制御文字のみ' => ["\u{FEFF}\u{200B}\u{200C}", ''],
        ];
    }

    // =========================================================================
    // 一般句読点変換（punctuation: true）
    // =========================================================================

    #[Test]
    #[DataProvider('punctuationProvider')]
    public function punctuationオプションで一般句読点が変換される(string $input, string $expected): void
    {
        $this->assertSame($expected, JaNormalizer::normalize($input, punctuation: true));
    }

    public static function punctuationProvider(): array
    {
        return [
            '左右ダブルクォート' => ['"テスト"', '"テスト"'],
            '左右シングルクォート' => ["\u{2018}テスト\u{2019}", "'テスト'"],
            'ダブルロー9引用符' => ["\u{201E}テスト\u{201D}", '"テスト"'],
            '水平省略記号' => ['テスト…続く', 'テスト...続く'],
            '二点リーダー' => ["テスト\u{2025}続く", 'テスト..続く'],
            'ENダッシュ' => ["テスト\u{2013}終わり", 'テスト-終わり'],
            'EMダッシュ' => ["テスト\u{2014}終わり", 'テスト-終わり'],
            '水平バー' => ["テスト\u{2015}終わり", 'テスト-終わり'],
            'ハイフン' => ["テスト\u{2010}終わり", 'テスト-終わり'],
            'プライム' => ["100\u{2032}", "100'"],
            'ダブルプライム' => ["100\u{2033}", '100"'],
            '混在' => ["\u{201C}髙橋さん\u{2026}\u{2013}テスト\u{201D}", '"髙橋さん...-テスト"'],
        ];
    }

    #[Test]
    public function punctuationオプションfalseでは一般句読点は変換されない(): void
    {
        $input = '"テスト"';
        // デフォルト（false）では変換しない
        $this->assertSame($input, JaNormalizer::normalize($input));
        $this->assertSame($input, JaNormalizer::normalize($input, punctuation: false));
    }

    #[Test]
    public function 米印は変換されない(): void
    {
        $input = '※注意事項';
        $this->assertSame($input, JaNormalizer::normalize($input, punctuation: true));
    }

    #[Test]
    public function パーミルは変換されない(): void
    {
        $input = "5\u{2030}";
        $this->assertSame($input, JaNormalizer::normalize($input, punctuation: true));
    }
}
