<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\RedisKey;
use App\User;

class UserRedisKeyController extends Controller
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
    public function store(Request $request, $id)
    {
        $this->validate($request, [
            'master_key' => 'required|min:5',
        ]);
        
        $user = User::find($id);
        
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
    public function index($id)
    {
        return $this->success(User::find($id)->redisKeys, 200);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function isAuthorized(Request $request)
    {
		$resource = "users_redis_keys";
		
		$user = RedisKey::find($this->getArgs($request)["id"]);
		
		return $this->authorizeUser($request, $resource, $user);
	}
}