<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\RedisKey;
use App\User;

class RedisKeyController extends Controller
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
            'master_key' => 'required|min:5',
        ]);
        
        $user = User::find(Authorizer::getResourceOwnerId());
        
        if(RedisKey::where('user_id','=',$user->id)->where('master_key','=',$request->master_key)->get()->first())
        {
            return $this->error('Master key ' . $request->master_key . ' is already in use.', 422);
        }
       
        $user->addRedisKey((new RedisKey)->setKeys($request->master_key));
        
        return $this->success('Key created successfully.', 200);
    }
    
    /**
     * @return mixed
     */
    public function index()
    {
        return $this->success(User::find(Authorizer::getResourceOwnerId())->redisKeys, 200);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function show($redisKey)
    {
        $redisKey = RedisKey::find($redisKey);
        
        if($redisKey && $redisKey->user_id == Authorizer::getResourceOwnerId())
        {
            return $this->success($redisKey, 200);
        }
        
       return $this->error('Client and transaction are not associated.', 422);
    }
}