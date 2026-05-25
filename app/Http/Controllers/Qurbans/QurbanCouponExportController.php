<?php

namespace App\Http\Controllers\Qurbans;

use App\Http\Controllers\Controller;
use App\Jobs\Qurbans\GenerateQurbanCouponsPdfJob;
use App\Models\Qurbans\QurbanCouponExport;
use App\Models\Qurbans\QurbanDistributionBatch;
use App\Services\Qurbans\QurbanDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QurbanCouponExportController extends Controller
{
    public function __construct(protected QurbanDistributionService $service) {}

    public function dispatchBatch(QurbanDistributionBatch $batch): RedirectResponse
    {
        $this->service->authorizeBatch($batch);

        $export = QurbanCouponExport::create([
            'organization_id' => $batch->organization_id,
            'batch_id' => $batch->id,
            'type' => QurbanCouponExport::TYPE_SINGLE,
            'status' => QurbanCouponExport::STATUS_PENDING,
            'created_by' => auth()->id(),
        ]);

        GenerateQurbanCouponsPdfJob::dispatch($export->id, app()->getLocale());

        return redirect()->route('mosque.qurban.coupon-exports.status', $export);
    }

    public function dispatchAll(): RedirectResponse
    {
        $context = $this->service->partnerContext();
        abort_unless($context, 403);

        $export = QurbanCouponExport::create([
            'organization_id' => $context['organization_id'],
            'batch_id' => null,
            'type' => QurbanCouponExport::TYPE_ALL,
            'status' => QurbanCouponExport::STATUS_PENDING,
            'created_by' => auth()->id(),
        ]);

        GenerateQurbanCouponsPdfJob::dispatch($export->id, app()->getLocale());

        return redirect()->route('mosque.qurban.coupon-exports.status', $export);
    }

    public function status(QurbanCouponExport $export): JsonResponse|\Illuminate\View\View
    {
        $this->authorizeExport($export);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => $export->status,
                'ready' => $export->isReady(),
                'expired' => $export->isExpired(),
                'download_url' => $export->isReady() && ! $export->isExpired()
                    ? route('mosque.qurban.coupon-exports.download', $export)
                    : null,
                'error_message' => $export->error_message,
            ]);
        }

        return view('qurbans.exports.status', compact('export'));
    }

    public function download(QurbanCouponExport $export): StreamedResponse
    {
        $this->authorizeExport($export);

        abort_unless($export->isReady(), 404);
        abort_if($export->isExpired(), 410, 'Export link has expired.');
        abort_unless(Storage::exists($export->file_path), 404);

        $filename = $export->type === QurbanCouponExport::TYPE_ALL
            ? 'Qurban_All_Coupons.pdf'
            : 'Qurban_Coupons_Batch_' . $export->batch_id . '.pdf';

        return Storage::download($export->file_path, $filename);
    }

    private function authorizeExport(QurbanCouponExport $export): void
    {
        /** @var \App\Models\Users\User|null $user */
        $user = auth()->user();
        abort_unless(
            $export->created_by === auth()->id() || $user?->can('edit-settings'),
            403
        );
    }
}
