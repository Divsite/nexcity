<?php

namespace App\Http\Controllers\API\Residents;

use App\Http\Controllers\Controller;
use App\Models\Profiles\ResidentDataCorrection;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use App\Support\ResidentCompleteness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The RT's own warga, read-only, for checking the data the mosque relies on.
 *
 * Adding and editing residents stays on the web. This list exists so a
 * pengurus RT can see, from a phone, whose KK, address, number or card is
 * still missing — and chase it before the mosque builds a zakat list from it.
 *
 * Deliberately thin: no NIK, no full KK number, no phone number. The web
 * already holds those for whoever needs them.
 */
class RtResidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'filter' => ['nullable', 'in:all,incomplete'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $residents = $this->residentsOf($organizationId)
            ->with('residentProfile')
            ->when($validated['search'] ?? null, fn (Builder $q, string $search) => $q
                ->where('name', 'like', '%' . $search . '%'))
            ->when(($validated['filter'] ?? 'all') === 'incomplete', fn (Builder $q) => ResidentCompleteness::scopeIncomplete($q))
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 30);

        return response()->json([
            'data' => collect($residents->items())->map(function (User $user) {
                $profile = $user->residentProfile;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'address_line' => $profile->address_line,
                    'is_head_family' => (bool) $profile->is_head_family,
                    'family_members_count' => $profile->family_members_count,
                    'family_card' => ResidentCompleteness::maskFamilyCard($profile->family_card_number),
                    'missing' => ResidentCompleteness::missing($user, $profile),
                ];
            })->values(),
            'meta' => [
                'current_page' => $residents->currentPage(),
                'last_page' => $residents->lastPage(),
                'total' => $residents->total(),
            ],
            // Over the whole RT, not the page: "12 warga belum lengkap" is
            // the number a pengurus works down.
            'summary' => [
                'residents' => $this->residentsOf($organizationId)->count(),
                'heads_of_family' => UserResidentProfile::query()
                    ->where('organization_id', $organizationId)
                    ->where('is_head_family', true)
                    ->count(),
                'incomplete' => ResidentCompleteness::scopeIncomplete($this->residentsOf($organizationId))->count(),
                'pending_corrections' => ResidentDataCorrection::query()
                    ->where('organization_id', $organizationId)
                    ->where('status', ResidentDataCorrection::STATUS_PENDING)
                    ->count(),
            ],
            'checks' => ResidentCompleteness::CHECKS,
        ]);
    }

    /**
     * @return Builder<User>
     */
    protected function residentsOf(int $organizationId): Builder
    {
        return User::query()->whereHas(
            'residentProfile',
            fn (Builder $profile) => $profile->where('organization_id', $organizationId),
        );
    }
}
