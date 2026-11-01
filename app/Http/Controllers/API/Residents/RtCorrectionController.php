<?php

namespace App\Http\Controllers\API\Residents;

use App\Http\Controllers\Controller;
use App\Models\Profiles\ResidentDataCorrection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The RT's queue of corrections sent by its warga.
 *
 * Resolving is a record of what the RT did, not the edit itself: "applied"
 * means the pengurus changed the resident on the web. Letting this endpoint
 * write the change would put an unreviewed value from a phone straight into
 * the data the mosque builds its zakat lists from.
 */
class RtCorrectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        $corrections = ResidentDataCorrection::query()
            ->with('user')
            ->where('organization_id', $organizationId)
            ->where('status', ResidentDataCorrection::STATUS_PENDING)
            ->oldest('id')
            ->get()
            ->map(fn (ResidentDataCorrection $c) => [
                'id' => $c->id,
                'resident_id' => $c->user_id,
                'resident_name' => $c->user?->name,
                'field' => $c->field,
                'field_label' => ResidentDataCorrection::FIELDS[$c->field] ?? $c->field,
                'proposed_value' => $c->proposed_value,
                'note' => $c->note,
                'created_at' => $c->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $corrections]);
    }

    public function resolve(Request $request, ResidentDataCorrection $correction): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('active_organization_id');

        // Another RT's request reads as not found, not forbidden: there is no
        // reason to confirm it exists.
        if ((int) $correction->organization_id !== $organizationId) {
            return response()->json(['message' => 'Permintaan tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:applied,rejected'],
        ]);

        if ($correction->status !== ResidentDataCorrection::STATUS_PENDING) {
            return response()->json(['message' => 'Permintaan ini sudah ditinjau.'], 409);
        }

        $correction->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $correction->id, 'status' => $correction->status]]);
    }
}
