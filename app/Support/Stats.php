<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class Stats
{
    /**
     * A gap-free daily series for the last $days days, ready for a chart.
     *
     * @param  iterable<string,int>  $perDay  map of 'Y-m-d' => count (days without rows may be missing)
     * @return list<array{date:string,label:string,count:int}>
     */
    public static function dailySeries(iterable $perDay, int $days = 30): array
    {
        $counts = [];
        foreach ($perDay as $date => $count) {
            $counts[(string) $date] = (int) $count;
        }

        $series = [];
        $start = CarbonImmutable::now()->startOfDay()->subDays($days - 1);
        for ($i = 0; $i < $days; $i++) {
            $d = $start->addDays($i);
            $series[] = ['date' => $d->toDateString(), 'label' => $d->format('M j'), 'count' => $counts[$d->toDateString()] ?? 0];
        }

        return $series;
    }
}
