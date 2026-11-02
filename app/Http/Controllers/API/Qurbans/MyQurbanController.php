<?php

namespace App\Http\Controllers\API\Qurbans;

use App\Http\Controllers\Controller;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanAnimalAllocation;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanOrderItem;
use App\Models\Qurbans\QurbanOrderPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Qurban saya": the pekurban's own lines, what they owe, and where the
 * animal is.
 *
 * For a perantau this screen is the product: they paid from far away and
 * cannot stand in the courtyard. Which animal, who shares it, how much is
 * left, and whether it has been slaughtered yet is what they open the app to
 * see.
 *
 * Only orders taken from the app carry the warga's account; a line a pengurus
 * filled at the counter has no owner to show it to. Scoped to the caller.
 */
class MyQurbanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = QurbanOrder::query()
            ->where('user_id', $request->user()->id)
            ->where('status', '!=', QurbanOrder::STATUS_CANCELLED)
            ->with([
                'organization',
                'program',
                'items.animalAllocations.animal',
                'payments' => fn ($q) => $q->latest('paid_at'),
            ])
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $orders->map(fn (QurbanOrder $order) => [
                'id' => $order->id,
                'code' => $order->order_code,
                'status' => $order->status,
                'program' => $order->program?->title,
                'year' => $order->program?->year,
                'organization' => $order->organization?->name,
                'organization_slug' => $order->organization?->slug,
                ...$order->paymentSummary(),
                'shares' => $order->items
                    ->flatMap(fn (QurbanOrderItem $item) => $item->animalAllocations)
                    ->sortBy('share_index')
                    ->map(fn (QurbanAnimalAllocation $a) => [
                        'name' => $a->notes,
                        'share_index' => $a->share_index,
                        'animal' => $this->animal($a->animal),
                    ])
                    ->values(),
                'payments' => $order->payments
                    ->where('status', '!=', 'cancelled')
                    ->map(fn (QurbanOrderPayment $p) => [
                        'amount' => (float) $p->amount,
                        'method' => $p->payment_method,
                        'paid_at' => $p->paid_at?->toIso8601String(),
                    ])
                    ->values(),
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function animal(?QurbanAnimal $animal): ?array
    {
        if (! $animal) {
            return null;
        }

        return [
            'code' => $animal->animal_code,
            'type' => $animal->animal_type,
            'share_slots' => (int) $animal->share_slots,
            // Where it is: draft → ready → in_transit → arrived → slaughtered →
            // processed → distributed. The app draws this as a line of steps.
            'status' => $animal->status,
            // Null until the mosque has the actual animal; shown once it does.
            'ear_tag_code' => $animal->ear_tag_code,
            'weight' => $animal->weight ? (float) $animal->weight : null,
        ];
    }
}
