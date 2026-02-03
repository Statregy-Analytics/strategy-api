<?php

namespace App\Observers;

use App\Enum\FinancialLogOperationEnum;
use App\Models\FinancialLog;
use App\Models\UserWallet;
use Illuminate\Support\Facades\Auth;

class UserWalletObserver
{
    /**
     * Handle the UserWallet "created" event.
     */
    public function created(UserWallet $userWallet): void
    {
        //
    }

    /**
     * Handle the UserWallet "updated" event.
     */
    public function updated(UserWallet $userWallet): void
    {
        $original = $userWallet->getOriginal();
        $changes = $userWallet->getChanges();

        $monetaryFields = [
            'current_balance',
            'current_investment',
            'current_loan',
        ];

        $hasMonetaryChange = false;
        $meta = [
            'fields' => [],
        ];

        foreach ($monetaryFields as $field) {
            if (array_key_exists($field, $changes)) {
                $hasMonetaryChange = true;
                $meta['fields'][$field] = [
                    'before' => $original[$field] ?? null,
                    'after' => $changes[$field],
                    'delta' => isset($original[$field]) ? $changes[$field] - $original[$field] : null,
                ];
            }
        }

        if (! $hasMonetaryChange) {
            return;
        }

        $amount = null;
        if (array_key_exists('current_balance', $changes) && isset($original['current_balance'])) {
            $amount = $changes['current_balance'] - $original['current_balance'];
        }

        FinancialLog::create([
            'user_wallet_id' => $userWallet->id,
            'user_id' => $userWallet->user_id,
            'operation' => FinancialLogOperationEnum::WALLET_UPDATE->value,
            'amount' => $amount,
            'balance_before' => $original['current_balance'] ?? null,
            'balance_after' => $changes['current_balance'] ?? ($original['current_balance'] ?? null),
            'meta' => array_merge($meta, [
                'actor_id' => Auth::id(),
                'performed_at' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Handle the UserWallet "deleted" event.
     */
    public function deleted(UserWallet $userWallet): void
    {
        //
    }

    /**
     * Handle the UserWallet "restored" event.
     */
    public function restored(UserWallet $userWallet): void
    {
        //
    }

    /**
     * Handle the UserWallet "force deleted" event.
     */
    public function forceDeleted(UserWallet $userWallet): void
    {
        //
    }
}
