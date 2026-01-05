<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function unauthorized()
    {
        return response()->json(
        [
        'status' => '401',
        'error' => 'Não autorizado'
        ],401);
    }

    public function isHeaderRow($row)
    {
        return is_string($row[0]) && preg_match('/[a-zA-Z', $row[0]);
    }
    public function cleanString($string)
    {
        return preg_replace('/[^0-9]/', '', $string);
    }
}
