<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\RedisKey;

class RedisKeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
        $this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function show($redisKey)
    {
        return $this->success(RedisKey::find($redisKey), 200);
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function isAuthorized(Request $request)
    {
		$resource = "redis_keys";
		
		$redisKey = RedisKey::find($this->getArgs($request)["id"]);
		
		return $this->authorizeUser($request, $resource, $redisKey);
	}
}