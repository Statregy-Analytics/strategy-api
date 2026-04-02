<?php

namespace App\Imports;

use App\Http\Requests\Investment\StoreInvestmentRquest;
use App\Models\Account;
use App\Models\UserIncome;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WalletUpdateImport implements ToCollection, WithHeadingRow, WithChunkReading, WithValidation, SkipsEmptyRows, WithCalculatedFormulas
{
    use SkipsFailures;

    public function rules() :array
    {
        return (new StoreInvestmentRquest())->rules();
    }

    protected $chunk = 1000;
    /**
     * @var array
     */
    public $investments =  [
        [
            'name' => 'Investimento Personalizado',
            'value' => 'investimento_personalizado',
            'data_info' => 'personalizado_no_ultimo_mes',
        ],
        [
            'name' => 'Expansão Patrimonial',
            'value' => 'expansao_patrimonial',
            'data_info' => 'patrimonial_no_ultimo_mes',
        ],
        [
            'name' => 'Reserva de emergência',
            'value' => 'reserva_de_emergencia',
            'data_info' => 'emergencia_no_ultimo_mes',
        ],
        [
            'name' => 'Investimento Personalizado 1 ano',
            'value' => 'investimento_personalizado_1_ano',
            'data_info' => 'personalizado_1_ano_no_ultimo_mes',
        ]
    ];
    public function chunkSize(): int
    {
        return $this->chunk;
    }
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            if(Account::where('person', $row['conta'])->exists()){
                $account = Account::where('person', $row['conta'])->first();
                Log::info('Account found: ' . $account);

                $currentBalance = $this->toDecimal($row['disponivel_para_investir'] ?? null);
                $currentInvestment = $this->toDecimal($row['carteira'] ?? null);
                $currentLoan = $this->toDecimal($row['dolar'] ?? null);

                $account->userWallet()->update([
                    'current_balance' => $currentBalance ?? $account->userWallet->current_balance,
                    'current_investment' => $currentInvestment ?? $account->userWallet->current_investment,
                    'updated_at' => now(),
                    'current_loan' => $currentLoan ?? $account->userWallet->current_loan,
                ]);

                foreach($this->investments as $investment) {
                    $value = $this->toDecimal($row[$investment['value']] ?? null);
                    $dataInfo = $this->toDecimal($row[$investment['data_info']] ?? null);

                    if($value !== null){
                        Log::info('row: ' . $row);
                        if($account->userIncomes()->where('origin_name', $investment['name'])->exists()){
                            UserIncome::updateOrCreate(
                                [
                                    'user_id' => $account->user_id,
                                    'origin_name' => $investment['name'],
                                ],
                                [
                                    'value' => $value,
                                    'data_info' => $dataInfo,
                                    'updated_at' => now(),
                                ]
                            );

                            Log::info('Updating investment data for user: ' . $account->user_id);
                        } else {
                            UserIncome::create([
                                'user_id' => $account->user_id,
                                'origin_name' => $investment['name'],
                                'value' => $value,
                                'data_info' => $dataInfo,
                                'created_at' => now(),
                                'date_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                    }
                }
            }
        }
    }

    private function toDecimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || str_starts_with($value, '=')) {
            return null;
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', $value);
        if ($normalized === null || $normalized === '') {
            return null;
        }

        $commaPos = strrpos($normalized, ',');
        $dotPos = strrpos($normalized, '.');

        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($commaPos !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
