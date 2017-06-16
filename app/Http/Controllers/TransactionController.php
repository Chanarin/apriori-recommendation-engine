<?php

namespace App\Http\Controllers;

use App\RedisKey;
use App\Transaction;
use App\User;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function show($id)
    {
        $transaction = Transaction::find($id);

        if ($transaction && RedisKey::find($transaction->redis_key_id)->user_id == Authorizer::getResourceOwnerId()) {
            return $this->success($transaction, 200);
        }

        return $this->error('Client and transaction are not associated.', 422);
    }
}
