<?php

namespace App\Http\Controllers;

use App\RedisKey;
use Illuminate\Http\Request;

trait IsAuthorizedTrait
{
    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function isAuthorized(Request $request)
    {
        $resource = 'redis_keys';

        $redisKey = RedisKey::find($this->getArgs($request)['id']);

        return $this->authorizeUser($request, $resource, $redisKey);
    }
}
