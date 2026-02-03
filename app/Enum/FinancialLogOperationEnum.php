<?php

namespace App\Enum;

enum FinancialLogOperationEnum: string
{
    case WALLET_UPDATE = 'wallet_update';
    case CLIENT_INCOME_DELETE = 'client_income_delete';

    public static function forSelect(): array
    {
        return array_combine(
            array_column(self::cases(), 'name'),
            array_column(self::cases(), 'value'),
        );
    }
}
