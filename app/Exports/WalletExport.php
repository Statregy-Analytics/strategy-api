<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class WalletExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting
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
            $currentLoan, // Dólar
            $currentBalance, // disponível para investir
            // aqui vai repetir total de investimento certo seria $currentInvestment
            $totalInvestido, // patrimônio investido (mesma origem do front)
            $totalInvestido, // investimentos (total aplicado em contratos)
            $carteira, // carteira (investido + disponível)

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
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Dólar
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Disponível para investir
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Patrimônio investido
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Investimentos
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Carteira
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Investimento Personalizado
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Expansão Patrimonial
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Reserva de emergência
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Investimento Personalizado 1 ano
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
