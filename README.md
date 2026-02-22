# wttks/laravel-str-ja

Laravel `Str` クラスの日本語拡張パッケージ。

- UTF-8 ↔ SJIS-win（CP932）変換
- UTF-8 ↔ eucJP-win 変換
- IBM拡張文字・NTT拡張文字・異体字の正規化（`髙` → `高`、`﨑` → `崎` 等）
- Unicode互換等価の正規化（`㈱` → `(株)`、`①` → `1`、`㎝` → `cm` 等）
- 日本語文字列の正規化（半角カナ → 全角、全角ASCII → 半角、制御文字削除 等）
- ひらがな ↔ カタカナ変換（半角カナ対応）
- SJIS-win 変換後のバイト数取得

## インストール

```bash
composer require wttks/laravel-str-ja
```

Laravel 11以降はパッケージ自動検出により、ServiceProviderは自動登録されます。

## 提供する Str マクロ一覧

| マクロ | 説明 |
|---|---|
| `Str::toSjis($str)` | UTF-8 → SJIS-win（正規化込み） |
| `Str::fromSjis($str)` | SJIS-win → UTF-8 |
| `Str::normalizeForSjis($str)` | SJIS-win変換前の正規化のみ |
| `Str::sjisBytes($str, normalize: false)` | SJIS-win変換後のバイト数 |
| `Str::toEuc($str)` | UTF-8 → eucJP-win（正規化込み） |
| `Str::fromEuc($str)` | eucJP-win → UTF-8 |
| `Str::normalizeForEuc($str)` | eucJP-win変換前の正規化のみ |
| `Str::normalizeJa($str, punctuation: false)` | 日本語文字列の正規化 |
| `Str::toHiragana($str)` | カタカナ・半角カナ → ひらがな |
| `Str::toKatakana($str)` | ひらがな・半角カナ → 全角カタカナ |

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
Str::sjisBytes('日本語');          // → 6（1文字2バイト×3）
Str::sjisBytes('㈱', normalize: true);  // → 4（正規化後 (株) = 4バイト）
```

または `SjisConverter` を直接使用:

```php
use Wttks\StrJa\SjisConverter;

$sjis = SjisConverter::toSjis($utf8String);
$utf8 = SjisConverter::fromSjis($sjisString);
$normalized = SjisConverter::normalize($utf8String);
$bytes = SjisConverter::byteLength($utf8String, normalize: false);
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

# ベンチマーク
vendor/bin/phpunit --testsuite Benchmark
```

## ライセンス

MIT
