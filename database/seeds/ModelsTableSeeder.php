<?php

use App\Combination;
use App\RedisKey;
use App\Transaction;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Redis as Redis;

class ModelsTableSeeder extends Seeder
{
    /**
     * @var int
     */
    const ITERATIONS = 10;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Faker\Generator $faker)
    {
        $this->resetTables();

        $hasher = app()->make('hash');

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $user = (new User())::create([
                'client'   => 'id'.$i,
                'secret'   => 'secret'.$i,
                'password' => $hasher->make('password'),
                'name'     => $faker->name,
                'email'    => $faker->email,
            ]);

            $redisKey = $user->addRedisKey($this->createRedisKey($i));

            $this->transactions($redisKey);
        }
    }

    /**
     * Creates transactions and combinations.
     *
     * @param object $redisKey
     *
     * @return void
     */
    private function transactions(RedisKey $redisKey)
    {
        $combination = new Combination($redisKey->combinations_key, $redisKey->transactions_key);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $transaction = new Transaction();

            $transaction->items = ($items = $this->setTransaction());

            $redisKey->addTransaction($transaction);

            $combination->zincrby($items, null, $transaction->id);
        }
    }

    /**
     * Truncate all tables and Redis Keys.
     *
     * @return void
     */
    private function resetTables()
    {
        \Schema::disableForeignKeyConstraints();

        User::truncate();

        RedisKey::truncate();

        Transaction::truncate();

        \Schema::enableForeignKeyConstraints();

        Redis::command('FLUSHALL');
    }

    /**
     * Create Redis keys.
     *
     * @param int $i
     *
     * @return object RedisKey
     */
    private function createRedisKey(int $i) : RedisKey
    {
        $redisKey = new RedisKey();

        $redisKey->master_key = 'master-'.$i;

        $redisKey->transactions_key = 'transactions-'.$redisKey->master_key.'-'.time();

        $redisKey->combinations_key = 'combinations-'.$redisKey->master_key.'-'.time();

        return $redisKey;
    }

    /**
     * Create transaction array.
     *
     * @return array
     */
    private function setTransaction() : array
    {
        $transaction = [];

        for ($i = 0; $i < rand(1, 10); $i++) {
            $transaction[] = rand(1, 1000);
        }

        return $transaction;
    }
}
