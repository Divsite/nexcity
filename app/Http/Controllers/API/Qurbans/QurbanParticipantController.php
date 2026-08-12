<?php

namespace App\Http\Controllers\API\Qurbans;

use App\Http\Controllers\Controller;
use App\Models\Qurbans\QurbanOrder;
use App\Models\Qurbans\QurbanOrderItem;
use App\Models\Qurbans\QurbanProgram;
use App\Models\Qurbans\QurbanProgramPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Who is in the patungan, and taking one more at the counter.
 *
 * Patungan is the part of qurban only a mosque can do. A vendor can sell an
 * animal; nobody but the mosque can hold seven strangers' money and guarantee
 * the result. So the screen this feeds is a list of **people**, not of stock.
 *
 * Recording is deliberately the short path, like `QuickCharityController`. The
 * web form carries beneficiaries, savings plans and receipts; none of that
 * belongs on a phone with someone waiting at a table. What is here is what a
 * pengurus actually does: name, phone, how many shares, paid or not.
 */
class QurbanParticipantController extends Controller
{
    public function __construct()
    {
        $this->middleware('capability:browse-qurban')->only('index');
        $this->middleware('capability:add-qurban')->only('store');
    }

    /**
     * The people holding shares in this year's patungan.
     */
    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        if ($organizationId === 0) {
            return response()->json(['message' => __('messages.organization_not_found')], 404);
        }

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'package_id' => ['nullable', 'integer'],
        ]);
        $year = (int) ($validated['year'] ?? now()->year);

        $programIds = QurbanProgram::query()
            ->where('organization_id', $organizationId)
            ->where('year', $year)
            ->pluck('id');

        $items = QurbanOrderItem::query()
            ->whereHas('order', fn ($q) => $q->whereIn('qurban_program_id', $programIds))
            ->when(
                $validated['package_id'] ?? null,
                fn ($q, $packageId) => $q->where('qurban_program_package_id', $packageId),
            )
            ->with(['order.user', 'package', 'animalAllocations.animal'])
            ->latest('id')
            ->get();

        return response()->json([
            'year' => $year,
            'has_participants' => $items->isNotEmpty(),

            'shares_taken' => (int) $items->sum('share_qty'),
            // Only money actually received. An unpaid pledge is not a share of
            // a cow, and counting it would have a pengurus order an animal
            // against money nobody has handed over.
            'shares_paid' => (int) $items
                ->filter(fn (QurbanOrderItem $i) => $i->order?->status === 'paid')
                ->sum('share_qty'),
            'total_collected' => (float) $items
                ->filter(fn (QurbanOrderItem $i) => $i->order?->status === 'paid')
                ->sum('subtotal'),

            'participants' => $items->map(fn (QurbanOrderItem $item) => [
                'id' => $item->id,
                'order_code' => $item->order?->order_code,
                // Falls back to the free-text name: most patungan buyers at a
                // village mosque have no account, and never will.
                'name' => $item->order?->user?->name
                    ?? $item->order?->customer_name
                    ?? '-',
                'phone' => $item->order?->customer_phone,
                'package' => $item->package?->title,
                'shares' => (int) $item->share_qty,
                'subtotal' => (float) $item->subtotal,
                'status' => $item->order?->status,
                // Which animal, once the mosque has allocated one. Null while
                // the batch is still filling — and that is the normal state
                // for most of the season.
                'animal_code' => $item->animalAllocations->first()?->animal?->animal_code,
                'created_at' => $item->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /**
     * Records one participant taking shares, at the counter.
     */
    public function store(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        $validated = $request->validate([
            'package_id' => ['required', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'shares' => ['required', 'integer', 'min:1', 'max:7'],
            'is_paid' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $package = QurbanProgramPackage::query()
            ->with('program')
            ->where('is_active', true)
            ->find($validated['package_id']);

        // Checked rather than trusted: the package id arrives from the client,
        // and without this one mosque could book a participant against
        // another's programme.
        if (! $package || (int) $package->program?->organization_id !== $organizationId) {
            throw ValidationException::withMessages([
                'package_id' => 'Paket ini tidak tersedia di organisasi Anda.',
            ]);
        }

        $shares = (int) $validated['shares'];

        if ($package->remaining_quota < $shares) {
            throw ValidationException::withMessages([
                'shares' => $package->remaining_quota > 0
                    ? "Sisa kuota tinggal {$package->remaining_quota} bagian."
                    : 'Kuota paket ini sudah habis.',
            ]);
        }

        $order = DB::transaction(function () use ($package, $validated, $shares, $organizationId, $request) {
            $paid = (bool) ($validated['is_paid'] ?? true);

            $order = QurbanOrder::create([
                'organization_id' => $organizationId,
                'qurban_program_id' => $package->qurban_program_id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                // Recorded at the counter, not self-service. The distinction
                // matters later: an online buyer gets notified, a walk-in was
                // standing there.
                'source_type' => 'counter',
                'order_code' => 'QRB-' . strtoupper(Str::random(8)),
                'status' => $paid ? 'paid' : 'pending_payment',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            QurbanOrderItem::create([
                'qurban_order_id' => $order->id,
                'qurban_program_package_id' => $package->id,
                'qty' => 1,
                'share_qty' => $shares,
                'price' => $package->price,
                'subtotal' => (float) $package->price * $shares,
                'status' => 'active',
            ]);

            // Held under the same transaction as the order: a share counted as
            // sold without an order behind it, or an order against a quota that
            // was never reduced, are both worse than the write failing.
            $package->decrement('remaining_quota', $shares);

            return $order;
        });

        return response()->json([
            'id' => $order->id,
            'order_code' => $order->order_code,
            'status' => $order->status,
            'message' => 'Peserta patungan dicatat.',
        ], 201);
    }
}
