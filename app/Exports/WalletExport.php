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
            'Personalizado 1 ano no último mês',
        ];
    }
    public function map($account): array
    {
        // Garante que estamos pegando a carteira mais recente do usuário
        $wallet = $account->userWallet()->latest('id')->first();
        $incomes = $account->userIncomes->keyBy('origin_name');

        // Valores principais da carteira seguindo a mesma lógica do front (Vuex getters)
        $currentInvestment = optional($wallet)->current_investment ?? 0; // Patrimônio investido
        $currentBalance = optional($wallet)->current_balance ?? 0; // Disponível para investir
        $currentLoan = optional($wallet)->current_loan ?? 0; // Cotação / dólar

        // Soma disponível para investir com o investido (saldo da carteira)
        $carteira = $currentInvestment + $currentBalance;

        // Total investido a partir dos contratos (mantido para a coluna "Investimentos")
        $totalInvestido = $account->userIncomes->sum(function ($income) {
            return $income->value ?? 0;
        });

        return [
            $account->user->name ?? null,
            $account->person,
            $this->formatNumber($currentLoan), // Dólar
            $this->formatNumber($currentBalance), // disponível para investir
            // aqui vai repetir total de investimento certo seria $currentInvestment
            $this->formatNumber($totalInvestido), // patrimônio investido (mesma origem do front)
            $this->formatNumber($totalInvestido), // investimentos (total aplicado em contratos)
            $this->formatNumber($carteira), // carteira (investido + disponível)

            // Investimento Personalizado
            $this->formatNumber(optional($incomes->get('Investimento Personalizado'))->value),
            optional($incomes->get('Investimento Personalizado'))->data_info,

            // Expansão Patrimonial
            $this->formatNumber(optional($incomes->get('Expansão Patrimonial'))->value),
            optional($incomes->get('Expansão Patrimonial'))->data_info,

            // Reserva de emergência
            $this->formatNumber(optional($incomes->get('Reserva de emergência'))->value),
            optional($incomes->get('Reserva de emergência'))->data_info,

            // Investimento Personalizado 1 ano
            $this->formatNumber(optional($incomes->get('Investimento Personalizado 1 ano'))->value),
            optional($incomes->get('Investimento Personalizado 1 ano'))->data_info,
        ];
    }
    private function formatNumber($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return number_format((float) $value, 2, ',', '.');
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
