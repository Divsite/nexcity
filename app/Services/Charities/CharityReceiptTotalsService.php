<?php

namespace App\Services\Charities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CharityReceiptTotalsService
{
    public function totalsForQuery(Builder $query): array
    {
        $transactionIds = (clone $query)
            ->select('charity_transactions.id')
            ->distinct()
            ->pluck('charity_transactions.id')
            ->all();

        $totals = $this->totalsForTransactionIds($transactionIds);

        return [
            'total_money' => $totals['total_money'],
            'total_rice' => $totals['total_rice'],
            'count' => count($transactionIds),
        ];
    }

    public function totalsForTransactionIds(array $transactionIds): array
    {
        if (empty($transactionIds)) {
            return [
                'total_money' => 0.0,
                'total_rice' => 0.0,
            ];
        }

        $totalMoney = 0.0;
        $totalRice = 0.0;

        $totalMoney += (float) DB::table('charity_fitrah_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_fidya_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_mal_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_donation_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_alms_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');
        $totalMoney += (float) DB::table('charity_endowment_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_money');

        $totalRice += (float) DB::table('charity_fitrah_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_rice');
        $totalRice += (float) DB::table('charity_fidya_receipts')->whereIn('charity_transaction_id', $transactionIds)->sum('amount_rice');

        return [
            'total_money' => $totalMoney,
            'total_rice' => $totalRice,
        ];
    }
}
