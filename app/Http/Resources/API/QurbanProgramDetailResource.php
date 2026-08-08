<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QurbanProgramDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->name,
            'description'  => $this->description,
            'status'       => $this->status,
            'year'         => $this->year,
            'period_start' => $this->period_start_at?->toISOString(),
            'period_end'   => $this->period_end_at?->toISOString(),
            'is_public'    => $this->is_public,
            'organization' => $this->when(
                $this->relationLoaded('organization'),
                fn () => [
                    'id'   => $this->organization->id,
                    'slug' => $this->organization->slug,
                    'name' => $this->organization->name,
                    'logo' => $this->organization->profile?->logo_url,
                ],
            ),
            'packages' => $this->when(
                $this->relationLoaded('packages'),
                fn () => $this->packages
                    ->where('is_active', true)
                    ->values()
                    ->map(fn ($pkg) => [
                        'id'               => $pkg->id,
                        'title'            => $pkg->title,
                        'description'      => $pkg->description,
                        'animal_type'      => $pkg->animal_type,
                        'package_type'     => $pkg->package_type,
                        'share_count'      => $pkg->share_count,
                        'price'            => (int) $pkg->price,
                        'quota'            => $pkg->quota,
                        'remaining_quota'  => $pkg->remaining_quota,
                        'target_weight_min' => $pkg->target_weight_min,
                        'target_weight_max' => $pkg->target_weight_max,
                        'is_available'     => $pkg->remaining_quota > 0,
                    ]),
            ),
            'distribution_batches_count' => $this->when(
                $this->relationLoaded('distributionBatches'),
                fn () => $this->distributionBatches->count(),
            ),
        ];
    }
}
