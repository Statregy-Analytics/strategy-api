<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIncome extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'origin_name',
        'value',
        'date_at',
        'data_info',
        'updated_at',
        'created_at',
    ];

    protected function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y H:i:s');
    }

    protected function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d/m/Y H:i:s');
    }
}
