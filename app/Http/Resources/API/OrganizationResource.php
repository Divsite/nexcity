<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'logo' => $this->profile?->logo_url,
            'description' => $this->profile?->description,
            'active_programs_count' => $this->when(
                $this->relationLoaded('qurbanPrograms'),
                fn () => $this->qurbanPrograms
                    ->whereIn('status', ['open'])
                    ->count(),
            ),
            'members_count' => $this->when(
                $this->relationLoaded('members'),
                fn () => $this->members->count(),
            ),
        ];
    }
}
