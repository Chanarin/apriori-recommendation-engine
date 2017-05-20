<?php   namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\Transaction;
use App\Combination;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-client');
    }
    
    public function index()
    {
        return response()->json([
            'transactions' => Transaction::all(),
            'client'       => Authorizer::getResourceOwnerId()
        ], 200);
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