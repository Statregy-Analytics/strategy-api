<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;

class WalletExport implements FromCollection, WithHeadings, WithMapping
{
    protected $investments = [
        'Investimento Personalizado',
        'Expansão Patrimonial',
        'Reserva de emergência',
        'Investimento Personalizado 1 ano',
    ];
    public function headings(): array
    {
        return [
            'Nome',
            'Conta',
            'Dolar',
            'Disponível para investir',
            'Patrimônio investido',
            'Investimentos',
            'Carteira',
            'Investimento Personalizado',
            'Personalizado no último mês',
            'Expansão Patrimonial',
            'Patrimonial no último mês',
            'Reserva de emergência',
            'Emergência no último mês',
            'Investimento Personalizado 1 ano',
            'Investimento Personalizado 1 ano no último mês',
        ];
    }
    public function map($account): array
    {
        $wallet = $account->userWallet;
        $incomes = $account->userIncomes->keyBy('origin_name');

        return [
            $account->user->name ?? null,
            $account->person,
            $wallet->current_loan ?? null,
            null, // disponível para investir (se não existir no banco)
            null, // patrimônio investido
            null, // investimentos
            $wallet->current_investment ?? null,

            // Investimento Personalizado
            optional($incomes->get('Investimento Personalizado'))->value,
            optional($incomes->get('Investimento Personalizado'))->data_info,

            // Expansão Patrimonial
            optional($incomes->get('Expansão Patrimonial'))->value,
            optional($incomes->get('Expansão Patrimonial'))->data_info,

            // Reserva de emergência
            optional($incomes->get('Reserva de emergência'))->value,
            optional($incomes->get('Reserva de emergência'))->data_info,

            // Investimento Personalizado 1 ano
            optional($incomes->get('Investimento Personalizado 1 ano'))->value,
            optional($incomes->get('Investimento Personalizado 1 ano'))->data_info,
        ];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Account::with([
            'user',
            'userWallet',
            'userIncomes'
        ])->get();
    }
}
