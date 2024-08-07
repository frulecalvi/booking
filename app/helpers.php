<?php

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