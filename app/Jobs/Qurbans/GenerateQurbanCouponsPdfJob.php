<?php

namespace App\Jobs\Qurbans;

use App\Models\Qurbans\QurbanCouponExport;
use App\Models\Qurbans\QurbanDistributionBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateQurbanCouponsPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public readonly int $exportId,
        public readonly string $locale = 'en',
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        app()->setLocale($this->locale);

        $export = QurbanCouponExport::find($this->exportId);

        if (! $export) {
            return;
        }

        $export->update(['status' => QurbanCouponExport::STATUS_PROCESSING]);

        try {
            if ($export->type === QurbanCouponExport::TYPE_SINGLE) {
                $pdf = $this->generateSingleBatch($export);
                $filename = 'Qurban_Coupons_' . $export->batch_id . '_' . now()->format('Ymd_His') . '.pdf';
            } else {
                $pdf = $this->generateAll($export);
                $filename = 'Qurban_All_Coupons_' . now()->format('Ymd_His') . '.pdf';
            }

            $dir = 'qurban-exports/' . $export->organization_id;
            $path = $dir . '/' . $filename;

            Storage::put($path, $pdf);

            $export->update([
                'status' => QurbanCouponExport::STATUS_READY,
                'file_path' => $path,
                'expires_at' => now()->addHours(2),
            ]);
        } catch (Throwable $e) {
            $export->update([
                'status' => QurbanCouponExport::STATUS_FAILED,
                'error_message' => substr($e->getMessage(), 0, 255),
            ]);
        }
    }

    private function generateSingleBatch(QurbanCouponExport $export): string
    {
        $batch = QurbanDistributionBatch::query()
            ->with([
                'organization.profile',
                'citizensAssociation',
                'neighborhoodAssociation',
                'coupons.beneficiary',
            ])
            ->findOrFail($export->batch_id);

        return Pdf::loadView('qurbans.exports.coupons', [
            'batch' => $batch,
            'coupons' => $batch->coupons,
        ])->setPaper('a4')->output();
    }

    private function generateAll(QurbanCouponExport $export): string
    {
        $batches = QurbanDistributionBatch::query()
            ->where('organization_id', $export->organization_id)
            ->with([
                'organization.profile',
                'citizensAssociation',
                'neighborhoodAssociation',
                'coupons.beneficiary',
            ])
            ->whereHas('coupons')
            ->orderBy('created_at')
            ->get();

        return Pdf::loadView('qurbans.exports.coupons-all', [
            'batches' => $batches,
        ])->setPaper('a4')->output();
    }
}
