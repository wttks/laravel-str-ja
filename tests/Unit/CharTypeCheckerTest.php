<?php

namespace Wttks\StrJa\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wttks\StrJa\CharTypeChecker;

/**
 * CharTypeChecker の文字種判定テスト
 */
class CharTypeCheckerTest extends TestCase
{
    // =========================================================================
    // isHiragana
    // =========================================================================

    #[Test]
    public function isHiragana_空文字はfalse(): void
    {
        $this->assertFalse(CharTypeChecker::isHiragana(''));
    }

    #[Test]
    #[DataProvider('isHiragana_trueProvider')]
    public function isHiragana_ひらがなのみはtrue(string $input): void
    {
        $this->assertTrue(CharTypeChecker::isHiragana($input));
    }

    public static function isHiragana_trueProvider(): array
    {
        return [
            '五十音（あ行）'  => ['あいうえお'],
            '五十音（か行）'  => ['かきくけこ'],
            '五十音（さ行）'  => ['さしすせそ'],
            '五十音（た行）'  => ['たちつてと'],
            '五十音（な行）'  => ['なにぬねの'],
            '五十音（は行）'  => ['はひふへほ'],
            '五十音（ま行）'  => ['まみむめも'],
            '五十音（や行）'  => ['やゆよ'],
            '五十音（ら行）'  => ['らりるれろ'],
            '五十音（わ行）'  => ['わをん'],
            '濁音'           => ['がぎぐげご'],
            '半濁音'         => ['ぱぴぷぺぽ'],
            '小文字'         => ['ぁぃぅぇぉっ'],
            '長音符を含む'    => ['あいうーえお'],
            '中点を含む'     => ['あ・い'],
            '長文'           => ['あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをん'],
        ];
    }

    #[Test]
    #[DataProvider('isHiragana_falseProvider')]
    public function isHiragana_ひらがな以外を含むはfalse(string $input): void
    {
        $this->assertFalse(CharTypeChecker::isHiragana($input));
    }

    public static function isHiragana_falseProvider(): array
    {
        return [
            'カタカナ混在'     => ['あいうアイウ'],
            '漢字混在'         => ['あい漢字'],
            'ASCII混在'        => ['あいうabc'],
            '半角カナ混在'     => ['あいｱｲ'],
            'カタカナのみ'     => ['アイウエオ'],
            '漢字のみ'         => ['日本語'],
            'ASCIIのみ'        => ['abc'],
            '数字のみ'         => ['123'],
            '半角カナのみ'     => ['ｱｲｳ'],
            'スペース混在'     => ['あ い'],
        ];
    }

    // =========================================================================
    // isKatakana
    // =========================================================================

    #[Test]
    public function isKatakana_空文字はfalse(): void
    {
        $this->assertFalse(CharTypeChecker::isKatakana(''));
    }

    #[Test]
    #[DataProvider('isKatakana_trueProvider')]
    public function isKatakana_全角カタカナのみはtrue(string $input): void
    {
        $this->assertTrue(CharTypeChecker::isKatakana($input));
    }

    public static function isKatakana_trueProvider(): array
    {
        return [
            '五十音（ア行）'  => ['アイウエオ'],
            '五十音（カ行）'  => ['カキクケコ'],
            '五十音（サ行）'  => ['サシスセソ'],
            '五十音（タ行）'  => ['タチツテト'],
            '五十音（ナ行）'  => ['ナニヌネノ'],
            '五十音（ハ行）'  => ['ハヒフヘホ'],
            '五十音（マ行）'  => ['マミムメモ'],
            '五十音（ヤ行）'  => ['ヤユヨ'],
            '五十音（ラ行）'  => ['ラリルレロ'],
            '五十音（ワ行）'  => ['ワヲン'],
            '濁音'           => ['ガギグゲゴ'],
            '半濁音'         => ['パピプペポ'],
            '小文字'         => ['ァィゥェォッ'],
            '長音符を含む'    => ['アイウーエオ'],
            '中点を含む'     => ['ア・イ'],
            'ヴ'             => ['ヴ'],
            'ヵヶ'           => ['ヵヶ'],
            '長文'           => ['アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン'],
        ];
    }

