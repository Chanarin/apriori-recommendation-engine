<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\RedisKey;
use App\Apriori;

class AprioriController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
        $this->middleware('authorize:' . __CLASS__);
    }
    
    /**
     * @param Request   $request
     * @param int       $id
     * 
     * @return mixed
     */
    public function recommend(Request $request, $id)
    {
        if(isset($request->query()['items']))
        {
            $this->validate($request,[
                'items.*' => 'required'
            ]);
            
            $redisKey = RedisKey::find($id);
            
            $apriori = new Apriori($redisKey->combinations_key, $redisKey->transactions_key);
            
            try
            {
                $items = $request->items;
                
                natsort($items);
                
                $rules = $apriori->predictions($items, true);
            }
            catch(\InvalidArgumentException $ex)
            {
                return $this->error($ex->getMessage(), 422);
            }
            
            return $this->success($rules, 200);
        }
        
        return $this->error("Ups! We couldn't retrieve any reccomendations, please check the 'items' parameter.", 422);
    }
    
    /**
     * @param Request   $request
     * @param int       $id
     * 
     * @return mixed    
     */
    public function support(Request $request, int $id) 
    {
        if(isset($request->query()['items']))
        {
            $this->validate($request,[
                'items.*' => 'required'
            ]);
            
            $redisKey = RedisKey::find($id);
            
            $apriori = new Apriori($redisKey->combinations_key, $redisKey->transactions_key);
            
            return $this->success([
                'support' => $apriori->getSupport($request->items)
            ], 200);
            
        }
        
        return $this->error("Ups! We couldn't retrieve any reccomendations, please check the 'items' parameter.", 422);
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