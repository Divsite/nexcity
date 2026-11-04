<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One organization the user belongs to, with what they may do there.
 *
 * @property-read \App\Models\Organizations\OrganizationUser $resource
 */
class MembershipResource extends JsonResource
{
    /**
     * @param  list<string>  $capabilities
     */
    public function __construct($resource, protected array $capabilities = [], protected ?string $levelName = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $organization = $this->resource->organization;

        return [
            'organization' => $organization
                ? new OrganizationResource($organization)
                : null,
            'is_primary' => (bool) $this->resource->is_primary,
            'level' => $this->resource->level_slug
                ? [
                    'slug' => $this->resource->level_slug,
                    'name' => $this->levelName,
                ]
                : null,
            'capabilities' => $this->capabilities,
        ];
    }
}
