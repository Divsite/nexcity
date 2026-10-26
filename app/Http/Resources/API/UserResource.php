<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->loadMissing('residentProfile.neighborhoodAssociation', 'residentProfile.citizensAssociation', 'residentProfile.organization');

        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'username' => $this->username,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'avatar'   => $this->avatar
                ? asset('uploads/avatar/' . $this->avatar)
                : null,
            'roles'            => $this->getRoleNames(),
            'resident_profile' => $this->when(
                $this->residentProfile !== null,
                fn () => new ResidentProfileResource($this->residentProfile)
            ),
        ];
    }
}
