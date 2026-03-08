<?php

namespace App\Services\Notifications;

use App\Models\Charities\CharityTransaction;
use App\Models\Organizations\OrganizationWhatsappGroup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Cknow\Money\Money;
use Throwable;

class OpenclawWebhookService
{
    public function sendTransferNotification(CharityTransaction $transaction): void
    {
        $transaction->loadMissing([
            'charityType.source',
            'paymentMethod.bank',
            'receivedBy',
            'fitrahReceipt',
            'fidyaReceipt',
            'malReceipt',
            'donationReceipt',
            'almsReceipt',
            'endowmentReceipt',
        ]);

        $url = config('services.openclaw.webhook_url');
        $token = config('services.openclaw.webhook_token');

        if (empty($url) || empty($token)) {
            Log::warning('Openclaw webhook config missing', [
                'transaction_id' => $transaction->id,
                'organization_id' => $transaction->organization_id,
                'has_url' => ! empty($url),
                'has_token' => ! empty($token),
            ]);
            return;
        }

        $groups = OrganizationWhatsappGroup::query()
            ->where('organization_id', $transaction->organization_id)
            ->orderBy('id')
            ->get();

        if ($groups->isEmpty()) {
            Log::warning('Openclaw webhook groups missing', [
                'transaction_id' => $transaction->id,
                'organization_id' => $transaction->organization_id,
            ]);
            return;
        }

        $payment = $transaction->paymentMethod;
        $charityType = $transaction->charityType?->source?->name ?? '-';
        $methodLabel = CharityTransaction::paymentMethodLabels()[$transaction->payment_method] ?? $transaction->payment_method;
        $moneyAmount = (float) ($transaction->total_money ?? 0);
        if ($moneyAmount <= 0) {
            $moneyAmount = $transaction->detailMoneyAmount();
        }

        $riceAmount = (float) ($transaction->total_rice ?? 0);
        if ($riceAmount <= 0) {
            $riceAmount = $transaction->detailRiceAmount();
        }

        $moneyLabel = Money::IDR($moneyAmount)->format(app()->getLocale());

        $bankName = $payment?->bank?->name
            ?? $payment?->account_name
            ?? '-';
        $accountName = $payment?->account_name ?? '-';
        $accountNumber = $payment?->account_number ?? '-';
        $timeLabel = optional($transaction->created_at)->format('d/m/Y H:i');

        $message = implode("\n", [
            "Transaksi zakat masuk:",
            "Nama Muzakki: {$transaction->payer_name}",
            "Telepon: " . ($transaction->payer_phone ?: '-'),
            "Petugas: " . ($transaction->receivedBy?->name ?? '-'),
            "Jenis: {$charityType}",
            "Nominal: {$moneyLabel}",
            "Metode: {$methodLabel}",
            "Bank: {$bankName}",
            "Nama Rekening: {$accountName}",
            "Ref: {$accountNumber}",
            "Waktu: {$timeLabel}",
            "Sumber: Sistem Zakat Nexcity",
        ]);

        foreach ($groups as $group) {
            try {
                $payload = [
                    'message' => $message,
                    'deliver' => true,
                    'channel' => 'whatsapp',
                    'to' => $group->jid,
                    'name' => 'zakat-transfer-ingress',
                ];

                Log::info('Openclaw webhook payload', [
                    'transaction_id' => $transaction->id,
                    'group_id' => $group->id,
                    'payload' => $payload,
                ]);

                Http::withToken($token)
                    ->timeout(8)
                    ->post($url, $payload);
            } catch (Throwable $exception) {
                Log::warning('Openclaw webhook failed', [
                    'transaction_id' => $transaction->id,
                    'group_id' => $group->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
