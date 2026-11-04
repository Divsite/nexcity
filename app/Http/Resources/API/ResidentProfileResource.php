<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'qr_token'               => $this->qr_token,
            'neighborhood_association' => $this->whenLoaded('neighborhoodAssociation', fn () => [
                'id'   => $this->neighborhoodAssociation->id,
                'name' => $this->neighborhoodAssociation->name,
            ]),
            'citizens_association'   => $this->whenLoaded('citizensAssociation', fn () => [
                'id'   => $this->citizensAssociation->id,
                'name' => $this->citizensAssociation->name,
            ]),
            'organization'           => $this->whenLoaded('organization', fn () => [
                'id'   => $this->organization->id,
                'name' => $this->organization->name,
            ]),
            'is_head_family'         => $this->is_head_family,
            'family_members_count'   => $this->family_members_count,
            'address_line'           => $this->address_line,
            'gender'                 => $this->gender,
            'birth_date'             => $this->birth_date?->toDateString(),
        ];
    }
}