    #[Test]
    #[DataProvider('isKatakana_falseProvider')]
    public function isKatakana_全角カタカナ以外を含むはfalse(string $input): void
    {
        $this->assertFalse(CharTypeChecker::isKatakana($input));
    }

    public static function isKatakana_falseProvider(): array
    {
        return [
            'ひらがな混在'    => ['アイウあいう'],
            '漢字混在'        => ['アイウ漢字'],
            'ASCII混在'       => ['アイウabc'],
            '半角カナ混在'    => ['アイｱｲ'],
            'ひらがなのみ'    => ['あいうえお'],
            '漢字のみ'        => ['日本語'],
            'ASCIIのみ'       => ['abc'],
            '半角カナのみ'    => ['ｱｲｳ'],
            'スペース混在'    => ['ア イ'],
        ];
    }

    // =========================================================================
    // hasHiragana
    // =========================================================================

    #[Test]
    public function hasHiragana_空文字はfalse(): void
    {
        $this->assertFalse(CharTypeChecker::hasHiragana(''));
    }

    #[Test]
    #[DataProvider('hasHiragana_trueProvider')]
    public function hasHiragana_ひらがなを含む場合はtrue(string $input): void
    {
        $this->assertTrue(CharTypeChecker::hasHiragana($input));
    }

    public static function hasHiragana_trueProvider(): array
    {
        return [
            'ひらがなのみ'    => ['あいうえお'],
            'ひらがな+漢字'   => ['日本語のテスト'],
            'ひらがな+カタカナ' => ['あいうアイウ'],
            'ひらがな+ASCII'  => ['abc あいう'],
            '1文字'           => ['あ'],
        ];
    }

    #[Test]
    #[DataProvider('hasHiragana_falseProvider')]
    public function hasHiragana_ひらがなを含まない場合はfalse(string $input): void
    {
        $this->assertFalse(CharTypeChecker::hasHiragana($input));
    }

    public static function hasHiragana_falseProvider(): array
    {
        return [
            'カタカナのみ'    => ['アイウエオ'],
            '漢字のみ'        => ['日本語'],
            'ASCIIのみ'       => ['abc'],
            '半角カナのみ'    => ['ｱｲｳ'],
            '数字のみ'        => ['123'],
        ];
    }

    // =========================================================================
    // hasKatakana
    // =========================================================================

    #[Test]
    public function hasKatakana_空文字はfalse(): void
    {
        $this->assertFalse(CharTypeChecker::hasKatakana(''));
    }

    #[Test]
    #[DataProvider('hasKatakana_trueProvider')]
    public function hasKatakana_全角カタカナを含む場合はtrue(string $input): void
    {
        $this->assertTrue(CharTypeChecker::hasKatakana($input));
    }

    public static function hasKatakana_trueProvider(): array
    {
        return [
            'カタカナのみ'      => ['アイウエオ'],
            'カタカナ+漢字'     => ['日本語テスト'],
            'カタカナ+ひらがな' => ['あいうアイウ'],
            'カタカナ+ASCII'    => ['ABCアイウ'],
            '長音符'            => ['ガイドー'],
            '1文字'             => ['ア'],
        ];
    }

    #[Test]
    #[DataProvider('hasKatakana_falseProvider')]
    public function hasKatakana_全角カタカナを含まない場合はfalse(string $input): void
    {
        $this->assertFalse(CharTypeChecker::hasKatakana($input));
    }

    public static function hasKatakana_falseProvider(): array
    {
        return [
            'ひらがなのみ'    => ['あいうえお'],
            '漢字のみ'        => ['日本語'],
            'ASCIIのみ'       => ['abc'],
            '半角カナのみ'    => ['ｱｲｳ'],  // 半角カナは対象外
            '数字のみ'        => ['123'],
        ];
    }

    // =========================================================================
    // hasKanji
    // =========================================================================

    #[Test]
    public function hasKanji_空文字はfalse(): void
    {
        $this->assertFalse(CharTypeChecker::hasKanji(''));
    }

    #[Test]
    #[DataProvider('hasKanji_trueProvider')]
    public function hasKanji_漢字を含む場合はtrue(string $input): void
    {
        $this->assertTrue(CharTypeChecker::hasKanji($input));
    }

