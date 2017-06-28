<?php

namespace App\Http\Controllers;

use App\RedisKey;
use Illuminate\Http\Request;

class RedisKeyController extends Controller
{
    use IsAuthorizedTrait;
    
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
        $this->middleware('authorize:'.__CLASS__, ['except' => ['index', 'store']]);
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function show(int $id)
    {
        return $this->success(RedisKey::find($id), 200);
    }
}
