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

    // =========================================================================
    // splitByWhitespace
    // =========================================================================

    #[Test]
    public function splitByWhitespace_空文字列は空配列を返す(): void
    {
        $this->assertSame([], JaNormalizer::splitByWhitespace(''));
    }

    #[Test]
    public function splitByWhitespace_空白なしはそのまま1要素で返す(): void
    {
        $this->assertSame(['日本語'], JaNormalizer::splitByWhitespace('日本語'));
        $this->assertSame(['Hello'], JaNormalizer::splitByWhitespace('Hello'));
    }

    #[Test]
    public function splitByWhitespace_半角スペースで分割(): void
    {
        $this->assertSame(['Hello', 'World'], JaNormalizer::splitByWhitespace('Hello World'));
    }

    #[Test]
    public function splitByWhitespace_全角スペースで分割(): void
    {
        // U+3000 IDEOGRAPHIC SPACE
        $this->assertSame(['日本語', 'テスト'], JaNormalizer::splitByWhitespace("日本語\u{3000}テスト"));
    }

    #[Test]
    public function splitByWhitespace_タブで分割(): void
    {
        $this->assertSame(['列1', '列2', '列3'], JaNormalizer::splitByWhitespace("列1\t列2\t列3"));
    }

    #[Test]
    public function splitByWhitespace_改行で分割(): void
    {
        $this->assertSame(['1行目', '2行目', '3行目'], JaNormalizer::splitByWhitespace("1行目\n2行目\r\n3行目"));
    }

    #[Test]
    public function splitByWhitespace_NBSPで分割(): void
    {
        // U+00A0 NO-BREAK SPACE（コピペで混入しやすい）
        $this->assertSame(['Hello', 'World'], JaNormalizer::splitByWhitespace("Hello\u{00A0}World"));
    }

    #[Test]
    public function splitByWhitespace_細いスペースで分割(): void
    {
        // U+2009 THIN SPACE
        $this->assertSame(['A', 'B'], JaNormalizer::splitByWhitespace("A\u{2009}B"));
    }

    #[Test]
    public function splitByWhitespace_ゼロ幅スペースで分割(): void
    {
        // U+200B ZERO WIDTH SPACE（不可視。コピペで混入しやすい）
        $this->assertSame(['日本語', 'テスト'], JaNormalizer::splitByWhitespace("日本語\u{200B}テスト"));
    }

    #[Test]
    public function splitByWhitespace_連続した空白は1つの区切りとして扱う(): void
    {
        $this->assertSame(['A', 'B'], JaNormalizer::splitByWhitespace('A   B'));
        $this->assertSame(['日本語', 'テスト'], JaNormalizer::splitByWhitespace("日本語\u{3000}\u{3000}テスト"));
    }

    #[Test]
    public function splitByWhitespace_全角と半角の混在空白は1つの区切り(): void
    {
        // 全角スペース + 半角スペース + タブ が連続
        $this->assertSame(['A', 'B'], JaNormalizer::splitByWhitespace("A\u{3000} \tB"));
    }

    #[Test]
    public function splitByWhitespace_先頭と末尾の空白は無視される(): void
    {
        $this->assertSame(['Hello', 'World'], JaNormalizer::splitByWhitespace('  Hello World  '));
        $this->assertSame(['日本語'], JaNormalizer::splitByWhitespace("\u{3000}日本語\u{3000}"));
    }

    #[Test]
    public function splitByWhitespace_空白のみの文字列は空配列を返す(): void
    {
        $this->assertSame([], JaNormalizer::splitByWhitespace('   '));
        $this->assertSame([], JaNormalizer::splitByWhitespace("\u{3000}\u{3000}"));
        $this->assertSame([], JaNormalizer::splitByWhitespace("\t\n\r"));
    }

    #[Test]
    #[DataProvider('splitByWhitespace_各種空白文字Provider')]
    public function splitByWhitespace_各種空白文字で正しく分割できる(string $separator, string $description): void
    {
        $input = "前{$separator}後";
        $result = JaNormalizer::splitByWhitespace($input);
        $this->assertSame(['前', '後'], $result, "{$description} で分割できなかった");
    }

    public static function splitByWhitespace_各種空白文字Provider(): array
    {
        return [
            '半角スペース'       => ["\u{0020}", 'U+0020 SPACE'],
            'NBSP'               => ["\u{00A0}", 'U+00A0 NO-BREAK SPACE'],
            'EN SPACE'           => ["\u{2002}", 'U+2002 EN SPACE'],
            'EM SPACE'           => ["\u{2003}", 'U+2003 EM SPACE'],
            'THIN SPACE'         => ["\u{2009}", 'U+2009 THIN SPACE'],
            'HAIR SPACE'         => ["\u{200A}", 'U+200A HAIR SPACE'],
            'ゼロ幅スペース'     => ["\u{200B}", 'U+200B ZERO WIDTH SPACE'],
            '全角スペース'       => ["\u{3000}", 'U+3000 IDEOGRAPHIC SPACE'],
            'タブ'               => ["\t",        'U+0009 TAB'],
            '改行LF'             => ["\n",        'U+000A LINE FEED'],
            '改行CR'             => ["\r",        'U+000D CARRIAGE RETURN'],
        ];
    }

    #[Test]
    public function splitByWhitespace_実用シナリオ_日本語混在テキスト(): void
    {
        // フォームからのコピペ入力（全角・半角・NBSPが混在）
        $input = "山田　太郎\u{00A0}yamada@example.com";
        $this->assertSame(['山田', '太郎', 'yamada@example.com'], JaNormalizer::splitByWhitespace($input));
    }

    #[Test]
    public function splitByWhitespace_実用シナリオ_タグ入力の分割(): void
    {
        // スペース区切りのタグ入力
        $input = 'PHP Laravel　日本語　テスト';
        $this->assertSame(['PHP', 'Laravel', '日本語', 'テスト'], JaNormalizer::splitByWhitespace($input));
    }

    // =========================================================================
    // hasTroubleChars
    // =========================================================================

    #[Test]
    public function hasTroubleChars_通常文字列はfalse(): void
    {
        $this->assertFalse(JaNormalizer::hasTroubleChars(''));
        $this->assertFalse(JaNormalizer::hasTroubleChars('日本語のテスト'));
        $this->assertFalse(JaNormalizer::hasTroubleChars('Hello World 123'));
    }

    #[Test]
    public function hasTroubleChars_タブと改行はfalse(): void
    {
        // タブ・LF・CR は通常テキストで使用されるため対象外
        $this->assertFalse(JaNormalizer::hasTroubleChars("列1\t列2"));
        $this->assertFalse(JaNormalizer::hasTroubleChars("1行目\n2行目"));
        $this->assertFalse(JaNormalizer::hasTroubleChars("1行目\r\n2行目"));
    }

    #[Test]
    #[DataProvider('troubleCharsProvider')]
    public function hasTroubleChars_トラブル文字を含む場合はtrue(string $char, string $description): void
    {
        $this->assertTrue(JaNormalizer::hasTroubleChars($char), "{$description} が検出されなかった");
        // 通常文字と混在しても検出できる
        $this->assertTrue(JaNormalizer::hasTroubleChars("日本語{$char}テスト"), "{$description} が混在時に検出されなかった");
    }

    public static function troubleCharsProvider(): array
    {
        return [
            'NULL'              => ["\x00",         'U+0000 NULL'],
            'BEL'               => ["\x07",         'U+0007 BEL'],
            'BS'                => ["\x08",         'U+0008 BS'],
            'VT'                => ["\x0B",         'U+000B VT'],
            'FF'                => ["\x0C",         'U+000C FF'],
            'ESC'               => ["\x1B",         'U+001B ESC'],
            'DEL'               => ["\x7F",         'U+007F DEL'],
            'ゼロ幅スペース'     => ["\u{200B}",    'U+200B ZERO WIDTH SPACE'],
            'ZWNJ'              => ["\u{200C}",    'U+200C ZERO WIDTH NON-JOINER'],
            'ZWJ'               => ["\u{200D}",    'U+200D ZERO WIDTH JOINER'],
            'LRM'               => ["\u{200E}",    'U+200E LEFT-TO-RIGHT MARK'],
            'RLM'               => ["\u{200F}",    'U+200F RIGHT-TO-LEFT MARK'],
            '双方向制御'         => ["\u{202A}",    'U+202A LEFT-TO-RIGHT EMBEDDING'],
            'Word Joiner'       => ["\u{2060}",    'U+2060 WORD JOINER'],
            'BOM'               => ["\u{FEFF}",    'U+FEFF BOM'],
        ];
    }

    // =========================================================================
    // removeTroubleChars
    // =========================================================================

    #[Test]
    public function removeTroubleChars_通常文字列はそのまま返す(): void
    {
        $this->assertSame('', JaNormalizer::removeTroubleChars(''));
        $this->assertSame('日本語のテスト', JaNormalizer::removeTroubleChars('日本語のテスト'));
        $this->assertSame("列1\t列2\n行2", JaNormalizer::removeTroubleChars("列1\t列2\n行2"));
    }

    #[Test]
    public function removeTroubleChars_トラブル文字を削除する(): void
    {
        // ゼロ幅スペース混入（コピペでよくある）
        $this->assertSame('日本語テスト', JaNormalizer::removeTroubleChars("日本語\u{200B}テスト"));

        // BOM が先頭についている
        $this->assertSame('Hello', JaNormalizer::removeTroubleChars("\u{FEFF}Hello"));

        // NULL バイト混入
        $this->assertSame('Hello', JaNormalizer::removeTroubleChars("Hel\x00lo"));

        // 複数種類が混在
        $this->assertSame('テスト', JaNormalizer::removeTroubleChars("\u{FEFF}テ\u{200B}ス\x00ト"));
    }

    #[Test]
    public function removeTroubleChars_タブと改行は保持される(): void
    {
        $input = "列1\t列2\n1行目\r\n2行目";
        $this->assertSame($input, JaNormalizer::removeTroubleChars($input));
    }

    #[Test]
    #[DataProvider('troubleCharsProvider')]
    public function removeTroubleChars_各トラブル文字が削除される(string $char, string $description): void
    {
        $input = "前{$char}後";
        $this->assertSame('前後', JaNormalizer::removeTroubleChars($input), "{$description} が削除されなかった");
    }

    // =========================================================================
    // sanitize
    // =========================================================================

    #[Test]
    public function sanitize_空文字列はそのまま返す(): void
    {
        $this->assertSame('', JaNormalizer::sanitize(''));
    }

    #[Test]
    public function sanitize_半角カナを全角カナに変換する(): void
    {
        $this->assertSame('ガイドABC', JaNormalizer::sanitize('ｶﾞｲﾄﾞABC'));
        $this->assertSame('パソコン', JaNormalizer::sanitize('ﾊﾟｿｺﾝ'));
    }

    #[Test]
    public function sanitize_全角ASCIIを半角に変換する(): void
    {
        $this->assertSame('ABC123', JaNormalizer::sanitize('ＡＢＣ１２３'));
        $this->assertSame('hello@example.com', JaNormalizer::sanitize('ｈｅｌｌｏ＠ｅｘａｍｐｌｅ．ｃｏｍ'));
    }

    #[Test]
    public function sanitize_連続した空白を半角スペース1つに正規化する(): void
    {
        $this->assertSame('山田 太郎', JaNormalizer::sanitize('山田　　太郎'));   // 全角スペース連続
        $this->assertSame('Hello World', JaNormalizer::sanitize('Hello   World'));
        $this->assertSame('a b c', JaNormalizer::sanitize("a\t\tb\n\nc"));       // タブ・改行
    }

    #[Test]
    public function sanitize_前後の空白をトリムする(): void
    {
        $this->assertSame('テスト', JaNormalizer::sanitize('　テスト　'));
        $this->assertSame('Hello', JaNormalizer::sanitize('  Hello  '));
    }

    #[Test]
    public function sanitize_トラブル文字を削除する(): void
    {
        $this->assertSame('Hello', JaNormalizer::sanitize("\u{FEFF}Hello"));
        $this->assertSame('日本語テスト', JaNormalizer::sanitize("日本語\u{200B}テスト"));
    }

    #[Test]
    public function sanitize_複合ケース(): void
    {
        // 半角カナ + 全角ASCII + 全角スペース + 前後トリム
        $this->assertSame('ガイド ABC 123', JaNormalizer::sanitize('　ｶﾞｲﾄﾞ　ＡＢＣ　１２３　'));
    }

    #[Test]
    public function sanitize_punctuation_trueで句読点も変換する(): void
    {
        $this->assertSame('"テスト"', JaNormalizer::sanitize('"テスト"', punctuation: true));
        $this->assertSame('続く...', JaNormalizer::sanitize('続く…', punctuation: true));
    }

    // =========================================================================
    // squish
    // =========================================================================

    #[Test]
    public function squish_空文字列はそのまま返す(): void
    {
        $this->assertSame('', JaNormalizer::squish(''));
    }

    #[Test]
    public function squish_通常文字列はそのまま返す(): void
    {
        $this->assertSame('日本語テスト', JaNormalizer::squish('日本語テスト'));
        $this->assertSame('Hello World', JaNormalizer::squish('Hello World'));
    }

    #[Test]
    public function squish_連続した半角スペースを1つに正規化する(): void
    {
        $this->assertSame('Hello World', JaNormalizer::squish('Hello   World'));
        $this->assertSame('a b c', JaNormalizer::squish('a  b  c'));
    }

    #[Test]
    public function squish_全角スペースを半角スペース1つに正規化する(): void
    {
        $this->assertSame('山田 太郎', JaNormalizer::squish('山田　太郎'));       // 全角スペース1つ
        $this->assertSame('山田 太郎', JaNormalizer::squish('山田　　太郎'));     // 全角スペース連続
        $this->assertSame('山田 太郎', JaNormalizer::squish('山田　 　太郎'));    // 全角・半角混在
    }

    #[Test]
    public function squish_NBSPや特殊スペースも正規化する(): void
    {
        $this->assertSame('a b', JaNormalizer::squish("a\u{00A0}b"));   // NBSP
        $this->assertSame('a b', JaNormalizer::squish("a\u{2009}b"));   // 細いスペース
        $this->assertSame('a b', JaNormalizer::squish("a\u{3000}b"));   // 全角スペース（ideographic space）
    }

    #[Test]
    public function squish_タブや改行も半角スペースに変換される(): void
    {
        $this->assertSame('a b', JaNormalizer::squish("a\tb"));
        $this->assertSame('a b', JaNormalizer::squish("a\nb"));
        $this->assertSame('a b', JaNormalizer::squish("a\r\nb"));
    }

    #[Test]
    public function squish_前後の空白をトリムする(): void
    {
        $this->assertSame('Hello', JaNormalizer::squish('  Hello  '));
        $this->assertSame('日本語', JaNormalizer::squish('　日本語　'));   // 全角スペース
        $this->assertSame('a b c', JaNormalizer::squish('  a  b  c  '));
    }

    #[Test]
    public function squish_トラブル文字を削除する(): void
    {
        // ゼロ幅スペースは空白扱いで吸収される
        $this->assertSame('日本語テスト', JaNormalizer::squish("日本語\u{200B}テスト"));
        // BOM は削除される
        $this->assertSame('Hello', JaNormalizer::squish("\u{FEFF}Hello"));
        // NULL バイトは削除される
        $this->assertSame('Hello', JaNormalizer::squish("Hel\x00lo"));
    }

    #[Test]
    public function squish_複合ケース(): void
    {
        // BOM + 全角スペース + ゼロ幅スペース + 改行が混在
        $input = "\u{FEFF}山田　　\u{200B}太郎\n\n鈴木";
        $this->assertSame('山田 太郎 鈴木', JaNormalizer::squish($input));
    }

    // =========================================================================
    // countWords
    // =========================================================================

    #[Test]
    public function countWords_空文字列は0を返す(): void
    {
        $this->assertSame(0, JaNormalizer::countWords(''));
    }

    #[Test]
    public function countWords_半角スペース区切りで単語数を返す(): void
    {
        $this->assertSame(1, JaNormalizer::countWords('hello'));
        $this->assertSame(2, JaNormalizer::countWords('hello world'));
        $this->assertSame(3, JaNormalizer::countWords('one two three'));
    }

    #[Test]
    public function countWords_全角スペースや特殊スペースでも分割される(): void
    {
        $this->assertSame(2, JaNormalizer::countWords('山田　太郎'));   // 全角スペース
        $this->assertSame(2, JaNormalizer::countWords("田中\u{00A0}花子")); // NBSP
    }

    #[Test]
    public function countWords_前後の空白は無視される(): void
    {
        $this->assertSame(2, JaNormalizer::countWords(' hello world '));
        $this->assertSame(2, JaNormalizer::countWords('　山田　太郎　'));
    }

    #[Test]
    public function countWords_連続した空白は1つの区切りとして扱われる(): void
    {
        $this->assertSame(2, JaNormalizer::countWords('hello   world'));
        $this->assertSame(3, JaNormalizer::countWords('a  b  c'));
    }
}
