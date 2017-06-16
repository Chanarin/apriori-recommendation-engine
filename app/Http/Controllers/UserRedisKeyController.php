<?php

namespace App\Http\Controllers;

use App\RedisKey;
use App\User;
use Illuminate\Http\Request;

class UserRedisKeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
        $this->middleware('authorize:'.__CLASS__);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request, int $id)
    {
        $this->validate($request, [
            'master_key' => 'required|min:5',
        ]);

        $user = User::find($id);

        if (RedisKey::where('user_id', '=', $user->id)->where('master_key', '=', $request->master_key)->get()->first()) {
            return $this->error('Master key '.$request->master_key.' is already in use.', 422);
        }

        $redisKey = new RedisKey();

        $user->addRedisKey($redisKey->setKeys($request->master_key));

        return $this->success([
            'message' => "Key with id {$redisKey->id} created successfully.",
            'data'    => $redisKey,
        ], 200);
    }

    /**
     * @param int     $id
     * @param Request $request
     *
     * @return mixed
     */
    public function index(int $id, Request $request)
    {
        $redisKeys = User::find($id)->redisKeys()->paginate(self::LIMIT);

        return $this->success($this->respondWithPagination($redisKeys, $request->get('access_token')), 200);
    }

    /**
     * @param int $id
     * @param int $redis_key_id
     *
     * @return mixed
     */
    public function destroy(int $id, int  $redis_key_id)
    {
        $user = User::find($id);

        if (is_null($user)) {
            return $this->error("You aren't allowed to perform the requested action", 403);
        }

        $redisKey = $user->redisKeys()->find($redis_key_id);

        $redisKey->remove()->delete();

        return $this->success("The Redis key with id {$redis_key_id} has been removed from user {$id}", 200);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @param int     $redis_key_id
     *
     * @return mixed
     */
    public function update(Request $request, int $id, int $redis_key_id)
    {
        $this->validate($request, [
            'master_key' => 'required|min:5',
        ]);

        $user = User::find($id);

        if (is_null($user)) {
            return $this->error("You aren't allowed to perform the requested action", 403);
        }

        if (RedisKey::where('user_id', '=', $id)->where('master_key', '=', $request->master_key)->get()->first()) {
            return $this->error('Master key '.$request->master_key.' is already in use.', 422);
        }

        $redisKey = $user->redisKeys()->find($redis_key_id);

        $oldCombinationKey = $redisKey->combinations_key;
        $oldTransactionKey = $redisKey->transactions_key;

        $redisKey->setKeys($request->master_key)
                 ->reassign($oldCombinationKey, $oldTransactionKey)
                 ->save();

        return $this->success([
            'message' => "Key with id {$redisKey->id} has been updated.",
            'data'    => $redisKey,
        ], 200);
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function isAuthorized(Request $request)
    {
        if (isset($this->getArgs($request)['redis_key_id'])) {
            
            $resource = 'redis_keys';
            
            $id = $this->getArgs($request)['redis_key_id'];
            
            $redisKey = RedisKey::find($id);

            return $this->authorizeUser($request, $resource, $redisKey);
        }

        $resource = 'users_redis_keys';
        
        $id = $this->getArgs($request)['id'];
        
        $user = User::find($id);

        return $this->authorizeUser($request, $resource, $user);
    }
}
