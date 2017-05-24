<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Combination;

class CombinationTest extends TestCase
{
    /**
     * @var string constant COMBINATIONS_KEY 
     */
    const COMBINATIONS_KEY = 'Combinations';
    
    /**
     * @var string constant TRANSACTIONS_KEY
     */
    const TRANSACTIONS_KEY = 'Transactions';
    
    /**
     * Create transaction array.
     *
     * @return array
     */
    public function setTransaction() : array
    {
        $transaction = [];
        
        for($i = 0; $i < rand(1, 20); $i++)
        {
            $transaction[] = rand(1,10000);
        }
        
        return $transaction;
    }
    
    /**
     * Test the combination constructor.
     *
     * @return void
     */
    public function test_constructor()
    {
        $this->assertInstanceOf('App\Combination', new Combination(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY));
    }
    
    /**
     * Test the combination constructor InvalidArgumentFailure.
     *
     * @return void
     */
    public function test_constructor_failure()
    {
        $this->expectException(TypeError::class);
        
        new Combination(null, self::TRANSACTIONS_KEY);
        
        $this->expectException(TypeError::class);
        
        new Combination(self::COMBINATIONS_KEY, null);
    }
    
    /**
     * Test adding the imploded subsets into Redis
     * 
     * @return void
     */
    public function test_zincrby()
    {
        $combination =  new Combination(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY);
        
        $transaction = $this->setTransaction();
        
        $this->assertTrue(is_null($combination->zincrby($transaction)));
    }
    
    /**
     * Test substracting the imploded subsets into Redis invalid argument fail
     * 
     * @return void
     */
    public function test_zincrby_decreasing()
    {
        $combination =  new Combination(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY);
        
        $transaction = $this->setTransaction();
        
        $this->assertTrue(is_null($combination->zincrby($transaction, null, 1)));
        
        $this->assertTrue(is_null($combination->zincrby($transaction, null, 1, -1)));
        
        $combination->clean();
    }
    
    /**
     * Test adding the imploded subsets into Redis invalid argument fail
     * 
     * @return void
     */
    public function test_zincrby_invalid_arguments_fail()
    {
        $combination =  new Combination(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY);
        
        $this->expectException(TypeError::class);
        
        $combination->zincrby('1');
        
        $transaction = $this->setTransaction();
        
        $this->expectException(InvalidArgumentException::class);
        
        $combination->zincrby($transaction, 0);
        
        $this->expectException(InvalidArgumentException::class);
        
        $combination->zincrby($transaction, count($transaction) + 1);
        
        $this->expectException(InvalidArgumentException::class);
        
        $combination->zincrby($transaction, 0);
        
        $this->expectException(InvalidArgumentException::class);
        
        $combination->zincrby($transaction, null, null, self::COMBINATIONS_KEY, 'a');
    }
    
    /**
     * Test destroying keys in Redis
     *
     * @return void
     */
    public function test_destroy()
    {
        $combination =  new Combination(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY);
        
        $this->assertTrue($combination->destroy() == 2);
        
        $this->assertTrue($combination->destroy() == 0);
    }
}
