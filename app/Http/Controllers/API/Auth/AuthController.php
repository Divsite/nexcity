<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\SessionResource;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(protected CapabilityResolver $capabilities)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->login)
            ->orWhere('username', $request->login)
            ->orWhere('email', $request->login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Nomor telepon atau password salah.'], 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        // Same shape as /auth/me, so the client has one way to read a session.
        // resolve() rather than toArray(): it runs the resource pipeline, which
        // is what strips optional fields the user does not have (a resident
        // profile, for instance) instead of emitting them as `{}`.
        return response()->json(
            (new SessionResource($user, $this->capabilities))->resolve($request)
                + ['access_token' => $token, 'token_type' => 'Bearer']
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(
            new SessionResource(
                $request->user(),
                $this->capabilities,
                $request->attributes->get('active_organization_id'),
            )
        );
    }

    /**
     * Switches which organization the session acts in.
     *
     * Nothing is stored server-side: the client keeps the choice and sends it
     * back as X-Organization-Id. This endpoint's job is to *verify* the user
     * may act there, and to return the session already shaped for it — so the
     * client has a single way to load a session rather than merging a partial
     * response into one it already holds.
     */
    public function setActiveOrganization(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization_id' => 'required|integer',
        ]);

        $user = $request->user();

        $isMember = $user->organizationMemberships()
            ->where('organization_id', $validated['organization_id'])
            ->exists();

        if (! $isMember) {
            return response()->json(
                ['message' => 'Anda bukan anggota organisasi ini.'],
                403
            );
        }

        return response()->json(
            new SessionResource($user, $this->capabilities, (int) $validated['organization_id'])
        );
    }
}
