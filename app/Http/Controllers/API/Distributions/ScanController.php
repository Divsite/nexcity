<?php

namespace App\Http\Controllers\API\Distributions;

use App\Actions\Distributions\MarkRecipientStatus;
use App\Http\Controllers\Controller;
use App\Models\Distributions\DistributionRecipient;
use App\Services\Authorization\AssignmentCapabilities;
use App\Services\Distributions\ScanResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Field scanning: read a card, then mark what happened.
 *
 * Two endpoints on purpose. Scanning tells the officer who is in front of them
 * and whether that person is entitled; marking is a separate, deliberate act.
 * Folding them together would mean a mis-scan silently recorded a distribution.
 */
class ScanController extends Controller
{
    public function __construct(
        protected ScanResolver $resolver,
        protected AssignmentCapabilities $assignments,
    ) {
    }

    /**
     * Resolve a scanned card in the caller's active organization.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'required|string|max:64',
            'distribution_id' => 'nullable|integer',
        ]);

        $organizationId = $request->attributes->get('active_organization_id');

        if (! $organizationId) {
            return response()->json(
                ['message' => 'Pilih organisasi terlebih dahulu sebelum memindai.'],
                422,
            );
        }

        $result = $this->resolver->resolve(
            $validated['qr_token'],
            (int) $organizationId,
            isset($validated['distribution_id']) ? (int) $validated['distribution_id'] : null,
        );

        return response()->json($result + [
            'message' => $this->messageFor($result['status']),
        ]);
    }

    /**
     * Mark a recipient as distributed, failed, rescheduled or redirected.
     */
    public function markRecipient(
        Request $request,
        DistributionRecipient $recipient,
        MarkRecipientStatus $mark,
    ): JsonResponse {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', MarkRecipientStatus::STATUSES),
            'note' => 'nullable|string|max:500',
            'reschedule_at' => 'nullable|date',
        ]);

        $organizationId = (int) $request->attributes->get('active_organization_id');
        $recipient->loadMissing('distribution');

        // The organization header is verified by middleware, but that only
        // proves membership. This proves the recipient belongs to the
        // organization being acted for.
        if ((int) $recipient->distribution?->organization_id !== $organizationId) {
            return response()->json(['message' => 'Penerima ini bukan milik organisasi Anda.'], 403);
        }

        try {
            $updated = $mark->handle(
                $recipient,
                $validated['status'],
                $request->user(),
                $validated['note'] ?? null,
                $validated['reschedule_at'] ?? null,
            );
        } catch (RuntimeException $e) {
            // A duplicate claim is a normal field event, not a server fault.
            return response()->json([
                'message' => $e->getMessage(),
                'distributed_at' => $recipient->distributed_at?->toIso8601String(),
                'distributed_by' => $recipient->officer?->name,
            ], 409);
        }

        return response()->json([
            'recipient_id' => $updated->id,
            'status' => $updated->status,
            'distributed_at' => $updated->distributed_at?->toIso8601String(),
            'message' => 'Status penerima diperbarui.',
        ]);
    }

    protected function messageFor(string $status): string
    {
        return match ($status) {
            ScanResolver::ENTITLED => 'Berhak menerima.',
            ScanResolver::ALREADY_CLAIMED => 'Sudah menerima distribusi ini.',
            ScanResolver::NOT_ENTITLED => 'Tidak terdaftar di program ini.',
            default => 'Kartu tidak dikenali.',
        };
    }
}
