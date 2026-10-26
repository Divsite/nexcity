<?php

namespace App\Http\Controllers\Dues;

use App\Actions\Dues\OpenDuesScheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dues\StoreDuesSchemeRequest;
use App\Models\Dues\RtDuesBill;
use App\Models\Dues\RtDuesPeriod;
use App\Models\Dues\RtDuesRate;
use App\Models\Dues\RtDuesScheme;
use App\Models\Organizations\Organization;
use App\Models\Profiles\UserResidentProfile;
use App\Models\Users\User;
use App\Services\Menus\MenuContextResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Iuran RT, from the treasurer's side.
 *
 * The shape follows the printed card an RT already uses: a scheme for the year,
 * two rates side by side, twelve months opened at once. Residents only read
 * this on mobile — see `MyDuesController`.
 *
 * Every query is scoped to the officer's own RT; an id in the URL is checked
 * against that, never trusted.
 */
class RtDuesController extends Controller
{
    public function __construct(protected MenuContextResolver $context)
    {
        $this->middleware('capability:browse-rt-dues')->only(['index', 'show', 'tiers']);
        $this->middleware('capability:add-rt-dues')->only('store');
        $this->middleware('capability:edit-rt-dues')->only(['update', 'updateBill', 'updateTiers']);
    }

    public function index(): View
    {
        $organization = $this->rt();

        $years = RtDuesScheme::query()
            ->where('organization_id', $organization?->id)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $selectedYear = request()->integer('year') ?: null;

        $schemes = RtDuesScheme::query()
            ->where('organization_id', $organization?->id)
            ->when($selectedYear, fn ($query) => $query->where('year', $selectedYear))
            ->with('rates')
            ->withCount('periods')
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();

        return view('rt.dues.index', [
            'schemes' => $schemes,
            'organization' => $organization,
            'commonTiers' => RtDuesRate::COMMON_TIERS,
            'years' => $years,
            'selectedYear' => $selectedYear,
            // Backfilling is allowed, opening a year early is not — see
            // StoreDuesSchemeRequest.
            'selectableYears' => range(now()->year, now()->year - 5),
        ]);
    }

