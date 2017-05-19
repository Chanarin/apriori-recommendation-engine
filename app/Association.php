<?php 

namespace App;

abstract class Association
{
    /**
     * @var string constant START_SEPARATION_PATTERN 
     */
    const START_SEPARATION_PATTERN = '^|^';
    
    /**
     * @var string constant END_SEPARATION_PATTERN 
     */
    const END_SEPARATION_PATTERN = '°|°';
    
    /**
     * @var string constant TRANSACTION_COUNTER_KEY 
     */
    const TRANSACTION_COUNTER_KEY = 'Transactions';
    
    /**
     * @var string constant TRANSACTION_COUNTER_KEY 
     */
    const COMBINATIONS_KEY = 'Combinations';
    
    /**
     * @var string
     */
    public $transactionKey = null;
    
    /**
     * @var string
     */
    public $combinationKey = null;
    /*
     |
     | Note:
     |
     | For testing either change the names of the TRANSACTION_COUNTER_KEY
     | and the COMBINATIONS_KEY or simply truncate Redis after you are
     | done testing.
     |
     */
}