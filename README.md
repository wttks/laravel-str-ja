# wttks/laravel-str-ja

Laravel `Str` クラスの日本語拡張パッケージ。

- UTF-8 ↔ SJIS-win（CP932）変換
- IBM拡張文字・NTT拡張文字・異体字の正規化（`髙` → `高`、`﨑` → `崎` 等）
- Unicode互換等価の正規化（`㈱` → `(株)`、`①` → `1`、`㎝` → `cm` 等）

## インストール

```bash
composer require wttks/laravel-str-ja
```

Laravel 11以降はパッケージ自動検出により、ServiceProviderは自動登録されます。

## 使い方

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
```

または `SjisConverter` を直接使用:

```php
use Wttks\StrJa\SjisConverter;

$sjis = SjisConverter::toSjis($utf8String);
$utf8 = SjisConverter::fromSjis($sjisString);
$normalized = SjisConverter::normalize($utf8String);
```

## 正規化の動作

`toSjis()` および `normalizeForSjis()` は変換前に以下の2ステップを自動適用します：

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

### Step 2: IBM拡張文字・異体字の置換（strtr使用）

| 入力 | 出力 | 説明 |
|---|---|---|
| `髙` | `高` | はしご高（U+9AD9） |
| `﨑` | `崎` | たつさき（U+FA11） |
| `—` | `-` | EMダッシュ |
| `–` | `-` | ENダッシュ |
| `−` | `－` | マイナス記号 |
| `∥` | `‖` | 平行記号 |

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

5,000回×5件のベンチマーク結果（PHP 8.5 / Apple M2相当）:

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
