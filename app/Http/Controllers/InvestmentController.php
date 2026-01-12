<?php

namespace App\Http\Controllers;

use App\Exports\WalletExport;
use App\Imports\WalletUpdateImport;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class InvestmentController extends Controller
{
    public function store()
    {
        // $investment = Investment::with(['investmentPerformances'])->get();
        $investment = Investment::get();

        return response()->json([
            'investment' => $investment,
            'status'=> 200
        ], 200);
    }

    public function importInvestment(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $file = $request->file('file');
        $file->storeAs('imports', now()->format('d-m-Y_H-i') . '_' . $file->getClientOriginalName());
        $import = new WalletUpdateImport();
        Excel::import($import, $file);
        if ($import->failures()->isNotEmpty()) {
            return response()->json([
                'errors' => $import->failures()
            ], 422);
        }
        return response()->json([
            'message' => 'Investimentos importados com sucesso!',
            'status'=> 200
        ], 200);
    }

    public function exportInvestment()
    {
        $fileName = 'wallet-export-' . now()->format('d-m-Y_H-i') . '.xlsx';
        try {
            $stored = Excel::store(new WalletExport(), $fileName, 'public');

            if (!$stored || !Storage::disk('public')->exists($fileName)) {
                Log::error('Falha ao gerar exportação da carteira', [
                    'file' => $fileName,
                    'stored' => $stored,
                ]);

                return response()->json([
                    'message' => 'Erro ao gerar o arquivo de exportação.',
                ], 500);
            }

            return response()->json([
                'download_url' => Storage::disk('public')->url($fileName),
                'file_name' => $fileName,
            ]);
        } catch (\Throwable $e) {
            Log::error('Exceção ao gerar exportação da carteira', [
                'file' => $fileName,
                'exception' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro inesperado ao gerar o arquivo de exportação.',
            ], 500);
        }
    }
    // public function importInvestment2(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls,csv'
    //     ]);

    //     $file = $request->file('file');

    //     try{
    //         $rows = MaatwebsiteExcel::toArray([], $file);

    //         if(empty($rows) || count($rows[0]) < 1){
    //             return response()->json([
    //                 'message' => 'O arquivo deve conter pelo menos uma linha de dados',
    //                 'status' => 422
    //             ], 422);
    //         }

    //         $startRow = 0;
    //         $rules =  new StoreInvestmentRquest();
    //         if($this->isHeaderRow($rows[0][0])){
    //             $startRow = 1;
    //         }

    //         for($i = $startRow; $i< count($rows[0]); $i++){
    //             if(empty(array_filter($rows[0][$i]))){
    //                 continue;
    //             }

    //             $row = $rows[0][$i];
    //             $lineNumber = $i +1;

    //             $mappedRow = [
    //                 'nome'                                   => $row[0]  ?? null,
    //                 'conta'                                  => $this->cleanString($row[1] ?? null),
    //                 'dolar'                                  => $row[2]  ?? null,
    //                 'disponivel_para_investir'               => $row[3]  ?? null,
    //                 'patrimonio_investido'                   => $row[4]  ?? null,
    //                 'investimentos'                          => $row[5]  ?? null,
    //                 'carteira'                               => $row[6]  ?? null,
    //                 'investimento_personalizado'             => $row[7]  ?? null,
    //                 'personalizado_ultimo_mes'               => $row[8]  ?? null,
    //                 'expansao_patrimonial'                   => $row[9]  ?? null,
    //                 'patrimonial_ultimo_mes'                 => $row[10] ?? null,
    //                 'reserva_emergencia'                     => $row[11] ?? null,
    //                 'emergencia_ultimo_mes'                  => $row[12] ?? null,
    //                 'investimento_personalizado_1_ano'       => $row[13] ?? null,
    //                 'investimento_personalizado_1_ano_mes'   => $row[14] ?? null,
    //             ];


    //             $validator = Validator::make($mappedRow, $rules->rules());

    //             if($validator->fails()) {
    //                 $erros['Linha'.$lineNumber] = $validator->errors()->all();

    //                 return response()->json([
    //                     $erros
    //                 ], 402);
    //             }
    //         }
    //         $file->storeAs('imports', now()->format('d-m-Y_H-i') . '_' . $file->getClientOriginalName());

    //         Excel::import(new WalletUpdateImport, $file);

    //         return response()->json([
    //             'message' => 'Investment imported successfully',
    //             'status'=> 200
    //         ], 200);

    //     }catch(Exception $e){
    //         return response()->json([
    //             'message' => 'errro ao processar o Arquivo'. $e->getMessage(),
    //         ], 500);
    //     }
    // }
}
