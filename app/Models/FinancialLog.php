<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_wallet_id',
        'user_id',
        'operation',
        'amount',
        'balance_before',
        'balance_after',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
