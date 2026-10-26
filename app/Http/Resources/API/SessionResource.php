<?php

namespace App\Http\Resources\API;

use App\Models\Organizations\OrganizationUser;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Everything a mobile client needs to decide what to show, in one payload.
 *
 * The client never re-derives authorization rules; it renders what this says.
 * See the Flutter side: docs/architecture/auth-and-capabilities.md.
 *
 * @property-read User $resource
 */
class SessionResource extends JsonResource
{
    public function __construct(
        User $resource,
        protected CapabilityResolver $capabilities,
        protected ?int $activeOrganizationId = null,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $memberships = $this->memberships();
        $resolved = $this->capabilities->resolveByOrganization($this->resource, $memberships);

        return [
            'user' => new UserResource($this->resource),
            'memberships' => $memberships
                ->map(fn (OrganizationUser $membership) => new MembershipResource(
                    $membership,
                    $resolved[$membership->organization_id]['capabilities'] ?? [],
                    $resolved[$membership->organization_id]['level_name'] ?? null,
                ))
                ->values(),
            'active_organization_id' => $this->activeOrganizationId
                ?? $this->capabilities->defaultOrganizationId($this->resource, $memberships),
            'global_capabilities' => $this->capabilities->globalCapabilities($this->resource),
        ];
    }

    /**
     * @return Collection<int, OrganizationUser>
     */
    protected function memberships(): Collection
    {
        return $this->resource
            ->organizationMemberships()
            ->with('organization.profile')
            ->get();
    }
}
