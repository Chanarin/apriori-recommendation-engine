<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\RedisKey;

class RedisKeyTransactionController extends Controller
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
    public function index($redisKey)
    {
        $redisKey = RedisKey::where($redisKey);
        
        if($redisKey && $redisKey->user_id == Authorizer::getResourceOwnerId())
        {
            return $this->success($redisKey->transactions, 200);
        }
        
        return $this->error('Client and master key are not associated.', 422);
    }
}