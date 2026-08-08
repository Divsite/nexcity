<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QurbanProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->name,
            'type'           => 'qurban',
            'status'         => $this->status,
            'year'           => $this->year,
            'period_start'   => $this->period_start_at?->toISOString(),
            'period_end'     => $this->period_end_at?->toISOString(),
            'is_public'      => $this->is_public,
            'organization'   => $this->when(
                $this->relationLoaded('organization'),
                fn () => [
                    'id'   => $this->organization->id,
                    'slug' => $this->organization->slug,
                    'name' => $this->organization->name,
                    'logo' => $this->organization->profile?->logo_url,
                ],
            ),
        ];
    }
}
