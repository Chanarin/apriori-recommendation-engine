<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use App\Apriori;
use App\Combination;

class AprioriTest extends TestCase
{
    /**
     * @var string constant TRANSACTION_COUNTER_KEY 
     */
    const COMBINATIONS_KEY = 'Combinations';
    
    /**
     * @var string constant TRANSACTIONS_KEY
     */
    const TRANSACTIONS_KEY = 'Transactions';
    
    /**
     * Test the apriori constructor.
     *
     * @return void
     */
    public function test_constructor()
    {
        $this->assertInstanceOf('App\Apriori', new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY));
        
        $this->assertInstanceOf('App\Apriori', new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY, 0.5, 0.5));
    }
    
    /**
     * Test the apriori constructor invalid fail.
     *
     * @return void
     */
    public function test_constructor_fail()
    {
        $this->expectException(TypeError::class);
        
        new Apriori(1,'a');
        
        $this->expectException(TypeError::class);
        
        new Apriori('b', 2);
        
        $this->expectException(InvalidArgumentException::class);
        
        new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY, -0.5, 0.5);
    }
    
     /**
     * Set elements to perform the test.
     *
     * @return array
     */
    private function setElements()
    {
        $e1 = 1;
        $e2 = 2;
        $e3 = 3;
        $e4 = 4;
        
        (new Combination(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->zincrby([$e1,$e2,$e3,$e4]);
        
        return [$e2,$e4];
    }
    
    /**
     * Test the apriori samples generation
     *
     * @return void
     */
    public function test_samples()
    {
        $elements = $this->setElements();
        
        $this->assertTrue(count((new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->samples($elements)) > 0);

        $this->assertTrue(count((new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->samples(['a','b','c'])) == 0);
    }
    
    /**
     * Test the apriori samples generation failure
     *
     * @return void
     */
     public function test_samples_invalid_arguments_fail()
     {
        $this->expectException(TypeError::class);
         
        (new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->samples('a');
        
        $this->expectException(TypeError::class);
        
        $elements = $this->setElements();
         
        (new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->samples($elements);
        
        $this->expectException(TypeError::class);
        
        (new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->samples($elements, null);
        
        $this->expectException(TypeError::class);
        
        (new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->samples($elements, 10000, null);
        
        $this->expectException(TypeError::class);
        
        (new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY))->samples($elements, 10000, 0, null);
    }
    
    /**
     * Test the apriori rules generation
     *
     * @return void
     */
    public function test_rules()
    {
        $elements = $this->setElements();
        
        $apriori =  new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY);
        
        $samples = $apriori->samples($elements);
        
        $rules = $apriori->rules($samples, $elements, true);
        
        $this->assertArrayHasKey('confidence', $rules[0]);
        $this->assertArrayHasKey('lift', $rules[0]);
        $this->assertArrayHasKey('support', $rules[0]);
        $this->assertArrayHasKey('key', $rules[0]);
        $this->assertTrue(count($rules[0]['key']) > 0);
    }
    
    /**
     * Test the apriori rules generation
     *
     * @return void
     */
    public function test_rules_invalid_arguments_fail()
    {
        $elements = $this->setElements();
        
        $apriori =  new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY);
        
        $samples = $apriori->samples($elements);
        
        $this->expectException(TypeError::class);
        
        $apriori->rules('a',$elements);
        
        $this->expectException(TypeError::class);
        
        $apriori->rules($samples,'a');
        
        $this->expectException(TypeError::class);
        
        $apriori->rules($samples, $elements, 'a');
        
        $this->expectException(TypeError::class);
        
        $apriori->rules($samples, $elements, false);
    }
    
    /**
     * Test the apriori predictions generation
     *
     * @return void
     */
    public function test_predictions()
    {
        $elements = $this->setElements();
        
        $apriori = new Apriori(self::COMBINATIONS_KEY, self::TRANSACTIONS_KEY);
        
        $predictions = $apriori->predictions($elements);
        
        $this->assertArrayHasKey('confidence', $predictions[0]);
        $this->assertArrayHasKey('support', $predictions[0]);
        $this->assertArrayHasKey('key', $predictions[0]);
        $this->assertTrue(count($predictions[0]['key']) > 0);
    }
}