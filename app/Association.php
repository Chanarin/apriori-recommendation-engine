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
     * @var string
     */
    public $transactionKey = null;
    
    /**
     * @var string
     */
    public $combinationKey = null;
}