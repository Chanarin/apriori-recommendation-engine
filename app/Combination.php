<?php

namespace App;

use Illuminate\Support\Facades\Redis as Redis;

class Combination extends Association
{
    /**
     * @var int constant MAX_SIZE 
     */
    const MAX_SIZE = 3;
    
    public function __construct(string $combinationKey, string $transactionKey)
    {
        $this->combinationKey  = $combinationKey;
        $this->transactionKey = $transactionKey;
    }
    
    /**
     * Create the combination's subsets
     * 
     * @param array   $set
     * @param int     $size   
     * 
     * @return array
     */
    private static function combinations(array $set = [], $size = 0) : array
    {
        if ( $size == 0 ) return [[]];
     
        if ( $set == [] ) return [];
     
        $prefix = [ array_shift($set) ];
     
        $result = [];
     
        foreach ( self::combinations($set, $size - 1) as $suffix ) 
        {
            $result[] = array_merge($prefix, $suffix);
        }
     
        foreach ( self::combinations($set, $size) as $next ) 
        {
            $result[] = $next;
        }
     
        return $result;
    }
    
    /**
     * Generate the combination subsets for each 'i' in size 'n'
     * 
     * @param array $set
     * @param int   $size
     * 
     * @return array
     */
    private static function subsets(array $set, $size = null) : array
    {
        $subsets = array();
        
        for( $i = 1; $i <= $size; $i++ )
        {
            
            $subsets[$i] = self::combinations($set, $i);
        }
        
        return $subsets;
    }
    
    /**
     * Prepares each element to be entered into the working set
     * 
     * @param array     $elements
     * 
     * @return array
     */
    private static function prepare(array $elements) : array
    {
        $elements = array_map('strval', $elements);
        
        sort($elements,1);
        
        $set = [];
        
        foreach($elements as $element)
        {
            if(strpos($element, self::END_SEPARATION_PATTERN) !== false || strpos($element, self::START_SEPARATION_PATTERN) !== false)
            {
                throw new \InvalidArgumentException(
                    'Elements cannot contain ' . self::END_SEPARATION_PATTERN . ' or ' . self::START_SEPARATION_PATTERN
                );
            }
            
            $set[] = self::START_SEPARATION_PATTERN . $element . self::END_SEPARATION_PATTERN;
        }
        
        return $set;
    }
    
    private static function validateInputs(array $set, $size, $score)
    {
        if( $size > count($set) || ( !is_null($size) && $size < 1 ) )
        {
            throw new \InvalidArgumentException('Ups! Size must be lesser or equal to the set count & greater than 0.');
        }
        
        if( count($set) < 1 )
        {
            throw new \InvalidArgumentException('Ups! The set count must be at least 0.');
        }
        
        if( $score <= 0 || !is_numeric($score) )
        {
            throw new \InvalidArgumentException('Ups! The score must be a positive integer.');
        }
    }
    
    /**
     * Add each imploded subset to redis using the ZINCRBY command
     * 
     * @param array     $set
     * @param int       $size
     * @param mixed     $txId
     * @param string    $key
     * @param int       $score
     * 
     * @return void
     */
    public function zincrby(array $set, $size = null, $txId = null, $score = 1)
    {
        self::validateInputs($set, $size, $score);
        
        $key = $this->combinationKey;
        $txKey = $this->transactionKey;
        
        $count = count($set);
        
        if( is_null($size) && $count <= self::MAX_SIZE ) $size = $count;
        elseif( is_null($size) &&  $count > self::MAX_SIZE ) $size = self::MAX_SIZE;
        
        $set = self::prepare($set);
        
        $subsets = self::subsets($set, $size);
        
        foreach($subsets as $subset)
        {
            foreach($subset as $value)
            {
                Redis::command('ZINCRBY', [$key, (int) $score, implode('', $value)]);
            }
        }
        
        if(is_null($txId)) $txId = time(); 
        
        Redis::command('ZINCRBY', [$txKey, 1, $txId] );
    }
}