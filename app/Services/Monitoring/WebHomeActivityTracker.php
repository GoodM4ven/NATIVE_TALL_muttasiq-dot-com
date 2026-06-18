<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WebHomeActivityTracker
{
    public const CONTEXT_HOME = 'home';

    public const CONTEXT_ATHKAR_GATE = 'athkar-app-gate';

    public const CONTEXT_QURAN_GATE = 'quran-app-gate';

    /**
     * @var array<int, string>
     */
    private const CONTEXTS = [
        self::CONTEXT_HOME,
        self::CONTEXT_ATHKAR_GATE,
        self::CONTEXT_QURAN_GATE,
    ];

    public function track(Request $request, string $context = self::CONTEXT_HOME): void
    {
        $context = $this->normalizeContext($context);
        $now = CarbonImmutable::now();
        $cache = $this->cache();

        if ($context === self::CONTEXT_HOME) {
            $this->trackHomeVisit($cache, $request, $now);

            return;
        }

        $this->trackAppVisit($cache, $request, $now, $context);
    }

    private function trackHomeVisit(Repository $cache, Request $request, CarbonImmutable $now): void
    {
        $fingerprint = $this->fingerprint($request);

        $this->incrementWithTtl(
            cache: $cache,
            key: $this->dailyHitsKey($now, self::CONTEXT_HOME),
            ttlSeconds: $this->dailyCounterTtlSeconds($now),
        );
        $this->incrementWithTtl(
            cache: $cache,
            key: $this->hourlyHitsKey($now, self::CONTEXT_HOME),
            ttlSeconds: $this->hourlyCounterTtlSeconds($now),
        );

        $isNewDailyVisitor = $cache->add(
            $this->dailySeenKey($now, $fingerprint, self::CONTEXT_HOME),
            true,
            now()->addDays($this->retentionDays()),
        );

        if ($isNewDailyVisitor) {
            $this->incrementWithTtl(
                cache: $cache,
                key: $this->dailyUniqueKey($now, self::CONTEXT_HOME),
                ttlSeconds: $this->dailyCounterTtlSeconds($now),
            );
        }

        $isNewHourlyVisitor = $cache->add(
            $this->hourlySeenKey($now, $fingerprint, self::CONTEXT_HOME),
            true,
            now()->addDays(2),
        );

        if ($isNewHourlyVisitor) {
            $this->incrementWithTtl(
                cache: $cache,
                key: $this->hourlyUniqueKey($now, self::CONTEXT_HOME),
                ttlSeconds: $this->hourlyCounterTtlSeconds($now),
            );
        }
    }

    /**
     * @return array{labels: array<int, string>, hits: array<int, int>, unique_visitors: array<int, int>}
     */
    public function dailySeries(int $days, string $context = self::CONTEXT_HOME): array
    {
        $context = $this->normalizeContext($context);
        $days = max(1, $days);
        $endDate = CarbonImmutable::today();
        $cache = $this->cache();

        $labels = [];
        $hits = [];
        $uniqueVisitors = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = $endDate->subDays($offset);
            $labels[] = $date->format('M d');
            $hits[] = (int) $cache->get($this->dailyHitsKey($date, $context), 0);
            $uniqueVisitors[] = (int) $cache->get($this->dailyUniqueKey($date, $context), 0);
        }

        return [
            'labels' => $labels,
            'hits' => $hits,
            'unique_visitors' => $uniqueVisitors,
        ];
    }

    /**
     * @return array{hits: int, unique_visitors: int}
     */
    public function todaySummary(string $context = self::CONTEXT_HOME): array
    {
        $context = $this->normalizeContext($context);
        $today = CarbonImmutable::today();
        $cache = $this->cache();

        return [
            'hits' => (int) $cache->get($this->dailyHitsKey($today, $context), 0),
            'unique_visitors' => (int) $cache->get($this->dailyUniqueKey($today, $context), 0),
        ];
    }

    /**
     * @return array{hits: int, unique_visitors: int}
     */
    public function last24HoursSummary(string $context = self::CONTEXT_HOME): array
    {
        $context = $this->normalizeContext($context);
        $currentHour = CarbonImmutable::now()->startOfHour();
        $hits = 0;
        $uniqueVisitors = 0;
        $cache = $this->cache();

        for ($offset = 0; $offset < 24; $offset++) {
            $hour = $currentHour->subHours($offset);
            $hits += (int) $cache->get($this->hourlyHitsKey($hour, $context), 0);
            $uniqueVisitors += (int) $cache->get($this->hourlyUniqueKey($hour, $context), 0);
        }

        return [
            'hits' => $hits,
            'unique_visitors' => $uniqueVisitors,
        ];
    }

    public function chartDays(): int
    {
        return max(7, (int) config('app.custom.security.web_home_metrics.chart_days', 14));
    }

    private function trackAppVisit(Repository $cache, Request $request, CarbonImmutable $now, string $context): void
    {
        $fingerprint = $this->fingerprint($request);

        $this->incrementWithTtl(
            cache: $cache,
            key: $this->dailyHitsKey($now, $context),
            ttlSeconds: $this->dailyCounterTtlSeconds($now),
        );
        $this->incrementWithTtl(
            cache: $cache,
            key: $this->hourlyHitsKey($now, $context),
            ttlSeconds: $this->hourlyCounterTtlSeconds($now),
        );

        $isNewDailyVisitor = $cache->add(
            $this->dailySeenKey($now, $fingerprint, $context),
            true,
            now()->addDays($this->retentionDays()),
        );

        if ($isNewDailyVisitor) {
            $this->incrementWithTtl(
                cache: $cache,
                key: $this->dailyUniqueKey($now, $context),
                ttlSeconds: $this->dailyCounterTtlSeconds($now),
            );
        }

        $isNewHourlyVisitor = $cache->add(
            $this->hourlySeenKey($now, $fingerprint, $context),
            true,
            now()->addDays(2),
        );

        if ($isNewHourlyVisitor) {
            $this->incrementWithTtl(
                cache: $cache,
                key: $this->hourlyUniqueKey($now, $context),
                ttlSeconds: $this->hourlyCounterTtlSeconds($now),
            );
        }
    }

    private function retentionDays(): int
    {
        return max(2, (int) config('app.custom.security.web_home_metrics.retention_days', 35));
    }

    private function dailyCounterTtlSeconds(CarbonImmutable $timestamp): int
    {
        return max(60, (int) $timestamp->endOfDay()->addDays($this->retentionDays())->diffInSeconds($timestamp, true));
    }

    private function hourlyCounterTtlSeconds(CarbonImmutable $timestamp): int
    {
        return max(60, (int) $timestamp->endOfHour()->addDays(2)->diffInSeconds($timestamp, true));
    }

    private function cache(): Repository
    {
        $store = config('app.custom.security.web_home_metrics.cache_store', 'database');
        if (! is_string($store) || $store === '') {
            return Cache::store();
        }

        return Cache::store($store);
    }

    private function incrementWithTtl(Repository $cache, string $key, int $ttlSeconds): int
    {
        $cache->add($key, 0, $ttlSeconds);

        return (int) $cache->increment($key);
    }

    private function fingerprint(Request $request): string
    {
        $ipAddress = trim((string) $request->ip());
        $userAgent = Str::limit((string) $request->userAgent(), 255, '');

        return hash('sha256', $ipAddress.'|'.$userAgent);
    }

    private function dailyHitsKey(CarbonImmutable $date, string $context): string
    {
        return $this->metricPrefix($context).':daily:hits:'.$date->format('Ymd');
    }

    private function dailyUniqueKey(CarbonImmutable $date, string $context): string
    {
        return $this->metricPrefix($context).':daily:unique:'.$date->format('Ymd');
    }

    private function dailySeenKey(CarbonImmutable $date, string $fingerprint, string $context): string
    {
        return $this->metricPrefix($context).':daily:seen:'.$date->format('Ymd').':'.$fingerprint;
    }

    private function hourlyHitsKey(CarbonImmutable $date, string $context): string
    {
        return $this->metricPrefix($context).':hourly:hits:'.$date->format('YmdH');
    }

    private function hourlyUniqueKey(CarbonImmutable $date, string $context): string
    {
        return $this->metricPrefix($context).':hourly:unique:'.$date->format('YmdH');
    }

    private function hourlySeenKey(CarbonImmutable $date, string $fingerprint, string $context): string
    {
        return $this->metricPrefix($context).':hourly:seen:'.$date->format('YmdH').':'.$fingerprint;
    }

    private function normalizeContext(string $context): string
    {
        return in_array($context, self::CONTEXTS, true)
            ? $context
            : self::CONTEXT_HOME;
    }

    private function metricPrefix(string $context): string
    {
        $context = $this->normalizeContext($context);

        if ($context === self::CONTEXT_HOME) {
            return 'metrics:web-home';
        }

        return 'metrics:web-home:view:'.$context;
    }
}
