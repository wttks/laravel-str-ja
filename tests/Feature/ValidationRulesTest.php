<?php

namespace Wttks\StrJa\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Wttks\StrJa\StrJaServiceProvider;
use Wttks\StrJa\SjisConverter;
use Wttks\StrJa\EucConverter;

/**
 * カスタムバリデーションルールのテスト
 *
 * Laravel の Validator を実際に動かして検証する。
 */
class ValidationRulesTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [StrJaServiceProvider::class];
    }

    private function passes(string $rule, mixed $value): bool
    {
        return Validator::make(['field' => $value], ['field' => $rule])->passes();
    }

    private function fails(string $rule, mixed $value): bool
    {
        return Validator::make(['field' => $value], ['field' => $rule])->fails();
    }

    // =========================================================================
    // is_hiragana
    // =========================================================================

    #[Test]
    public function is_hiragana_ひらがなのみは通過(): void
    {
        $this->assertTrue($this->passes('is_hiragana', 'あいうえお'));
        $this->assertTrue($this->passes('is_hiragana', 'やまだたろう'));
        $this->assertTrue($this->passes('is_hiragana', 'すずきいちろー')); // 長音符
    }

    #[Test]
    public function is_hiragana_ひらがな以外は失敗(): void
    {
        $this->assertTrue($this->fails('is_hiragana', 'アイウエオ'));
        $this->assertTrue($this->fails('is_hiragana', '日本語'));
        $this->assertTrue($this->fails('is_hiragana', 'abc'));
        $this->assertTrue($this->fails('is_hiragana', 'あいうABC'));
        // 空文字列は required なしではルールをスキップするため別途 required と組み合わせる
    }

    // =========================================================================
    // has_hiragana
    // =========================================================================

    #[Test]
    public function has_hiragana_ひらがなを含む場合は通過(): void
    {
        $this->assertTrue($this->passes('has_hiragana', 'あいうえお'));
        $this->assertTrue($this->passes('has_hiragana', '日本語のテスト'));
        $this->assertTrue($this->passes('has_hiragana', 'ABCあいう'));
    }

    #[Test]
    public function has_hiragana_ひらがなを含まない場合は失敗(): void
    {
        $this->assertTrue($this->fails('has_hiragana', 'アイウエオ'));
        $this->assertTrue($this->fails('has_hiragana', 'ABC123'));
    }

    // =========================================================================
    // is_katakana
    // =========================================================================

    #[Test]
    public function is_katakana_全角カタカナのみは通過(): void
    {
        $this->assertTrue($this->passes('is_katakana', 'アイウエオ'));
        $this->assertTrue($this->passes('is_katakana', 'ヤマダタロウ'));
        $this->assertTrue($this->passes('is_katakana', 'スズキイチロー')); // 長音符
    }

    #[Test]
    public function is_katakana_全角カタカナ以外は失敗(): void
    {
        $this->assertTrue($this->fails('is_katakana', 'あいうえお'));
        $this->assertTrue($this->fails('is_katakana', '日本語'));
        $this->assertTrue($this->fails('is_katakana', 'abc'));
        $this->assertTrue($this->fails('is_katakana', 'ｱｲｳ')); // 半角カナ
    }

    // =========================================================================
    // has_katakana
    // =========================================================================

    #[Test]
    public function has_katakana_全角カタカナを含む場合は通過(): void
    {
        $this->assertTrue($this->passes('has_katakana', 'アイウエオ'));
        $this->assertTrue($this->passes('has_katakana', 'テストABC'));
        $this->assertTrue($this->passes('has_katakana', '日本語テスト'));
    }

    #[Test]
    public function has_katakana_全角カタカナを含まない場合は失敗(): void
    {
        $this->assertTrue($this->fails('has_katakana', 'あいうえお'));
        $this->assertTrue($this->fails('has_katakana', 'ABC123'));
        $this->assertTrue($this->fails('has_katakana', 'ｱｲｳ')); // 半角カナは対象外
    }

    // =========================================================================
    // has_japanese
    // =========================================================================

    #[Test]
    public function has_japanese_日本語文字を含む場合は通過(): void
    {
        $this->assertTrue($this->passes('has_japanese', 'あいうえお'));
        $this->assertTrue($this->passes('has_japanese', 'アイウエオ'));
        $this->assertTrue($this->passes('has_japanese', '日本語'));
        $this->assertTrue($this->passes('has_japanese', 'ABCテスト'));
    }

    #[Test]
    public function has_japanese_日本語文字を含まない場合は失敗(): void
    {
        $this->assertTrue($this->fails('has_japanese', 'ABC123'));
        $this->assertTrue($this->fails('has_japanese', 'Hello World'));
    }

    // =========================================================================
    // has_kanji
    // =========================================================================

    #[Test]
    public function has_kanji_漢字を含む場合は通過(): void
    {
        $this->assertTrue($this->passes('has_kanji', '日本語'));
        $this->assertTrue($this->passes('has_kanji', '山田太郎'));
        $this->assertTrue($this->passes('has_kanji', 'ABC日本'));
    }

    #[Test]
    public function has_kanji_漢字を含まない場合は失敗(): void
    {
        $this->assertTrue($this->fails('has_kanji', 'あいうえお'));
        $this->assertTrue($this->fails('has_kanji', 'アイウエオ'));
        $this->assertTrue($this->fails('has_kanji', 'ABC123'));
    }

    // =========================================================================
    // is_utf8
    // =========================================================================

    #[Test]
    public function is_utf8_UTF8文字列は通過(): void
    {
        $this->assertTrue($this->passes('is_utf8', '日本語テスト'));
        $this->assertTrue($this->passes('is_utf8', 'Hello World'));
        $this->assertTrue($this->passes('is_utf8', ''));
    }

    #[Test]
    public function is_utf8_SJISバイナリは失敗(): void
    {
        $sjis = SjisConverter::toSjis('日本語');
        $this->assertTrue($this->fails('is_utf8', $sjis));
    }

    #[Test]
    public function is_utf8_EUCバイナリは失敗(): void
    {
        $euc = EucConverter::toEuc('日本語');
        $this->assertTrue($this->fails('is_utf8', $euc));
    }

    // =========================================================================
    // is_sjis
    // =========================================================================

    #[Test]
    public function is_sjis_SJISバイナリは通過(): void
    {
        $sjis = SjisConverter::toSjis('日本語テスト');
        $this->assertTrue($this->passes('is_sjis', $sjis));
    }

    #[Test]
    public function is_sjis_UTF8日本語は失敗(): void
    {
        $this->assertTrue($this->fails('is_sjis', '日本語'));
    }

    // =========================================================================
    // is_euc
    // =========================================================================

    #[Test]
    public function is_euc_EUCバイナリは通過(): void
    {
        $euc = EucConverter::toEuc('日本語テスト');
        $this->assertTrue($this->passes('is_euc', $euc));
    }

    #[Test]
    public function is_euc_UTF8日本語は失敗(): void
    {
        $this->assertTrue($this->fails('is_euc', '日本語'));
    }

    // =========================================================================
    // no_unsafe_chars
    // =========================================================================

    #[Test]
    public function no_unsafe_chars_通常文字列は通過(): void
    {
        $this->assertTrue($this->passes('no_unsafe_chars', '日本語テスト'));
        $this->assertTrue($this->passes('no_unsafe_chars', "タブ\t改行\n含む"));
        $this->assertTrue($this->passes('no_unsafe_chars', ''));
    }

    #[Test]
    public function no_unsafe_chars_制御文字を含む場合は失敗(): void
    {
        $this->assertTrue($this->fails('no_unsafe_chars', "Hel\x00lo"));      // NULL
        $this->assertTrue($this->fails('no_unsafe_chars', "\u{FEFF}Hello"));  // BOM
        $this->assertTrue($this->fails('no_unsafe_chars', "日本語\u{200B}テスト")); // ゼロ幅スペース
    }

    // =========================================================================
    // エラーメッセージ確認
    // =========================================================================

    #[Test]
    public function エラーメッセージにattributeが含まれる(): void
    {
        $validator = Validator::make(
            ['furigana' => '山田太郎'],
            ['furigana' => 'is_katakana']
        );

        $this->assertTrue($validator->fails());
        $message = $validator->errors()->first('furigana');
        $this->assertStringContainsString('furigana', $message);
    }
}
