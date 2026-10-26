<?php

namespace App\Http\Controllers\API\Announcements;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\AnnouncementResource;
use App\Models\Announcements\Announcement;
use App\Models\Organizations\Organization;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Announcements, read side.
 *
 * Two questions are kept apart on purpose, because conflating them is how a
 * feed becomes spam:
 *
 *   May this user read it?      → Announcement::scopeVisibleTo
 *   Should it arrive unasked?   → the membership filter in index()
 *
 * A mosque's public announcement is readable by anyone, but it does not push
 * itself into the feed of someone who has never been to that mosque. They see
 * it when they open the mosque — there is no "follow" in this product, and
 * without one, an open feed would fill with strangers' notices.
 */
class AnnouncementController extends Controller
{
    public function __construct(protected CapabilityResolver $capabilities) {}

    /**
     * The signed-in user's own feed: their RT and the mosques they belong to.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        [$memberIds, $staffIds] = $this->organizationIds($request);

        $announcements = Announcement::query()
            ->published()
            ->visibleTo($memberIds, $staffIds)
            // Their own organizations only — see the class comment.
            ->whereIn('announcements.organization_id', $memberIds)
            ->with(['category', 'organization', 'author'])
            ->feedOrder()
            ->paginate(20);

        return AnnouncementResource::collection($announcements);
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
