<?php

namespace App\Observers;

use App\Models\Charities\CharityTransaction;

class CharityTransactionObserver
{
    public function created(CharityTransaction $transaction): void
    {
        activity(__('messages.charity_transactions'))
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log(__('messages.charity_transactions_has_been_created', [
                'name' => $transaction->payer_name ?? '#' . $transaction->id,
            ]));
    }

    public function updated(CharityTransaction $transaction): void
    {
        activity(__('messages.charity_transactions'))
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log(__('messages.charity_transactions_has_been_updated', [
                'name' => $transaction->payer_name ?? '#' . $transaction->id,
            ]));
    }
}
