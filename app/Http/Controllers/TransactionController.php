<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Transaction;
use App\Combination;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::all();
        
        return response()->json(['transactions' => $transactions], 200);
    }
    
    public function store(Request $request)
    {
        $this->validate($request, [
            'transactions_key' => 'required|min:5',
            'combinations_key' => 'required|min:5',
            'items.*'          => 'required|integer'
        ]);
        
        $transaction = Transaction::create([
            'items' => $request->items
        ]);
        
        (new Combination($request->combinations_key, $request->transactions_key))
            ->zincrby($transaction->items, null, $transaction->id);
        
        return $this->createSuccessResponse('SUCCESS', 200);
    }
}