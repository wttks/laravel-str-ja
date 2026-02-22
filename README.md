# wttks/laravel-str-ja

Laravel `Str` クラスの日本語拡張パッケージ。

- UTF-8 ↔ SJIS-win（CP932）変換
- UTF-8 ↔ eucJP-win 変換
- IBM拡張文字・NTT拡張文字・異体字の正規化（`髙` → `高`、`﨑` → `崎` 等）
- Unicode互換等価の正規化（`㈱` → `(株)`、`①` → `1`、`㎝` → `cm` 等）
- 日本語文字列の正規化（半角カナ → 全角、全角ASCII → 半角、制御文字削除 等）
- ひらがな ↔ カタカナ変換（半角カナ対応）
- 文字種判定（ひらがな・カタカナ・漢字・日本語・フリガナ）
- 文字コード判定（UTF-8 / SJIS-win / eucJP-win / ASCII）
- トラブル文字の判定・削除（制御文字・不可視文字・BOM等）
- 空白の正規化（全角・特殊スペースを半角1つに統一）
- SJIS-win バイト数取得・バイト数指定切り捨て
- 文字幅計算（全角=2、半角=1）
- 空白分割・単語数カウント
- Laravel カスタムバリデーションルール（14種）

## インストール

```bash
composer require wttks/laravel-str-ja
```

Laravel 11以降はパッケージ自動検出により、ServiceProviderは自動登録されます。

翻訳ファイルをアプリに公開する場合:

```bash
php artisan vendor:publish --tag=str-ja-lang
```

## 提供する Str マクロ一覧

### 文字コード変換

| マクロ | 説明 |
|---|---|
| `Str::toSjis($str)` | UTF-8 → SJIS-win（正規化込み） |
| `Str::fromSjis($str)` | SJIS-win → UTF-8 |
| `Str::normalizeForSjis($str)` | SJIS-win変換前の正規化のみ |
| `Str::sjisBytes($str, normalize: false)` | SJIS-win変換後のバイト数 |
| `Str::truncateSjis($str, $maxBytes, normalize: false)` | SJIS-winバイト数指定で末尾を切り捨て |
| `Str::toEuc($str)` | UTF-8 → eucJP-win（正規化込み） |
| `Str::fromEuc($str)` | eucJP-win → UTF-8 |
| `Str::normalizeForEuc($str)` | eucJP-win変換前の正規化のみ |

### 日本語文字列の正規化

| マクロ | 説明 |
|---|---|
| `Str::normalizeJa($str, punctuation: false)` | 日本語文字列の正規化（半角カナ→全角、全角ASCII→半角 等） |
| `Str::squishJa($str)` | トラブル文字削除 + 連続空白を半角スペース1つに正規化 + 前後トリム |

### かな変換

| マクロ | 説明 |
|---|---|
| `Str::toHiragana($str)` | カタカナ・半角カナ → ひらがな |
| `Str::toKatakana($str)` | ひらがな・半角カナ → 全角カタカナ |

### 文字種判定

| マクロ | 説明 |
|---|---|
| `Str::isHiragana($str)` | 全体がひらがなか判定（長音符・中点を許容） |
| `Str::isKatakana($str)` | 全体が全角カタカナか判定（長音符・中点を許容） |
| `Str::hasHiragana($str)` | ひらがなを含むか判定 |
| `Str::hasKatakana($str)` | 全角カタカナを含むか判定 |
| `Str::hasKanji($str)` | 漢字を含むか判定 |
| `Str::hasJapanese($str)` | ひらがな・カタカナ・漢字のいずれかを含むか判定 |
| `Str::isFurigana($str, $mode = 'both')` | フリガナとして有効か判定（空白許容） |

### 文字コード判定

| マクロ | 説明 |
|---|---|
| `Str::detectEncoding($str)` | エンコーディングを判定（`'UTF-8'` / `'SJIS-win'` / `'eucJP-win'` / `'ASCII'` / `false`） |
| `Str::isUtf8($str)` | UTF-8（またはASCIIのみ）か判定 |
| `Str::isSjis($str)` | SJIS-win（またはASCIIのみ）か判定 |
| `Str::isEuc($str)` | eucJP-win（またはASCIIのみ）か判定 |
| `Str::isAscii($str)` | ASCIIのみで構成されているか判定 |

### トラブル文字・空白

| マクロ | 説明 |
|---|---|
| `Str::hasTroubleChars($str)` | トラブル文字（制御文字・不可視文字・BOM等）が含まれるか判定 |
| `Str::removeTroubleChars($str)` | トラブル文字を削除 |
| `Str::squishJa($str)` | トラブル文字削除 + 連続空白の正規化 + 前後トリム |
| `Str::splitWords($str)` | 全角・半角・特殊スペースで単語に分割（配列を返す） |
| `Str::strWidth($str)` | 文字幅を返す（全角=2、半角=1） |

