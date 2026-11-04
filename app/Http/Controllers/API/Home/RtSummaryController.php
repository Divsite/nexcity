<?php

namespace App\Http\Controllers\API\Home;

use App\Http\Controllers\Controller;
use App\Models\Distributions\Distribution;
use App\Models\Distributions\DistributionRecipient;
use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Organizations\Organization;
use App\Models\Profiles\ResidentDataCorrection;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use App\Services\Authorization\CapabilityResolver;
use App\Support\ResidentCompleteness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The figures an RT officer opens the app to see.
 *
 * Each section is there only if the caller's level grants the module behind
 * it: a bendahara sees iuran, a sekretaris sees warga, the ketua sees both. A
 * section the caller cannot open would be a number pointing at a door they
 * cannot walk through.
 */
class RtSummaryController extends Controller
{
    public function __construct(protected CapabilityResolver $capabilities)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');
        $organization = Organization::query()->find($organizationId);

        if (! $organization || $organization->type !== Organization::TYPE_RT) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $user = $request->user();
        $may = fn (string $permission) => $this->capabilities->holds($user, $permission, $organizationId);

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'residents' => $may('browse-rt-residents') ? $this->residents($organizationId) : null,
            'dues' => $may('browse-rt-dues') ? $this->dues($organizationId) : null,
            // The RT's distribution tab is gated on the resident list behind it.
            'distributions' => $may('browse-rt-residents') ? $this->distributions($organizationId) : null,
        ]);
    }

    /**
     * @return array<string, int>
     */
    protected function residents(int $organizationId): array
    {
        $residents = fn () => User::query()->whereHas(
            'residentProfile',
            fn (Builder $q) => $q->where('organization_id', $organizationId),
        );

        return [
            'total' => $residents()->count(),
            'heads_of_family' => UserResidentProfile::query()
                ->where('organization_id', $organizationId)
                ->where('is_head_family', true)
                ->count(),
            'incomplete' => ResidentCompleteness::scopeIncomplete($residents())->count(),
            'pending_corrections' => ResidentDataCorrection::query()
                ->where('organization_id', $organizationId)
                ->where('status', ResidentDataCorrection::STATUS_PENDING)
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function dues(int $organizationId): array
    {
        $outstanding = RtDuesBill::query()
            ->where('status', RtDuesBill::STATUS_PENDING)
            ->whereHas('period', fn (Builder $q) => $q->where('organization_id', $organizationId));

        // This month's monthly dues; a one-off levy has no month and is not
        // "this month's" anything.
        $thisMonth = RtDuesPeriod::query()
            ->where('organization_id', $organizationId)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->withCount([
                'bills',
                'bills as paid_count' => fn ($q) => $q->where('status', RtDuesBill::STATUS_PAID),
            ])
            ->withSum(['bills as collected_amount' => fn ($q) => $q->where('status', RtDuesBill::STATUS_PAID)], 'amount')
            ->get();

        return [
            'outstanding_amount' => (float) (clone $outstanding)->sum('amount'),
            'residents_in_arrears' => (clone $outstanding)->distinct()->count('resident_id'),
            // Null when no period is open for this month, which is a real
            // answer: the treasurer has not opened it yet.
            'this_month' => $thisMonth->isEmpty() ? null : [
                'label' => $thisMonth->first()->label,
                'bills_count' => (int) $thisMonth->sum('bills_count'),
                'paid_count' => (int) $thisMonth->sum('paid_count'),
                'collected_amount' => (float) $thisMonth->sum('collected_amount'),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function distributions(int $organizationId): array
    {
        $ids = Distribution::query()->where('organization_id', $organizationId)->pluck('id');

        return [
            'recipients_pending' => DistributionRecipient::query()
                ->whereIn('distribution_id', $ids)
                ->where('status', '!=', 'distributed')
                ->count(),
        ];
    }
}
