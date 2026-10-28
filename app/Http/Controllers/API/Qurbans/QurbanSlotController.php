<?php

namespace App\Http\Controllers\API\Qurbans;

use App\Http\Controllers\Controller;
use App\Models\Qurbans\QurbanAnimal;
use App\Models\Qurbans\QurbanAnimalAllocation;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanOrderItem;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanProgramPackage;
use App\Services\Authorization\CapabilityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The patungan board: Sapi 1, Sapi 2, Sapi 3, and who is in each.
 *
 * This is a sign-up sheet, not a shopping cart. A takmir already runs it on
 * paper — a heading and seven lines — and recruits people into it one by one.
 * The system replaces the paper, not the recruiting.
 *
 * So a person does not choose their fellow shareholders. They pick an animal
 * and put a name on a free line. Who sits on the other lines is somebody else's
 * business, and knowing it is the point: you can see who you are sharing with.
 *
 * Two audiences read the same board:
 *
 * - **Warga** see names, because that is how it works out loud at the mosque.
 *   They do not see phone numbers, which is nobody's business but the
 *   pengurus's.
 * - **Pengurus** see everything, and may fill a line for someone who walked in
 *   with cash and no smartphone.
 */
class QurbanSlotController extends Controller
{
    public function __construct(protected CapabilityResolver $capabilities) {}

    /**
     * Every animal in the season, with its lines filled and empty.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'organization_id' => ['nullable', 'integer'],
        ]);

        $organizationId = (int) (
            $validated['organization_id']
            ?? $request->attributes->get('active_organization_id')
        );

        if ($organizationId === 0) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $year = (int) ($validated['year'] ?? now()->year);
        $user = $request->user();
        $isStaff = $this->capabilities->holds($user, 'browse-qurban', $organizationId);

        $programs = QurbanProgram::query()
            ->where('organization_id', $organizationId)
            ->where('year', $year)
            ->pluck('id');

        $animals = QurbanAnimal::query()
            ->where('organization_id', $organizationId)
            ->whereIn('qurban_program_id', $programs)
            ->with(['program.packages' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('animal_code')
            ->get();

        $allocations = QurbanAnimalAllocation::query()
            ->whereIn('qurban_animal_id', $animals->pluck('id'))
            ->with(['orderItem.order'])
            ->get()
            ->groupBy('qurban_animal_id');

        return response()->json([
            'year' => $year,
            'has_board' => $animals->isNotEmpty(),
            'is_staff' => $isStaff,

            'animals' => $animals->map(function (QurbanAnimal $animal) use ($allocations, $isStaff, $user) {
                $filled = $allocations[$animal->id] ?? collect();
                $package = $animal->program?->packages->first();

                return [
                    'id' => $animal->id,
                    // "Sapi 1" — the heading on the sheet, written before any
                    // animal was bought.
                    'code' => $animal->animal_code,
                    'animal_type' => $animal->animal_type,
                    'share_slots' => (int) $animal->share_slots,
                    'filled_count' => $filled->count(),
                    'status' => $animal->status,

                    // Null until the mosque has actually procured it. Shown
                    // when present because that is when the sheet stops being
                    // a plan and starts being a cow.
                    'ear_tag_code' => $animal->ear_tag_code,
                    'weight' => $animal->weight ? (float) $animal->weight : null,

                    'base_price' => (float) ($package?->base_price ?? 0),
                    'service_fee' => (float) ($package?->service_fee ?? 0),
                    'price_per_share' => (float) ($package?->price ?? 0),
                    'package_id' => $package?->id,

                    'slots' => $this->slots($animal, $filled, $isStaff, $user?->id),
                ];
            })->values(),
        ]);
    }

    /**
     * One row per line on the sheet, filled or empty.
     *
     * Empty lines are returned too. A board that only listed the taken places
     * would answer "who is in" but not "is there room", and the second is the
     * question someone opens this to ask.
     *
     * @param  \Illuminate\Support\Collection<int, QurbanAnimalAllocation>  $filled
     * @return list<array<string, mixed>>
     */
    protected function slots(
        QurbanAnimal $animal,
        $filled,
        bool $isStaff,
        ?int $userId,
    ): array {
        $byIndex = $filled->keyBy('share_index');
        $slots = [];

        for ($index = 1; $index <= (int) $animal->share_slots; $index++) {
            $allocation = $byIndex[$index] ?? null;
            $order = $allocation?->orderItem?->order;

            if (! $allocation) {
                $slots[] = ['index' => $index, 'is_taken' => false];

                continue;
            }

            $slots[] = [
                'index' => $index,
                'is_taken' => true,
                // The name the qurban is offered on behalf of. Shown to
                // everyone: at the mosque this is read out loud, and seeing who
                // you are sharing with is half the reason to join a particular
                // animal.
                'name' => $allocation->notes ?: $order?->customer_name ?? '-',
                'status' => $order?->status,
                'is_paid' => $order?->status === 'paid',
                // Whose line it is, so the app can mark "punya Anda".
                'is_mine' => $userId !== null && (int) $order?->user_id === $userId,
                // Pengurus business only. A warga has no reason to collect the
                // phone numbers of six neighbours from a screen.
                'phone' => $isStaff ? $order?->customer_phone : null,
                'order_id' => $isStaff ? $order?->id : null,
            ];
        }

        return $slots;
    }

