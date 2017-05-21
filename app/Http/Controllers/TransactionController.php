<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\Transaction;
use App\Combination;
use App\RedisKey;
use App\User;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'master_key' => 'required|exists:redis_keys,master_key',
            'items.*'    => 'required|integer'
        ]);
        
        $redisKey = RedisKey::where('master_key', '=', $request->master_key)
                        ->where('user_id', '=', Authorizer::getResourceOwnerId())
                        ->get()
                        ->first();
                        
        if(is_null($redisKey))
        {
            return $this->error('Client and master key are not associated.', 404);
        }
        
        $transaction = new Transaction($request->items);
        
        $redisKey->addTransaction($transaction);
        
        $this->combination($redisKey, $transaction);
        
        return $this->success('Transaction added successfully.', 200);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function show($transaction)
    {
        
        $transaction = Transaction::find($transaction);
        
        if($transaction && RedisKey::find($transaction->redis_key_id)->user_id == Authorizer::getResourceOwnerId())
        {
            return $this->success($transaction, 200);
        }
        
        return $this->error('Client and transaction are not associated.', 404);
    }
    
    /**
     * @param RedisKey      $redisKey
     * @param Transaction   $transaction
     * 
     * @return void
     */
    private function combination(RedisKey $redisKey, Transaction $transaction)
    {
        (new Combination($redisKey->combinations_key, $redisKey->transactions_key))
            ->zincrby($transaction->items, null, $transaction->id);
    }
}