<?php

namespace App\Services;

use App\Enum\RoleEnum;
use App\Enum\FinancialLogOperationEnum;
use App\Models\Investment;
use App\Models\FinancialLog;
use App\Models\User;
use App\Models\UserIncome;
use App\Models\UserInvestment;
use App\Models\UserWallet;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientServices
{
    public function get(int $user):User
    {
        $response  = User::where('id', $user)->with(['account', 'userWallet', 'userBankAccounts', 'UserIncomes'])->first();
        return $response;
    }
    public function getAll():Collection
    {
        $response = DB::table('users')
        ->join('user_wallets', 'user_wallets.user_id', '=', 'users.id')
        ->join('accounts', 'accounts.user_id', '=', 'users.id')
        ->leftJoin('user_investments', 'user_investments.user_id', '=', 'users.id')
        ->leftJoin('investments', 'investments.id', '=', 'user_investments.investment_id')
        ->select(
            'users.id',
            'users.name',
            'users.email',
            DB::raw('CONCAT("'.env("APP_URL").'/storage/users/'.'",accounts.avatar) as avatar'),
            // 'accounts.notifications as notification',
            DB::raw('DATE_FORMAT(accounts.birthday, "%d/%m/%Y") as birthday'),
            'user_wallets.current_balance as balance',
            'user_wallets.current_loan as loan',
            'user_wallets.current_investment as current_investment',
            DB::raw(
                'CASE
                    WHEN user_investments.investment_id IS NULL THEN "Sem investimento"
                    ELSE investments.name
                END as investment')
            // DB::raw('COALESCE(investments.name, "Sem investimento") as investment_name')
            // DB::raw('CASE WHEN user_investments.investment_id IS NULL THEN NULL ELSE COALESCE(investments.name, "Sem investimento") END as investment_name')
        )
        ->groupBy(
            'users.id',
            'users.name',
            'users.email',
            'avatar',
            'notifications',
            'birthday',
            'balance',
            'loan',
            'current_investment',
            'investment'
        )
        ->where('role_id', RoleEnum::Client)
        ->get();
        return $response;
    }

    public function addInvestimentUser(int $user, int $investment, bool $transiction = false)
    {
        try{
            DB::beginTransaction();
            // $inve = new UserInvestment();
            // $inve->user_id = $user;
            // $inve->investment_id = $investment;
            // $inve->saveOrFail();
            $inve = DB::table('user_investments')
                ->updateOrInsert(
                    ['user_id' => $user],
                    ['investment_id' => $investment]
                );

            if($transiction && $inve){
                $getWallet = UserWallet::where('user_id', $user)->first();
                Log::info('Foi transferido valor para investmento do ID.'.json_decode($getWallet->user_id));
                $value_add = $getWallet->current_balance;
                $getWallet->current_investment+=$value_add;
                $getWallet->current_balance = 0;
                $getWallet->updateOrFail();
            }

            DB::commit();
            return response()->json([
                'message'=> 'Será adicionado o rendimento nos investimentos do usuário.',
                'status'=> 200
            ], 200);
        }catch( Exception $e){
            Log::error('exception ->'.$e);
            DB::rollBack();
            return response()->json([
                'message' => 'Erro na atualização!',
                'exception' => $e,
                'status'=> 500
            ], 500);
        }
    }

    public function deleteIncomeUser(int $user, int $incomeId)
    {
        try{
            DB::beginTransaction();

            $income = UserIncome::where('id', $incomeId)
                ->where('user_id', $user)
                ->first();
            if (! $income) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Nenhum rendimento encontrado para este usuário.',
                    'status' => 404,
                ], 404);
            }

            $incomeValue = (float) $income->value;

            $wallet = UserWallet::where('user_id', $user)->first();

            if ($wallet) {
                $investmentBefore = (float) $wallet->current_investment;
                $investmentAfter = max(0, $investmentBefore - $incomeValue);

                $wallet->current_investment = $investmentAfter;
                $wallet->updateOrFail();

                FinancialLog::create([
                    'user_wallet_id' => $wallet->id,
                    'user_id' => $user,
                    'operation' => FinancialLogOperationEnum::CLIENT_INCOME_DELETE->value,
                    'amount' => $incomeValue,
                    'balance_before' => $wallet->current_balance,
                    'balance_after' => $wallet->current_balance,
                    'meta' => [
                        'income_id' => $income->id,
                        'income_origin_name' => $income->origin_name,
                        'income_date_at' => $income->date_at,
                        'income_value' => $incomeValue,
                        'current_investment_before' => $investmentBefore,
                        'current_investment_after' => $investmentAfter,
                        'actor_id' => Auth::id(),
                        'performed_at' => now()->toISOString(),
                    ],
                ]);
            }

            $income->delete();

            DB::commit();

            return response()->json([
                'message'=> 'Rendimento do usuário removido com sucesso.',
                'status'=> 200
            ], 200);
        }catch( Exception $e){
            Log::error('exception ->'.$e);
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao remover investimento do usuário!',
                'exception' => $e,
                'status'=> 500
            ], 500);
        }
    }
    public function deleteIncomes(int $user, $income)
    {

    }
}
