<?php

if (! function_exists('getAllDatesUntil')) {
    function getAllDatesUntil(string $endDate) {
        $dateFrom = new \DateTime(now());
        $dateTo = new \DateTime("{$endDate}");

        // dd($dateTo);

        if ($dateFrom > $dateTo)
            return [];

        $dates = [];

        while ($dateFrom <= $dateTo) {
            $dates[] = $dateFrom->format('Y-m-d');
            $dateFrom->modify('+1 day');
        }

        return $dates;
    }
}

if (! function_exists('getAllWeekdayDatesUntil')) {
    function getAllWeekdayDatesUntil(int $weekdayNumber, string $endDate) {
        $dateFrom = new \DateTime(now());
        $dateTo = new \DateTime("{$endDate}");

        // dd($dateTo);

        if ($dateFrom > $dateTo)
            return [];

        $weekdayMap = [
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
        ];

        $dates = [];

        // var_dump($dateFrom->format('N'));
        if ($dateFrom->format('N') != $weekdayNumber)
            $dateFrom->modify("next {$weekdayMap[$weekdayNumber]}");

        while ($dateFrom <= $dateTo) {
            $dates[] = $dateFrom->format('Y-m-d');
            $dateFrom->modify('+1 week');
        }

        return $dates;
    }
}

if (! function_exists('roundPrice')) {
    function roundPrice(float $amount): float {
        return floatval(number_format($amount, 2, '.', ''));
    }
}

if (! function_exists('formatPriceAsString')) {
    function formatPriceAsString(?float $amount): string {
        $amount = $amount ?? 0;
        return strval(number_format($amount, 2, '.', ''));
    }
}