<?php

namespace App\Http\Controllers\API\Home;

use App\Http\Controllers\Controller;
use App\Models\Distributions\Distribution;
use App\Models\Organizations\OrganizationUser;
use App\Services\Charities\CharityTransactionService;
use App\Services\Menus\MenuContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The figures a mosque officer opens the app to see.
 *
 * Deliberately small: four numbers and the officer's own standing. A phone
 * home screen is glanced at between other things, not studied — anything that
 * needs studying belongs on the web, where there is a keyboard and a wide
 * table.
 *
 * Money comes from `CharityTransactionService`, the same calculation the web
 * recap and the OpenClaw report use. Re-deriving a total here would be a second
 * source of truth for the same figure, and the two would disagree eventually.
 */
class MosqueSummaryController extends Controller
{
    public function __construct(
        protected MenuContextResolver $context,
        protected CharityTransactionService $transactions,
    ) {
        $this->middleware('capability:browse-mosque-charity-transactions|browse-mosque-charity-distributions|browse-qurban');
    }

    public function index(Request $request): JsonResponse
    {
        [, $organization] = $this->context->resolve($request->user());

        if (! $organization) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $recap = $this->transactions->dailySummaryByOrganization($organization->id);

        $distributions = Distribution::query()
            ->where('organization_id', $organization->id);

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
            ],

            // What the officer is here as. The header says "Masuk sebagai" and
            // this is the answer — a volunteer scanning coupons and the
            // bendahara see the same screen otherwise.
            'level' => $this->levelName($request, $organization->id),

            'stats' => [
                'charity_today' => (float) $recap['total_money'],
                'charity_today_label' => $recap['total_money_label'],
                'transactions_today' => (int) $recap['total_transactions'],
                'rice_today' => (float) $recap['total_rice'],

                // Open distributions are the actionable number: they are what
                // an officer might have to go and do something about today.
                'distributions_open' => (clone $distributions)
                    ->whereNotIn('status', ['completed'])
                    ->count(),
                'distributions_total' => (clone $distributions)->count(),

                'members' => OrganizationUser::query()
                    ->where('organization_id', $organization->id)
                    ->count(),
            ],
        ]);
    }

    protected function levelName(Request $request, int $organizationId): ?string
    {
        return OrganizationUser::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $request->user()?->id)
            ->value('level_slug');
    }
}
