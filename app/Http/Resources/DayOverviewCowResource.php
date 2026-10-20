<?php

namespace App\Http\Resources;

use App\Models\Farm\CowDailySummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CowDailySummary
 */
class DayOverviewCowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'animalId' => $this->animal_id,
            'cowNumber' => $this->cow_number,
            'lifeNumber' => $this->life_number,
            'daysInMilk' => $this->days_in_milk,
            'lactationNumber' => $this->lactation_number,
            'milkProduction' => $this->interval_milk_production,
            'bcs' => $this->latest_bcs,
            'bcsAgeDays' => $this->bcsAgeDays(),
            'bcsIsOutdated' => $this->bcsIsOutdated(),
            'feedN1' => $this->feed_total_sum_n1,
            'feedN1Delta' => $this->feedAdjustment(1),
            'feedN3' => $this->feed_total_sum_n3,
            'feedN3Delta' => $this->feedAdjustment(3),
            'fpcm' => $this->fatAndProteinCorrectedMilk(),
            'fat' => $this->lab_fat_percentage,
            'protein' => $this->lab_protein_percentage,
            'lactose' => $this->lab_lactose_percentage,
            'urea' => $this->lab_ureum,
            'cellCount' => $this->lab_cell_number,
            'sampleDate' => $this->lab_sample_date?->toDateString(),
        ];
    }
}