## 使い方

### SJIS-win 変換

```php
use Illuminate\Support\Str;

// UTF-8 → SJIS-win（異体字・互換文字を自動正規化してから変換）
$sjis = Str::toSjis('髙橋㈱の﨑山支店');
// → SJIS-winバイト列（正規化後: 高橋(株)の崎山支店）

// SJIS-win → UTF-8
$utf8 = Str::fromSjis($sjis);
// → '高橋(株)の崎山支店'

// 正規化のみ（文字コード変換なし）
$normalized = Str::normalizeForSjis('髙橋㈱の﨑山支店');
// → '高橋(株)の崎山支店'（UTF-8のまま）

// SJIS-win 変換後のバイト数
Str::sjisBytes('日本語');                   // → 6（1文字2バイト×3）
Str::sjisBytes('㈱', normalize: true);      // → 4（正規化後 (株) = 4バイト）

// バイト数指定で切り捨て（文字の途中で切らない）
Str::truncateSjis('日本語テスト', 6);       // → '日本語'（6バイトまで）
Str::truncateSjis('日本語テスト', 7);       // → '日本語'（7バイト目は次の文字の途中）
```

または `SjisConverter` を直接使用:

```php
use Wttks\StrJa\SjisConverter;

$sjis       = SjisConverter::toSjis($utf8String);
$utf8       = SjisConverter::fromSjis($sjisString);
$normalized = SjisConverter::normalize($utf8String);
$bytes      = SjisConverter::byteLength($utf8String, normalize: false);
$truncated  = SjisConverter::truncateByBytes($utf8String, 20);
```

### eucJP-win 変換

```php
// UTF-8 → eucJP-win
$euc = Str::toEuc('髙橋㈱テスト');

// eucJP-win → UTF-8
$utf8 = Str::fromEuc($euc);

// 正規化のみ
$normalized = Str::normalizeForEuc('髙橋㈱テスト');
```

> **SJIS-win との差異**: `©`（U+00A9）は eucJP-win で変換可能ですが SJIS-win では欠落します。`™` は eucJP-win で `TM` に変換されます。`€` はどちらでも変換不可です。

### 日本語文字列の正規化（normalizeJa）

```php
// 半角カナ → 全角、全角ASCII → 半角、不可視制御文字削除
Str::normalizeJa('ｶﾞｲﾄﾞＡＢＣ１２３');
// → 'ガイドABC123'

// punctuation: true で一般句読点も変換
Str::normalizeJa('"テスト"…続く', punctuation: true);
// → '"テスト"...続く'
```

`normalizeJa` の変換内容（常時）:

| 変換 | 例 |
|---|---|
| 不可視制御文字・BOM 削除 | U+200B, U+FEFF 等 |
| 半角カナ → 全角カナ | `ｶﾞ` → `ガ` |
| 全角ASCII → 半角 | `Ａ` → `A`、`１` → `1`、`！` → `!` |

`punctuation: true` の場合に追加:

| 変換 | 例 |
|---|---|
| 引用符 | `"` `"` → `"`、`'` `'` → `'` |
| ダッシュ類 | `–` `—` `―` → `-` |
| 省略記号 | `…` → `...`、`‥` → `..` |

### 空白の正規化（squishJa）

`Str::squish()` の日本語強化版。トラブル文字（NULLバイト・ゼロ幅スペース・双方向制御文字等）も合わせて除去します。

```php
// 連続した空白（全角・半角・タブ・改行・NBSP等）を半角スペース1つに
Str::squishJa('山田　　太郎');         // → '山田 太郎'
Str::squishJa('  Hello   World  ');    // → 'Hello World'

// 前後の空白もトリム
Str::squishJa('　山田太郎　');         // → '山田太郎'

// トラブル文字も除去（Str::squish() との違い）
Str::squishJa("Hel\x00lo");           // → 'Hello'（NULLバイト削除）
Str::squishJa("日本語\u{200B}テスト"); // → '日本語テスト'（ゼロ幅スペース削除）

// 複合ケース
Str::squishJa("\u{FEFF}山田　　\u{200B}太郎\n\n鈴木");
// → '山田 太郎 鈴木'
```

> **`Str::squish()` との違い**: `Str::squish()` は空白の正規化のみ行います。`squishJa()` はそれに加えてNULLバイト・ゼロ幅スペース・双方向制御文字等のトラブル文字も除去します。

