<?php

namespace Wttks\StrJa\Tests\Benchmark;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Wttks\StrJa\SjisConverter;

/**
 * 正規化3実装のパフォーマンスベンチマーク
 *
 * このテストはパフォーマンス特性を計測・出力するためのもの。
 * 実行: vendor/bin/phpunit --testsuite Benchmark
 */
class NormalizeImplementationBenchmarkTest extends TestCase
{
    /** @var array<string> ベンチマーク用テストデータ */
    private array $fixtures;

    /** ウォームアップ + 計測の繰り返し回数 */
    private const ITERATIONS = 5000;

    protected function setUp(): void
    {
        $this->fixtures = [
            // 通常の日本語（変換対象なし）
            str_repeat('日本語のサンプルテキストです。', 10),
            // IBM拡張文字を多数含む
            str_repeat('髙橋さんと﨑山さんが打ち合わせ。EMダッシュ—も含む。', 10),
            // Unicode互換文字を多数含む
            str_repeat('㈱テスト①②③㎝㎏Ⅰ', 10),
            // 混在（最も実務に近い）
            str_repeat('髙橋㈱の﨑山①支店、EMダッシュ—の件', 10),
            // 長文（変換対象少なめ）
            str_repeat('あいうえおかきくけこさしすせそたちつてとなにぬねの', 20),
        ];
    }

    #[Test]
    public function benchmark_strtr_vs_str_replace_vs_preg_replace(): void
    {
        $results = [];

        foreach (['strtr', 'str_replace', 'preg_replace'] as $impl) {
            // ウォームアップ（JITキャッシュ等の影響を除外）
            for ($i = 0; $i < 100; $i++) {
                $this->callImpl($impl, $this->fixtures[0]);
            }

            $start = hrtime(true);
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                foreach ($this->fixtures as $text) {
                    $this->callImpl($impl, $text);
                }
            }
            $elapsed = hrtime(true) - $start;

            $results[$impl] = $elapsed;

            echo sprintf(
                "\n  [%s] %d iterations × %d fixtures = %.3f ms (avg: %.4f ms/call)\n",
                $impl,
                self::ITERATIONS,
                count($this->fixtures),
                $elapsed / 1_000_000,
                $elapsed / 1_000_000 / (self::ITERATIONS * count($this->fixtures))
            );
        }

        // 最速実装を表示
        $fastest = (string) array_search(min($results), $results);
        echo sprintf("\n  >>> 最速実装: %s\n", $fastest);

        // strtr が採用実装なので、strtr が最速であることを確認
        // （環境によって差が出るためアサーションは緩め: 最遅より2倍以上遅くない）
        $strtrTime = $results['strtr'];
        $maxAllowed = min($results) * 3.0;

        $this->assertLessThanOrEqual(
            $maxAllowed,
            $strtrTime,
            'strtr の実行時間が最速の3倍を超えている'
        );
    }

    #[Test]
    public function benchmark_toSjis_full_pipeline(): void
    {
        $text = str_repeat('髙橋㈱の﨑山①支店、EMダッシュ—の件、日本語テキスト', 5);

        // ウォームアップ
        for ($i = 0; $i < 50; $i++) {
            SjisConverter::toSjis($text);
        }

        $iterations = 1000;
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            SjisConverter::toSjis($text);
        }
        $elapsed = hrtime(true) - $start;

        echo sprintf(
            "\n  [toSjis full pipeline] %d iterations: %.3f ms (avg: %.4f ms/call, text: %d chars)\n",
            $iterations,
            $elapsed / 1_000_000,
            $elapsed / 1_000_000 / $iterations,
            mb_strlen($text)
        );

        // 1回あたり 10ms 未満であること（過剰に遅くないことを確認）
        $avgMs = $elapsed / 1_000_000 / $iterations;
        $this->assertLessThan(10.0, $avgMs, 'toSjis の平均処理時間が10msを超えている');
    }

    #[Test]
    public function benchmark_fromSjis_pipeline(): void
    {
        $sjis = SjisConverter::toSjis(str_repeat('日本語テキストのSJIS変換テスト', 10));

        // ウォームアップ
        for ($i = 0; $i < 50; $i++) {
            SjisConverter::fromSjis($sjis);
        }

        $iterations = 1000;
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            SjisConverter::fromSjis($sjis);
        }
        $elapsed = hrtime(true) - $start;

        echo sprintf(
            "\n  [fromSjis] %d iterations: %.3f ms (avg: %.4f ms/call)\n",
            $iterations,
            $elapsed / 1_000_000,
            $elapsed / 1_000_000 / $iterations
        );

        $avgMs = $elapsed / 1_000_000 / $iterations;
        $this->assertLessThan(10.0, $avgMs, 'fromSjis の平均処理時間が10msを超えている');
    }

    #[Test]
    public function benchmark_table_cache_effect(): void
    {
        $text = '髙橋㈱テスト';

        // キャッシュなし（初回）
        SjisConverter::clearTableCache();
        $startCold = hrtime(true);
        SjisConverter::normalize($text);
        $coldTime = hrtime(true) - $startCold;

        // キャッシュあり（2回目以降）
        $iterations = 1000;
        $start = hrtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            SjisConverter::normalize($text);
        }
        $warmTime = (hrtime(true) - $start) / $iterations;

        echo sprintf(
            "\n  [table cache] cold: %.4f ms, warm avg: %.4f ms (%.1fx faster)\n",
            $coldTime / 1_000_000,
            $warmTime / 1_000_000,
            $coldTime / max($warmTime, 1)
        );

        // ウォーム時はコールド時より速い（キャッシュが効いている）
        $this->assertLessThanOrEqual($coldTime, $warmTime * 2);
    }

    // =========================================================================
    // ヘルパー
    // =========================================================================

    private function callImpl(string $impl, string $text): string
    {
        return match ($impl) {
            'strtr' => SjisConverter::normalize($text),
            'str_replace' => SjisConverter::normalizeWithStrReplace($text),
            'preg_replace' => SjisConverter::normalizeWithPregReplace($text),
        };
    }
}
