<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\RedisKey;
use App\Transaction;
use App\Combination;
use App\Jobs\CombinationJob;

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
        return $this->success(RedisKey::find($redisKey)->transactions, 200);
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
        
        return $this->success("Transaction with id {$transaction->id} created successfully", 200);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function storeAsync(Request $request, $id)
    {
        $this->validate($request, [
            'items.*'    => 'required|integer'
        ]);
        
        $transaction = new Transaction($request->items);
        
        $redisKey = RedisKey::find($id);
        
        $redisKey->addTransaction($transaction);
        
        $job = new CombinationJob($redisKey, $transaction);
        
        $this->dispatch($job);
        
        return $this->success("Transaction with id {$transaction->id} created successfully", 200);
    }
    
    /**
     * @param RedisKey      $redisKey
     * @param Transaction   $transaction
     * 
     * @return void
     */
    private function combination(RedisKey $redisKey, Transaction $transaction)
    {
        (new Combination(
            $redisKey->combinations_key, 
            $redisKey->transactions_key
        ))->zincrby($transaction->items, null, $transaction->id);
    }
    
    /**
     * @param int   $id
     * @param int   $transaction_id
     * 
     * @return mixed
     */
    public function destroy(int $id, int $transaction_id)
    {
        $redisKey = RedisKey::find($id);
        
        $transaction = $redisKey->transactions()->find($transaction_id);
        
        if(!$transaction)
        {
            return $this->error("The transaction with id {$transaction_id} wasn't found.", 404);
        }
        
        $transaction->clean($redisKey);
        
        $transaction->delete();
        
        return $this->success("The transaction with id {$transaction_id} was successfully deleted.", 200);
    }
    
    /**
     * @param Request   $request
     * @param int       $id
     * @param int       $transaction_id
     * 
     * @return mixed
     */
    public function update(Request $request, int $id, int $transaction_id)
    {
        $this->validate($request, [
            'items.*'    => 'required|integer'
        ]);
        
        $redisKey = RedisKey::find($id);
        
        $transaction = $redisKey->transactions()->find($transaction_id);
        
        if(!$transaction)
        {
            return $this->error("The transaction with id {$transaction_id} wasn't found.", 404);
        }
        
        $transaction->clean($redisKey);
        
        $transaction->items = $request->items;
        
        $transaction->save();
        
        $this->combination($redisKey, $transaction);
        
        return $this->success("Transaction with id {$transaction_id} was updated successfully.", 200);
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