### ひらがな ↔ カタカナ変換

```php
// カタカナ・半角カナ → ひらがな
Str::toHiragana('アイウエオ');   // → 'あいうえお'
Str::toHiragana('ｶﾞｲﾄﾞ');      // → 'がいど'
Str::toHiragana('ヴ');           // → 'ゔ'

// ひらがな・半角カナ → 全角カタカナ
Str::toKatakana('あいうえお');   // → 'アイウエオ'
Str::toKatakana('ｶﾞｲﾄﾞ');      // → 'ガイド'
Str::toKatakana('ゔ');           // → 'ヴ'
```

> **注意**: `toKatakana` はひらがなも変換します。`ｶﾞｲﾄﾞの名前` → `ガイドノ名前`

### 文字種判定

```php
// 全体判定: フリガナバリデーション等で使用
Str::isKatakana('ヤマダタロウ');   // → true（全角カタカナのみ）
Str::isKatakana('ﾔﾏﾀﾞﾀﾛｳ');      // → false（半角カナは不可）
Str::isKatakana('やまだたろう');   // → false（ひらがな不可）

Str::isHiragana('やまだたろう');   // → true（ひらがなのみ）
Str::isHiragana('スズキイチロー'); // → false

// 含む判定: 混在テキストの検査等で使用
Str::hasKanji('日本語テスト');   // → true
Str::hasKanji('テストABC');      // → false
Str::hasHiragana('日本語のABC'); // → true
Str::hasKatakana('アイウのABC'); // → true（半角カナは対象外）
Str::hasJapanese('山田 Taro');   // → true（漢字を含む）
Str::hasJapanese('Yamada Taro'); // → false

// フリガナ判定（空白許容、ひらがな・カタカナどちらかで統一）
Str::isFurigana('ヤマダ タロウ');   // → true
Str::isFurigana('やまだ たろう');   // → true
Str::isFurigana('やまだタロウ');    // → false（混在NG）
Str::isFurigana('山田太郎');        // → false（漢字NG）
Str::isFurigana('やまだ', 'hiragana');  // → true（ひらがなのみ許可）
Str::isFurigana('ヤマダ', 'katakana'); // → true（カタカナのみ許可）
```

> **注意**: `isKatakana` / `hasKatakana` / `hasJapanese` は半角カタカナ（ｱｲｳ等）を対象外とします。半角カナを含む入力は事前に `normalizeJa()` や `toKatakana()` で全角変換してから判定してください。

### 文字コード判定

```php
use Wttks\StrJa\EncodingDetector;

// エンコーディングを判定（ASCII → UTF-8 → SJIS-win → eucJP-win の順で厳密に判定）
Str::detectEncoding('日本語');          // → 'UTF-8'
Str::detectEncoding('Hello');           // → 'ASCII'
Str::detectEncoding(SjisConverter::toSjis('日本語')); // → 'SJIS-win'

// 真偽値で判定（ASCIIのみの文字列は全てのエンコーディングでtrueを返す）
Str::isUtf8('日本語');   // → true
Str::isUtf8('Hello');    // → true（ASCIIはUTF-8互換）
Str::isSjis($sjisBytes); // → true
Str::isEuc($eucBytes);   // → true
Str::isAscii('Hello');   // → true
Str::isAscii('日本語');  // → false
```

### トラブル文字の判定・削除

制御文字・不可視文字・BOMなど、入力値にコピペで混入しやすいトラブルの原因となる文字を検出・除去します。

対象文字:
- ASCII制御文字（NUL〜BS, VT, FF, SO〜US, DEL）※タブ・LF・CRは除外
- ゼロ幅スペース等（U+200B〜U+200F）
- 双方向制御文字（U+202A〜U+202E、U+2066〜U+206F）
- Word Joiner 等（U+2060〜U+2064）
- BOM / Zero Width No-Break Space（U+FEFF）
- Interlinear Annotation 制御文字（U+FFF9〜U+FFFB）

```php
// 判定
Str::hasTroubleChars("Hel\x00lo");           // → true（NULLバイト）
Str::hasTroubleChars("\u{FEFF}Hello");        // → true（BOM）
Str::hasTroubleChars("日本語\u{200B}テスト"); // → true（ゼロ幅スペース）
Str::hasTroubleChars("Hello\tWorld");         // → false（タブは通常テキスト）

// 削除
Str::removeTroubleChars("\u{FEFF}Hello");        // → 'Hello'
Str::removeTroubleChars("日本語\u{200B}テスト"); // → '日本語テスト'
```

### 空白分割・単語数カウント

