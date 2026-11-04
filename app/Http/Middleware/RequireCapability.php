<?php

namespace App\Http\Middleware;

use App\Models\Organizations\OrganizationUser;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
 *
 * "Active" means the organization on *this request* — the one the mobile client
 * sent in `X-Organization-Id` — not the user's default membership. See
 * `activeOrganizationId()` for why the difference bites in both directions.
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

        if ($this->holdsAny($user, $required, $request)) {
            return $next($request);
        }

        abort(403);
    }

    /**
     * @param  list<string>  $required
     */
    protected function holdsAny(User $user, array $required, Request $request): bool
    {
        $memberships = $user->organizationMemberships()->get();

        $held = collect($this->capabilities->globalCapabilities($user));

        $activeId = $this->activeOrganizationId($request, $user, $memberships);

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

    /**
     * The organization this request is being made in.
     *
     * `ResolveActiveOrganization` has already checked that the caller belongs
     * to whatever `X-Organization-Id` claims, so the attribute is trustworthy
     * by the time we read it.
     *
     * Reading it matters in both directions. A bendahara at RT A who is also a
     * plain member of Masjid B was previously judged by their RT A level on
     * *every* request, so acting in Masjid B they carried RT A's permissions
     * with them — and an officer whose only qurban level sits in the mosque
     * they are actually standing in could be refused for the same reason.
     *
     * Falls back to the default membership when no header was sent, which is
     * how the web app calls in: it has no organization switcher on the request.
     *
     * @param  Collection<int, OrganizationUser>  $memberships
     */
    protected function activeOrganizationId(
        Request $request,
        User $user,
        Collection $memberships,
    ): ?int {
        $active = $request->attributes->get('active_organization_id');

        if (is_int($active)) {
            return $active;
        }

        return $this->capabilities->defaultOrganizationId($user, $memberships);
    }
}
