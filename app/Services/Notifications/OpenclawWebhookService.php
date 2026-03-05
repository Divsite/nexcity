<?php

namespace App\Services\Notifications;

use App\Models\Charities\CharityTransaction;
use App\Models\Organizations\OrganizationWhatsappGroup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Cknow\Money\Money;

class OpenclawWebhookService
{
    public function sendTransferNotification(CharityTransaction $transaction): void
    {
        $url = config('services.openclaw.webhook_url');
        $token = config('services.openclaw.webhook_token');

        if (empty($url) || empty($token)) {
            return;
        }

        $groups = OrganizationWhatsappGroup::query()
            ->where('organization_id', $transaction->organization_id)
            ->orderBy('id')
            ->get();

        if ($groups->isEmpty()) {
            return;
        }

        $payment = $transaction->paymentMethod;
        $charityType = $transaction->charityType?->source?->name ?? '-';
        $methodLabel = CharityTransaction::paymentMethodLabels()[$transaction->payment_method] ?? $transaction->payment_method;
        $moneyLabel = Money::IDR($transaction->total_money ?? 0)->format(app()->getLocale());
        $riceLabel = Number::format($transaction->total_rice ?? 0, 2, 2, app()->getLocale());

        $bankName = $payment?->name ?? '-';
        $accountNumber = $payment?->account_number ?? '-';
        $timeLabel = optional($transaction->created_at)->format('d/m/Y H:i');

        $message = implode("\n", [
            "Transaksi zakat masuk:",
            "Nama Muzakki: {$transaction->payer_name}",
            "Jenis: {$charityType}",
            "Nominal: {$moneyLabel}",
            "Beras: {$riceLabel} L",
            "Metode: {$methodLabel}",
            "Bank: {$bankName}",
            "Ref: {$accountNumber}",
            "Waktu: {$timeLabel}",
            "Sumber: Sistem Zakat Nexcity",
        ]);

        foreach ($groups as $group) {
            try {
                Http::withToken($token)
                    ->timeout(8)
                    ->post($url, [
                        'message' => $message,
                        'deliver' => true,
                        'channel' => 'whatsapp',
                        'to' => $group->jid,
                        'name' => 'zakat-transfer-ingress',
                    ]);
            } catch (\Throwable $exception) {
                Log::warning('Openclaw webhook failed', [
                    'transaction_id' => $transaction->id,
                    'group_id' => $group->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