    /**
     * Puts a name on the next free line of one animal.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'animal_id' => ['required', 'integer'],
            'names' => ['required', 'array', 'min:1', 'max:7'],
            'names.*' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'is_paid' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $animal = QurbanAnimal::with('program.packages')->find($validated['animal_id']);
        $organizationId = (int) $animal?->organization_id;

        if (! $animal || ! $animal->qurban_program_id) {
            throw ValidationException::withMessages([
                'animal_id' => 'Hewan ini tidak tersedia untuk patungan.',
            ]);
        }

        $user = $request->user();
        $isStaff = $this->capabilities->holds($user, 'add-qurban', $organizationId);

        // A warga signs up for themselves; a pengurus may fill a line for
        // someone who walked in with cash. Both write the same row — what
        // differs is only who is allowed to name somebody else.
        $names = array_values($validated['names']);

        $package = $animal->program?->packages->firstWhere('is_active', true)
            ?? $animal->program?->packages->first();

        if (! $package) {
            throw ValidationException::withMessages([
                'animal_id' => 'Program ini belum punya paket harga.',
            ]);
        }

        $order = DB::transaction(function () use ($animal, $names, $validated, $package, $organizationId, $user, $isStaff) {
            // Locked for the length of the write. Two people tapping "ambil
            // bagian" at the same second would otherwise both be handed line
            // six, and one of them would silently lose their place.
            $taken = QurbanAnimalAllocation::query()
                ->where('qurban_animal_id', $animal->id)
                ->lockForUpdate()
                ->pluck('share_index')
                ->all();

            $free = array_values(array_diff(
                range(1, (int) $animal->share_slots),
                $taken,
            ));

            if (count($free) < count($names)) {
                throw ValidationException::withMessages([
                    'names' => count($free) > 0
                        ? 'Sisa ' . count($free) . ' bagian di hewan ini.'
                        : 'Bagian di hewan ini sudah penuh.',
                ]);
            }

            $paid = (bool) ($validated['is_paid'] ?? false);

            $order = QurbanOrder::create([
                'organization_id' => $organizationId,
                'qurban_program_id' => $animal->qurban_program_id,
                'user_id' => $isStaff ? null : $user->id,
                'customer_name' => $names[0],
                'customer_phone' => $validated['phone'] ?? null,
                'source_type' => $isStaff ? 'counter' : 'app',
                'order_code' => 'QRB-' . strtoupper(Str::random(8)),
                // Unpaid by default. Patungan is normally paid off in
                // instalments, and a slot marked paid on the day it was taken
                // would have the mosque order an animal it cannot fund.
                'status' => $paid ? 'paid' : 'pending_payment',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $item = QurbanOrderItem::create([
                'qurban_order_id' => $order->id,
                'qurban_program_package_id' => $package->id,
                'qty' => 1,
                'share_qty' => count($names),
                'price' => $package->price,
                'subtotal' => (float) $package->price * count($names),
                'status' => 'active',
            ]);

            foreach ($names as $offset => $name) {
                QurbanAnimalAllocation::create([
                    'qurban_animal_id' => $animal->id,
                    'qurban_order_item_id' => $item->id,
                    'qurban_program_id' => $animal->qurban_program_id,
                    'share_index' => $free[$offset],
                    // The name this share is offered on behalf of. One line,
                    // one name — someone taking two lines for themselves and a
                    // late parent fills two, each named.
                    'notes' => $name,
                ]);
            }

            return $order;
        });

        return response()->json([
            'order_id' => $order->id,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'shares' => count($names),
            'message' => 'Bagian patungan diambil.',
        ], 201);
    }
}