    public function store(StoreDuesSchemeRequest $request, OpenDuesScheme $open): RedirectResponse
    {
        $organization = $this->rt();

        if (! $organization) {
            return back()->with('error', __('messages.organization_not_found'));
        }

        $validated = $request->validated();

        // Create only. Re-submitting the same name and year is refused by the
        // request rather than quietly folded into the existing scheme: an RT
        // that opens 2026 twice would bill every household twice, and nobody
        // would notice until a resident complained. Correcting rates has its
        // own path — see update().
        $scheme = RtDuesScheme::create([
            'organization_id' => $organization->id,
            'name' => $validated['name'],
            'year' => $validated['year'],
            'type' => $validated['type'],
            'programs' => $validated['programs'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        $this->syncRates($scheme, $validated['rates'], (int) $validated['default_rate']);

        $result = $open->handle(
            $scheme->load('rates'),
            dueDate: $validated['due_date'] ?? null,
            createdBy: $request->user()?->id,
        );

        return redirect()
            ->route('rt.dues.show', $scheme)
            ->with('success', __('messages.dues_scheme_opened', $result));
    }

    /**
     * Corrects an existing scheme's rates and programme list.
     *
     * Separate from store() on purpose. A treasurer must be able to fix a wrong
     * figure without the system treating it as a new collection — and must not
     * be able to create a second 2026 by accident while trying.
     *
     * Bills already issued keep their own copy of the amount, so a correction
     * changes what is billed from now on, never what a household was already
     * told they owed.
     */
    public function update(Request $request, RtDuesScheme $scheme, OpenDuesScheme $open): RedirectResponse
    {
        $this->authorizeOwnership($scheme->organization_id);

        $validated = $request->validate([
            'programs' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
            'rates' => ['required', 'array', 'min:1', 'max:10'],
            'rates.*.label' => ['required', 'string', 'max:60'],
            'rates.*.tier' => ['nullable', 'string', 'max:40'],
            'rates.*.amount' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'default_rate' => ['required', 'integer', 'min:0'],
        ]);

        $scheme->update(['programs' => $validated['programs'] ?? null]);

        $this->syncRates($scheme, $validated['rates'], (int) $validated['default_rate']);

        // Re-running the issue picks up residents who moved in since, without
        // touching anyone already billed.
        $result = $open->handle(
            $scheme->load('rates'),
            dueDate: $validated['due_date'] ?? null,
            createdBy: $request->user()?->id,
        );

        return redirect()
            ->route('rt.dues.show', $scheme)
            ->with('success', __('messages.dues_scheme_updated', ['bills' => $result['bills']]));
    }

    public function show(RtDuesScheme $scheme): View
    {
        $this->authorizeOwnership($scheme->organization_id);

        $periods = $scheme->periods()
            ->withCount([
                'bills',
                'bills as paid_count' => fn ($query) => $query->where('status', RtDuesBill::STATUS_PAID),
                'bills as waived_count' => fn ($query) => $query->where('status', RtDuesBill::STATUS_WAIVED),
            ])
            ->withSum(
                ['bills as collected_amount' => fn ($query) => $query->where('status', RtDuesBill::STATUS_PAID)],
                'amount'
            )
            ->orderBy('month')
            ->get();

        return view('rt.dues.show', [
            'scheme' => $scheme->load('rates'),
            'periods' => $periods,
        ]);
    }

    public function period(RtDuesPeriod $period): View
    {
        $this->authorizeOwnership($period->organization_id);

        $period->loadCount([
            'bills',
            'bills as paid_count' => fn ($query) => $query->where('status', RtDuesBill::STATUS_PAID),
            'bills as waived_count' => fn ($query) => $query->where('status', RtDuesBill::STATUS_WAIVED),
        ]);

        return view('rt.dues.period', ['period' => $period->load('scheme')]);
    }

    public function updateBill(Request $request, RtDuesBill $bill): RedirectResponse
    {
        $this->authorizeOwnership($bill->period->organization_id);

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', RtDuesBill::STATUSES)],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $paid = $validated['status'] === RtDuesBill::STATUS_PAID;

        $bill->update([
            'status' => $validated['status'],
            // Cleared when a bill moves back off "paid", so a correction does
            // not leave a payment date on a bill nobody paid.
            'paid_at' => $paid ? ($bill->paid_at ?? now()) : null,
            'payment_method' => $paid ? ($validated['payment_method'] ?? 'cash') : null,
            'note' => $validated['note'] ?? null,
            'recorded_by' => $request->user()?->id,
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }

    /**
     * Golongan warga — who is Ber KK and who is not.
     *
     * A separate screen on purpose: this is household data, not billing data.
     * A treasurer opens it when someone moves in, not every month. Deciding it
     * inside the dues form would mean re-deciding it twelve times a year, and
     * would leave past months unexplainable once someone's status changed.
     */
    public function tiers(): View
    {
        $organization = $this->rt();

        $residents = UserResidentProfile::query()
            ->where('organization_id', $organization?->id)
            ->with('user:id,name,phone')
            ->orderBy('id')
            ->paginate(50);

        return view('rt.dues.tiers', [
            'residents' => $residents,
            'organization' => $organization,
            'commonTiers' => RtDuesRate::COMMON_TIERS,
        ]);
    }

    public function updateTiers(Request $request): RedirectResponse
    {
        $organization = $this->rt();

        $validated = $request->validate([
            'tier' => ['nullable', 'string', 'max:40'],
            'resident_ids' => ['required', 'array', 'min:1'],
            'resident_ids.*' => ['integer'],
        ]);

        // Scoped by organization, so a tampered id cannot reclassify someone
        // else's resident.
        $changed = UserResidentProfile::query()
            ->where('organization_id', $organization?->id)
            ->whereIn('user_id', $validated['resident_ids'])
            ->update(['dues_tier' => $validated['tier'] ?: null]);

        return back()->with('success', __('messages.dues_tier_updated', ['count' => $changed]));
    }

    /**
     * Replaces the scheme's rates with what the form submitted.
     *
     * @param  array<int, array{label: string, tier: ?string, amount: mixed}>  $rates
     */
    protected function syncRates(RtDuesScheme $scheme, array $rates, int $defaultIndex): void
    {
        $keep = [];

        foreach (array_values($rates) as $index => $rate) {
            $tier = ($rate['tier'] ?? '') !== '' ? $rate['tier'] : null;

            $row = $scheme->rates()->updateOrCreate(
                ['tier' => $tier],
                [
                    'label' => $rate['label'],
                    'amount' => $rate['amount'],
                    'is_default' => $index === $defaultIndex,
                ],
            );

            $keep[] = $row->id;
        }

        // A rate the treasurer removed should stop applying to future runs.
        // Bills already issued keep their own copy of the amount, so history is
        // unaffected.
        $scheme->rates()->whereNotIn('id', $keep)->delete();
    }

    /** The RT the signed-in officer is acting in. */
    protected function rt(): ?Organization
    {
        [, $organization] = $this->context->resolve(auth()->user());

        return $organization;
    }

    /**
     * Anything reached by id must belong to the officer's own RT.
     *
     * Without this the routes would be reachable by typing another RT's id —
     * the same gap the resident QR card had. See
     * docs/operations/authorization-audit.md.
     */
    protected function authorizeOwnership(?int $organizationId): void
    {
        $user = auth()->user();

        if ($user instanceof User && $user->hasRole('superadmin')) {
            return;
        }

        abort_if($organizationId === null || $organizationId !== $this->rt()?->id, 403);
    }
}
