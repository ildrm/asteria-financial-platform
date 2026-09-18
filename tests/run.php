<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Asteria\FinancialPlatform\Application\MarketData\GetQuote;
use Asteria\FinancialPlatform\Application\MarketData\DemoHistoricalSeries;
use Asteria\FinancialPlatform\Domain\Analytics\BlackScholesCalculator;
use Asteria\FinancialPlatform\Domain\Analytics\FixedCouponBondCalculator;
use Asteria\FinancialPlatform\Domain\Analytics\FxForwardCalculator;
use Asteria\FinancialPlatform\Domain\Analytics\OptionType;
use Asteria\FinancialPlatform\Domain\Analytics\PortfolioAnalytics;
use Asteria\FinancialPlatform\Domain\Exception\DomainException;
use Asteria\FinancialPlatform\Domain\Exception\InstrumentNotFound;
use Asteria\FinancialPlatform\Domain\MarketData\DataQuality;
use Asteria\FinancialPlatform\Domain\MarketData\Quote;
use Asteria\FinancialPlatform\Infrastructure\Providers\ProviderRegistry;
use Asteria\FinancialPlatform\Providers\Demo\DemoMarketDataProvider;
use Asteria\FinancialPlatform\Support\FrozenClock;

$passed = 0;
$failed = 0;

function test(string $name, Closure $body): void
{
    global $passed, $failed;
    try {
        $body();
        ++$passed;
        fwrite(STDOUT, "PASS {$name}\n");
    } catch (Throwable $exception) {
        ++$failed;
        fwrite(STDERR, "FAIL {$name}: {$exception->getMessage()}\n");
    }
}

function assertNear(float $expected, float $actual, float $tolerance, string $message = ''): void
{
    if (abs($expected - $actual) > $tolerance) {
        throw new RuntimeException($message !== '' ? $message : "Expected {$expected}, got {$actual}");
    }
}

