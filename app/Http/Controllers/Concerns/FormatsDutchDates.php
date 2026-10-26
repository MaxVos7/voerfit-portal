<?php

namespace App\Http\Controllers\Concerns;

use Carbon\CarbonImmutable;

trait FormatsDutchDates
{
    /**
     * "19 oktober", with the year only when it is not the current year.
     */
    private function dutchLabel(CarbonImmutable $day): string
    {
        $format = $day->isCurrentYear() ? 'j F' : 'j F Y';

        return $day->settings(['locale' => 'nl'])->translatedFormat($format);
    }
}
