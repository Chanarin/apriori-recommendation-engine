<?php

namespace App\Http\Controllers;

use App\RedisKey;
use App\Transaction;
use App\User;
use Illuminate\Http\Request;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth');
        $this->middleware('oauth-user');
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function show($transaction)
    {
        $transaction = Transaction::find($transaction);

        if ($transaction && RedisKey::find($transaction->redis_key_id)->user_id == Authorizer::getResourceOwnerId()) {
            return $this->success($transaction, 200);
        }

        return $this->error('Client and transaction are not associated.', 422);
    }
}
