<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the X-Organization-Id header and verifies the caller belongs there.
 *
 * The mobile client remembers which organization the user is acting in and
 * sends it on every request; nothing is stored server-side. This middleware is
 * what stops that header from being a way to read another organization's data
 * simply by editing a number.
 *
 * On success the id is put on the request attributes so controllers and
 * services can scope their queries without re-reading the header:
 *
 *     $request->attributes->get('active_organization_id')
 *
 * A missing header is not an error — many endpoints are not organization
 * scoped. An *invalid* one is, because it can only mean a stale client or
 * someone probing.
 */
class ResolveActiveOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->header('X-Organization-Id');

        if (blank($organizationId)) {
            return $next($request);
        }

        if (! ctype_digit((string) $organizationId)) {
            return response()->json(
                ['message' => 'Konteks organisasi tidak valid.'],
                400
            );
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $organizationId = (int) $organizationId;

        // Superadmin operates across organizations; everyone else must be a
        // member of the one they claim to be acting in.
        $allowed = $user->hasRole('superadmin')
            || $user->organizationMemberships()
                ->where('organization_id', $organizationId)
                ->exists();

        if (! $allowed) {
            return response()->json(
                ['message' => 'Anda bukan anggota organisasi ini.'],
                403
            );
        }

        $request->attributes->set('active_organization_id', $organizationId);

        return $next($request);
    }
}
