<?php

namespace App\Http\Controllers\API\Announcements;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\AnnouncementResource;
use App\Models\Announcements\Announcement;
use App\Models\Organizations\Organization;
use App\Models\Profiles\UserResidentProfile;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Announcements, read side.
 *
 * Two questions are kept apart on purpose, because conflating them is how a
 * feed becomes spam:
 *
 *   May this user read it?      → Announcement::scopeVisibleTo
 *   Should it arrive unasked?   → the membership filter in index()
 *
 * A public announcement is readable by anyone, but it does not push itself
 * into the feed of someone who has never been near that mosque or RT. They see
 * it when they open the organization — there is no "follow" in this product,
 * and without one an open feed would fill with strangers' notices.
 *
 * The one widening the warga asks for themselves is `area`: public notices
 * from organizations in the same kelurahan, kecamatan, kota or provinsi as
 * where they live. The location is stored as five separate fields on both the
 * warga and the organization precisely so it can be matched one level at a
 * time.
 */
class AnnouncementController extends Controller
{
    public function __construct(protected CapabilityResolver $capabilities) {}

    /**
     * The signed-in user's own feed: their RT and the mosques they belong to.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'area' => ['nullable', Rule::in(array_keys(self::AREAS))],
        ]);

        [$memberIds, $staffIds] = $this->organizationIds($request);

        $area = $validated['area'] ?? null;
        $place = $area ? $this->placeOf($request->user()->id, self::AREAS[$area]) : null;

        $announcements = Announcement::query()
            ->published()
            ->visibleTo($memberIds, $staffIds)
            ->where(function ($q) use ($memberIds, $area, $place) {
                // Their own organizations — see the class comment.
                $q->whereIn('announcements.organization_id', $memberIds);

                // Plus, when asked, public notices from around where they
                // live. Only public: a neighbouring RT's members-only notice
                // stays with that RT, and visibleTo above already holds that.
                if ($place !== null) {
                    $column = self::AREAS[$area];
                    $q->orWhere(fn ($near) => $near
                        ->where('announcements.audience', Announcement::AUDIENCE_PUBLIC)
                        ->whereHas('organization', fn ($o) => $o->where($column, $place)));
                }
            })
            ->with(['category', 'organization', 'author'])
            ->feedOrder()
            ->paginate(20);

        return AnnouncementResource::collection($announcements)->additional([
            // Null when no area was asked for, or the warga's address has no
            // value at that level — the app says so instead of showing an
            // unexplained short list.
            'area' => $place !== null ? $area : null,
        ]);
    }

    /** `area` → the location column it matches, warga and organization alike. */
    protected const AREAS = [
        'village' => 'village_id',
        'district' => 'district_id',
        'city' => 'city_id',
        'province' => 'province_id',
    ];

    /**
     * Where the warga lives, at one level: their resident profile first, then
     * the RT it points to, then an RT they are a member of — the address is
     * often filled in on the RT and never on the person.
     */
    protected function placeOf(int $userId, string $column): ?int
    {
        $profile = UserResidentProfile::query()
            ->with('organization')
            ->where('user_id', $userId)
            ->first();

        $value = $profile?->{$column}
            ?? $profile?->organization?->{$column}
            ?? Organization::query()
                ->where('type', Organization::TYPE_RT)
                ->whereHas('users', fn ($q) => $q->whereKey($userId))
                ->whereNotNull($column)
                ->value($column);

        return $value !== null ? (int) $value : null;
    }

    /**
     * One organization's announcements — what you see on a mosque's page.
     *
     * No membership filter here: a visitor browsing a mosque should see its
     * public notices. What they must not see is anything addressed to members
     * or pengurus, and `visibleTo` is what holds that line.
     */
    public function forOrganization(Request $request, string $slug): AnonymousResourceCollection
    {
        $organization = Organization::where('slug', $slug)->firstOrFail();
        [$memberIds, $staffIds] = $this->organizationIds($request);

        $announcements = Announcement::query()
            ->where('announcements.organization_id', $organization->id)
            ->published()
            ->visibleTo($memberIds, $staffIds)
            ->with(['category', 'organization', 'author'])
            ->feedOrder()
            ->paginate(20);

        return AnnouncementResource::collection($announcements);
    }

    /**
     * A single announcement, opened from a notification or a link.
     */
    public function show(Request $request, Announcement $announcement): JsonResponse
    {
        [$memberIds, $staffIds] = $this->organizationIds($request);

        $visible = Announcement::query()
            ->whereKey($announcement->id)
            ->published()
            ->visibleTo($memberIds, $staffIds)
            ->with(['category', 'organization', 'author'])
            ->first();

        // 404, not 403: telling someone an announcement exists but is not for
        // them leaks that the RT announced something.
        if (! $visible) {
            return response()->json(['message' => __('messages.announcement_not_found')], 404);
        }

        return response()->json(['data' => new AnnouncementResource($visible)]);
    }

    /**
     * The organizations this user belongs to, and the subset where they are
     * pengurus.
     *
     * Staff is decided by CapabilityResolver, the same authority the menu and
     * the middleware use — not by reading `role` off the pivot, which is the
     * mistake this project has already made once.
     *
     * @return array{list<int>, list<int>}
     */
    protected function organizationIds(Request $request): array
    {
        $user = $request->user();
        $memberships = $user->organizationMemberships()->get();

        $memberIds = $memberships->pluck('organization_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $resolved = $this->capabilities->resolveByOrganization($user, $memberships);

        $staffIds = collect($resolved)
            ->filter(fn (array $entry) => ($entry['capabilities'] ?? []) !== [])
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [$memberIds, $staffIds];
    }
}
