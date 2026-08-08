<?php

namespace App\Http\Middleware;

use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorizes by the user's **level** in their organization, not by their role.
 *
 * `permission:` (Spatie) reads the role, and every mosque officer carries the
 * same `mosque_admin` role — so a bendahara passed `permission:delete-qurban`
 * and could reach the qurban routes by typing the URL. See
 * docs/operations/authorization-audit.md.
 *
 * Usage mirrors Spatie's, including the OR form:
 *
 *     $this->middleware('capability:browse-qurban');
 *     $this->middleware('capability:add-residents|add-rt-residents');
 *
 * A name is allowed if the user holds it either globally (account-level
 * permissions, which come from the role) or through their level in the active
 * organization. Superadmin is never scoped.
 */
class RequireCapability
{
    public function __construct(protected CapabilityResolver $capabilities)
    {
    }

    public function handle(Request $request, Closure $next, string $names): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        // Platform staff, not a partner. Not scoped to any organization.
        if ($user->hasRole('superadmin')) {
            return $next($request);
        }

        $required = array_filter(explode('|', $names));

        if ($this->holdsAny($user, $required)) {
            return $next($request);
        }

        abort(403);
    }

    /**
     * @param  list<string>  $required
     */
    protected function holdsAny(User $user, array $required): bool
    {
        $memberships = $user->organizationMemberships()->get();

        $held = collect($this->capabilities->globalCapabilities($user));

        $activeId = $this->capabilities->defaultOrganizationId($user, $memberships);

        if ($activeId !== null) {
            $resolved = $this->capabilities->resolveByOrganization($user, $memberships);
            $held = $held->merge($resolved[$activeId]['capabilities'] ?? []);
        }

        foreach ($required as $name) {
            if ($held->contains($name)) {
                return true;
            }
        }

        return false;
    }
}
