<?php

namespace App\Jobs;

use App\RedisKey;
use App\Transaction;
use App\Combination;

class CombinationJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RedisKey $redisKey, Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->redisKey    = $redisKey;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new Combination(
            $this->redisKey->combinations_key, 
            $this->redisKey->transactions_key
        ))->zincrby($this->transaction->items, null, $this->transaction->id);
    }
}