<?php

use Illuminate\Database\Seeder;

use App\Combination;
use App\Transaction;

use Illuminate\Support\Facades\Redis as Redis;

class TransactionsTableSeeder extends Seeder
{
    /**
     * @var int
     */
    const ITERATIONS = 100;
    
     /**
     * @var string constant TRANSACTION_COUNTER_KEY 
     */
    const TRANSACTION_COUNTER_KEY = 'Transactions';
    
    /**
     * @var string constant TRANSACTION_COUNTER_KEY 
     */
    const COMBINATIONS_KEY = 'Combinations';
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Redis::command('DEL', [self::COMBINATIONS_KEY, self::TRANSACTION_COUNTER_KEY]);
        
        Transaction::truncate();
        
        $combination = new Combination(self::COMBINATIONS_KEY, self::TRANSACTION_COUNTER_KEY);
        
        for($i = 0; $i < self::ITERATIONS; $i++)
        {
            $transaction = new Transaction;
            
            $transaction->items = ($items = $this->setTransaction());
            
            $transaction->save();
            
            $combination->zincrby($items, null, $transaction->id);
        }
    }
    
    /**
     * Create transaction array.
     *
     * @return array
     */
    private function setTransaction() : array
    {
        $transaction = [];
        
        for($i = 0; $i < rand(1, 10); $i++)
        {
            $transaction[] = rand(1,1000);
        }
        
        return $transaction;
    }
}