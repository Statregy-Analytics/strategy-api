<?php

namespace App\Http\Controllers;

use App\Imports\WalletUpdateImport;
use App\Models\Investment;
use Illuminate\Http\Request;
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
        // $path = $file->storeAs('imports', now()->format('d-m-Y_H-i') . '_' . $file->getClientOriginalName());

        Excel::import(new WalletUpdateImport, $file);

        return response()->json([
            'message' => 'Investment imported successfully',
            'status'=> 200
        ], 200);
    }
}
