<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\RedisKey;
use App\Transaction;
use App\Combination;

class RedisKeyTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
        $this->middleware('authorize:' . __CLASS__);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function transactions($redisKey)
    {
        $redisKey = RedisKey::find($redisKey);
        
        if($redisKey && $redisKey->user_id == Authorizer::getResourceOwnerId())
        {
            return $this->success($redisKey->transactions, 200);
        }
        
        return $this->error('Client and master key are not associated.', 422);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function store(Request $request, $id)
    {
        $this->validate($request, [
            'items.*'    => 'required|integer'
        ]);
        
        $transaction = new Transaction($request->items);
        
        $redisKey = RedisKey::find($id);
        
        $redisKey->addTransaction($transaction);
        
        $this->combination($redisKey, $transaction);
        
        return $this->success($transaction, 200);
    }
    
    /**
     * @param RedisKey      $redisKey
     * @param Transaction   $transaction
     * 
     * @return void
     */
    private function combination(RedisKey $redisKey, Transaction $transaction)
    {
        $combinationKey = $redisKey->combinations_key;
        
        $transactionKey = $redisKey->transactions_key;
        
        (new Combination($combinationKey, $transactionKey))->zincrby($transaction->items, null, $transaction->id);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function isAuthorized(Request $request)
    {
		$resource = "redis_keys";
		
		$redis_key = RedisKey::find($this->getArgs($request)["id"]);
		
		return $this->authorizeUser($request, $resource, $redis_key);
	}
}