<?php

namespace App\Jobs;

use App\Combination;
use App\RedisKey;
use App\Transaction;

class CombinationJob extends Job
{
    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var RedisKey
     */
    private $redisKey;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RedisKey $redisKey, Transaction $transaction)
    {
        $this->transaction = $transaction;
        $this->redisKey = $redisKey;
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