```php
use Wttks\StrJa\JaNormalizer;

// 全角・半角・特殊スペースで分割
Str::splitWords('Hello World');          // → ['Hello', 'World']
Str::splitWords('山田　太郎　鈴木');      // → ['山田', '太郎', '鈴木']（全角スペース）
Str::splitWords("a\u{00A0}b");           // → ['a', 'b']（NBSP）

// 単語数カウント
JaNormalizer::countWords('Hello World'); // → 2
JaNormalizer::countWords('山田　太郎');  // → 2
JaNormalizer::countWords('');           // → 0

// 文字幅（全角=2、半角=1）
Str::strWidth('日本語');    // → 6
Str::strWidth('Hello');     // → 5
Str::strWidth('日本Hello'); // → 9
```

## Laravel バリデーションルール

`StrJaServiceProvider` の登録により、以下のカスタムルールが使用可能になります。

| ルール | 説明 |
|---|---|
| `is_hiragana` | ひらがなのみ |
| `has_hiragana` | ひらがなを1文字以上含む |
| `is_katakana` | 全角カタカナのみ |
| `has_katakana` | 全角カタカナを1文字以上含む |
| `has_japanese` | ひらがな・カタカナ・漢字のいずれかを含む |
| `has_kanji` | 漢字を1文字以上含む |
| `is_utf8` | UTF-8エンコーディングである |
| `is_sjis` | SJIS-winエンコーディングである |
| `is_euc` | eucJP-winエンコーディングである |
| `no_unsafe_chars` | トラブル文字を含まない |
| `is_furigana` | フリガナとして有効（空白許容） |
| `is_furigana:hiragana` | ひらがなのフリガナ（空白許容） |
| `is_furigana:katakana` | カタカナのフリガナ（空白許容） |
| `word_count:N` | ちょうどN単語 |
| `min_word_count:N` | N単語以上 |
| `max_word_count:N` | N単語以下 |

```php
// FormRequest での使用例
public function rules(): array
{
    return [
        'name'     => ['required', 'no_unsafe_chars'],
        'furigana' => ['required', 'is_furigana:katakana'],
        'bio'      => ['nullable', 'max_word_count:200'],
    ];
}
```

エラーメッセージは `lang/ja/validation.php` / `lang/en/validation.php` に定義されています。
カスタマイズする場合は `vendor:publish` でアプリに公開してください。

## SJIS-win 正規化の動作

`toSjis()` / `normalizeForSjis()` は変換前に以下の2ステップを自動適用します。

### Step 1: Unicode NFKC 正規化

| 入力 | 出力 | 説明 |
|---|---|---|
| `㈱` | `(株)` | 丸囲み株式会社 |
| `㈲` | `(有)` | 丸囲み有限会社 |
| `①` | `1` | 丸付き数字 |
| `㎝` | `cm` | 単位記号 |
| `Ａ` | `A` | 全角英字 |
| `１` | `1` | 全角数字 |
| `～` | `~` | 全角チルダ |

### Step 2: IBM拡張文字・異体字の置換

| 入力 | 出力 | 説明 |
|---|---|---|
| `髙` | `高` | はしご高（U+9AD9） |
| `﨑` | `崎` | たつさき（U+FA11） |
| `—` | `-` | EMダッシュ |
| `–` | `-` | ENダッシュ |
| `−` | `－` | マイナス記号 |

## ラウンドトリップについて

**異体字を含まない文字列**は `toSjis` → `fromSjis` で元の文字列に戻ります。

**異体字・互換文字を含む文字列**は正規化後の文字列で戻ります（元の異体字には戻りません）。

```php
// 通常の日本語 → 完全一致で戻る
SjisConverter::fromSjis(SjisConverter::toSjis('日本語テスト')) === '日本語テスト'; // true

// 異体字あり → 正規化後の文字で戻る
SjisConverter::fromSjis(SjisConverter::toSjis('髙橋')) === '高橋'; // true（'髙橋'ではない）
```

## パフォーマンス

5,000回×5件のベンチマーク結果（PHP 8.5）:

| 実装 | 1回あたり |
|---|---|
| **strtr（採用）** | **0.003ms** |
| str_replace | 0.008ms |
| preg_replace | 0.020ms |

変換テーブルは初回ロード後にメモリキャッシュされます（2回目以降は約64倍高速）。

## テスト

```bash
# 単体テスト
vendor/bin/phpunit --testsuite Unit

# Featureテスト（Laravelバリデーションルール）
vendor/bin/phpunit --testsuite Feature

# ベンチマーク
vendor/bin/phpunit --testsuite Benchmark
```

## ライセンス

MIT