    public static function hasKanji_trueProvider(): array
    {
        return [
            '漢字のみ'         => ['日本語'],
            '漢字+ひらがな'    => ['日本語のテスト'],
            '漢字+カタカナ'    => ['日本テスト'],
            '漢字+ASCII'       => ['日本abc'],
            '1文字'            => ['日'],
            '異体字（はしご高）' => ['髙橋'],
            '異体字（たつさき）' => ['﨑山'],
            'CJK互換漢字'      => ['㐂'],  // U+3402
        ];
    }

    #[Test]
    #[DataProvider('hasKanji_falseProvider')]
    public function hasKanji_漢字を含まない場合はfalse(string $input): void
    {
        $this->assertFalse(CharTypeChecker::hasKanji($input));
    }

    public static function hasKanji_falseProvider(): array
    {
        return [
            'ひらがなのみ'  => ['あいうえお'],
            'カタカナのみ'  => ['アイウエオ'],
            'ASCIIのみ'     => ['abc'],
            '半角カナのみ'  => ['ｱｲｳ'],
            '数字のみ'      => ['123'],
        ];
    }

    // =========================================================================
    // hasJapanese
    // =========================================================================

    #[Test]
    public function hasJapanese_空文字はfalse(): void
    {
        $this->assertFalse(CharTypeChecker::hasJapanese(''));
    }

    #[Test]
    #[DataProvider('hasJapanese_trueProvider')]
    public function hasJapanese_日本語文字を含む場合はtrue(string $input): void
    {
        $this->assertTrue(CharTypeChecker::hasJapanese($input));
    }

    public static function hasJapanese_trueProvider(): array
    {
        return [
            'ひらがな'          => ['あいうえお'],
            'カタカナ'          => ['アイウエオ'],
            '漢字'              => ['日本語'],
            'ひらがな+ASCII'    => ['あいうabc'],
            'カタカナ+ASCII'    => ['アイウabc'],
            '漢字+ASCII'        => ['日本語abc'],
            '混在'              => ['日本語のテストABC'],
        ];
    }

    #[Test]
    #[DataProvider('hasJapanese_falseProvider')]
    public function hasJapanese_日本語文字を含まない場合はfalse(string $input): void
    {
        $this->assertFalse(CharTypeChecker::hasJapanese($input));
    }

    public static function hasJapanese_falseProvider(): array
    {
        return [
            'ASCIIのみ'    => ['abc'],
            '数字のみ'     => ['123'],
            '半角カナのみ' => ['ｱｲｳ'],  // 半角カナは判定対象外
            '記号のみ'     => ['!@#$%'],
            '空白のみ'     => ['   '],
        ];
    }

    // =========================================================================
    // 実用シナリオ: フリガナバリデーション
    // =========================================================================

    #[Test]
    public function フリガナ入力の全角カタカナ判定(): void
    {
        // 正常: 全角カタカナ（長音符込み）
        $this->assertTrue(CharTypeChecker::isKatakana('ヤマダタロウ'));
        $this->assertTrue(CharTypeChecker::isKatakana('スズキイチロー'));

        // 異常: ひらがな混入
        $this->assertFalse(CharTypeChecker::isKatakana('やまだたろう'));

        // 異常: 半角カナ混入
        $this->assertFalse(CharTypeChecker::isKatakana('ﾔﾏﾀﾞ'));
    }

    #[Test]
    public function フリガナ入力のひらがな判定(): void
    {
        // 正常: ひらがな（長音符込み）
        $this->assertTrue(CharTypeChecker::isHiragana('やまだたろう'));
        $this->assertTrue(CharTypeChecker::isHiragana('すずきいちろー'));

        // 異常: カタカナ混入
        $this->assertFalse(CharTypeChecker::isHiragana('ヤマダタロウ'));
    }

    #[Test]
    public function 日本語が含まれるか確認(): void
    {
        // 日本語を含む
        $this->assertTrue(CharTypeChecker::hasJapanese('山田太郎'));
        $this->assertTrue(CharTypeChecker::hasJapanese('テスト123'));

        // 日本語を含まない（英数字のみ）
        $this->assertFalse(CharTypeChecker::hasJapanese('Yamada Taro'));
        $this->assertFalse(CharTypeChecker::hasJapanese('12345'));
    }
}