function assertTrue(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function assertThrows(string $exceptionClass, Closure $body): void
{
    try {
        $body();
    } catch (Throwable $exception) {
        if ($exception instanceof $exceptionClass) {
            return;
        }
        throw new RuntimeException('Expected ' . $exceptionClass . ', got ' . get_class($exception));
    }
    throw new RuntimeException('Expected exception ' . $exceptionClass . ' was not thrown.');
}

$instant = new DateTimeImmutable('2026-01-15T14:30:00+00:00');
$provider = new DemoMarketDataProvider(new FrozenClock($instant));

test('demo quote is deterministic and fully attributed', function () use ($provider, $instant): void {
    $quote = $provider->quote('astr');
    assertNear(124.20, $quote->last, 1.0e-12);
    assertNear(124.20, $quote->midpoint(), 1.0e-12);
    assertTrue($quote->observedAt == $instant, 'Quote timestamp must come from the injected clock.');
    assertTrue($quote->synthetic && $quote->quality === DataQuality::Indicative, 'Demo provenance flags are required.');
    assertTrue($quote->provider === 'demo' && $quote->licensing !== '', 'Provider and licensing metadata are required.');
});

test('demo provider covers multiple asset classes and bounds search', function () use ($provider): void {
    assertTrue(count($provider->searchInstruments('', 2)) === 2, 'Search limit was not enforced.');
    assertTrue($provider->searchInstruments('gold')[0]->symbol === 'XAUUSD', 'Name search did not resolve the commodity.');
    assertTrue($provider->isEntitled('quote.snapshot', 'BTCUSD'), 'Demo quote entitlement should be discoverable.');
    assertTrue($provider->throttleStatus()->remaining === PHP_INT_MAX, 'Demo throttle status must be explicit.');
});

test('unknown symbols fail explicitly', function () use ($provider): void {
    assertThrows(InstrumentNotFound::class, static fn () => $provider->quote('MISSING'));
});

test('quote invariants reject crossed markets', function () use ($instant): void {
    assertThrows(DomainException::class, static fn () => new Quote('TEST', 'USD', 11.0, 10.0, 10.5, 10.0, 10.0, 12.0, 9.0, 1, 'OPEN', $instant, $instant, 'test', 'test', DataQuality::Good, false, 'test'));
});

test('provider registry and application handler preserve adapter boundary', function () use ($provider): void {
    $registry = new ProviderRegistry();
    $registry->registerMarketData($provider);
    $quote = (new GetQuote($registry))->handle('EURUSD');
    assertNear(1.0873, $quote->last, 1.0e-12);
    assertThrows(InvalidArgumentException::class, static fn () => $registry->registerMarketData($provider));
});

test('par fixed-coupon bond golden case', static function (): void {
    $metrics = (new FixedCouponBondCalculator())->calculate(100.0, 0.05, 0.05, 10.0, 2);
    assertNear(100.0, $metrics->dirtyPrice, 1.0e-9);
    assertNear(0.05, $metrics->currentYield, 1.0e-12);
    assertTrue($metrics->modifiedDuration > 7.0 && $metrics->modifiedDuration < 8.0, 'Modified duration is outside the known range.');
    assertTrue($metrics->convexity > 60.0 && $metrics->dv01 > 0.0, 'Convexity and DV01 must be positive.');
});

test('bond accrued interest separates clean and dirty price', static function (): void {
    $metrics = (new FixedCouponBondCalculator())->calculate(100.0, 0.06, 0.05, 2.0, 2, 0.5);
    assertNear(1.5, $metrics->accruedInterest, 1.0e-12);
    assertNear($metrics->dirtyPrice - 1.5, $metrics->cleanPrice, 1.0e-12);
});

test('Black-Scholes call and put match published golden values', static function (): void {
    $calculator = new BlackScholesCalculator();
    $call = $calculator->calculate(OptionType::Call, 100.0, 100.0, 1.0, 0.05, 0.20);
    $put = $calculator->calculate(OptionType::Put, 100.0, 100.0, 1.0, 0.05, 0.20);
    assertNear(10.4506, $call->price, 0.001);
    assertNear(5.5735, $put->price, 0.001);
    assertNear(0.6368, $call->delta, 0.001);
    assertNear(0.01876, $call->gamma, 0.0001);
});

test('FX forward obeys covered-interest parity', static function (): void {
    $calculator = new FxForwardCalculator();
    $forward = $calculator->outright(1.10, 0.05, 0.03, 1.0);
    assertNear(1.1213592233, $forward, 1.0e-9);
    assertNear($forward - 1.10, $calculator->points(1.10, 0.05, 0.03, 1.0), 1.0e-12);
});

test('local historical series is deterministic and closes at the current quote', function () use ($provider, $instant): void {
    $series = new DemoHistoricalSeries($provider, new FrozenClock($instant));
    $bars = $series->daily('ASTR', 30);
    assertTrue(count($bars) === 30, 'Historical series length must be bounded and exact.');
    assertNear($provider->quote('ASTR')->last, $bars[29]->close, 1.0e-12);
    assertTrue($bars[0]->synthetic && $bars[0]->source === 'asteria-local-history-v1', 'History provenance is required.');
    assertTrue($series->daily('ASTR', 30) == $bars, 'Historical series must be deterministic for a frozen clock.');
});

test('portfolio analytics golden return, volatility, drawdown and tail risk', static function (): void {
    $metrics = (new PortfolioAnalytics())->calculate([0.01, -0.02, 0.015, -0.01, 0.005]);
    assertNear(-0.00042646735, $metrics->totalReturn, 1.0e-9);
    assertTrue($metrics->annualizedVolatility > 0.20 && $metrics->annualizedVolatility < 0.25, 'Annualized volatility is outside the golden range.');
    assertNear(0.02, $metrics->maxDrawdown, 1.0e-12);
    assertNear(0.02, $metrics->historicalVar95, 1.0e-12);
    assertNear(0.02, $metrics->expectedShortfall95, 1.0e-12);
});

test('repository root is a directly installable WordPress plugin', static function (): void {
    $root = dirname(__DIR__);
    foreach (['asteria-financial-platform.php', 'autoload.php', 'uninstall.php', 'readme.txt', 'README.md', 'app', 'resources', 'languages'] as $requiredPath) {
        assertTrue(file_exists($root . '/' . $requiredPath), 'Required plugin-root path is missing: ' . $requiredPath);
    }
    assertTrue(! is_dir($root . '/plugin'), 'A nested plugin directory must not be recreated.');
    $bootstrap = (string) file_get_contents($root . '/asteria-financial-platform.php');
    $directoryReadme = (string) file_get_contents($root . '/readme.txt');
    assertTrue(str_contains($bootstrap, 'Plugin Name: Asteria Financial Platform'), 'WordPress plugin header is missing.');
    assertTrue(str_contains($bootstrap, "Requires PHP: 8.2"), 'WordPress PHP requirement header is missing.');
    assertTrue(str_contains($bootstrap, 'Version:     0.2.0') && str_contains($directoryReadme, 'Stable tag: 0.2.0'), 'Plugin header and stable tag versions must agree.');
});

test('relative documentation links resolve from their source files', static function (): void {
    $root = dirname(__DIR__);
    $files = array_merge([$root . '/README.md', $root . '/CONTRIBUTING.md', $root . '/CHANGELOG.md'], glob($root . '/docs/*.md') ?: []);
    foreach ($files as $file) {
        $contents = (string) file_get_contents($file);
        preg_match_all('/\[[^\]]+\]\((?!https?:|#)([^)#]+)(?:#[^)]*)?\)/', $contents, $matches);
        foreach ($matches[1] as $target) {
            $resolved = dirname($file) . '/' . rawurldecode($target);
            assertTrue(file_exists($resolved), basename($file) . ' links to missing path: ' . $target);
        }
    }
});

test('requirements matrix is machine-readable and status constrained', static function (): void {
    $root = dirname(__DIR__);
    $path = $root . '/docs/requirements/feature-matrix.json';
    $matrix = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $required = ['id', 'domain', 'feature', 'subfeature', 'status', 'implementation_path', 'tests', 'documentation', 'security_review', 'performance_review', 'ux_review'];
    assertTrue(count($matrix['requirements']) >= 60, 'Requirements matrix must retain broad capability coverage.');
    foreach ($matrix['requirements'] as $row) {
        foreach ($required as $field) {
            assertTrue(array_key_exists($field, $row), 'Matrix row is missing field ' . $field);
        }
        assertTrue(in_array($row['status'], $matrix['allowed_statuses'], true), 'Matrix row has an invalid status.');
        assertTrue($row['status'] !== 'VERIFIED', 'Foundation rows cannot be marked VERIFIED without full release evidence.');
        if ($row['status'] !== 'NOT_STARTED') {
            assertTrue($row['implementation_path'] !== [], 'Active matrix rows must cite implementation paths.');
            foreach ($row['implementation_path'] as $implementationPath) {
                assertTrue(! str_starts_with($implementationPath, 'plugin/'), 'Matrix paths must be relative to the current plugin root.');
                assertTrue(file_exists($root . '/' . $implementationPath), 'Missing matrix evidence path: ' . $implementationPath);
            }
        }
        foreach ($row['documentation'] as $documentationPath) {
            assertTrue(file_exists($root . '/' . $documentationPath), 'Missing matrix documentation path: ' . $documentationPath);
        }
    }
});

fwrite(STDOUT, "\n{$passed} passed, {$failed} failed.\n");
exit($failed === 0 ? 0 : 1);
