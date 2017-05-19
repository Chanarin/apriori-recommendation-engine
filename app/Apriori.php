<?php

namespace App;

use Illuminate\Support\Facades\Redis as Redis;

class Apriori extends Association
{
    /**
     * @var int constant COUNT 
     */
    const COUNT = 100000;
    
    /**
     * Minimum relative probability of frequent transactions.
     *
     * @var float
     */
    private $confidence;
    
    /**
     * Minimum relative frequency of transactions.
     *
     * @var float
     */
    private $support;
    
    /**
     * Ratio of the observed support to that expected if subset X and 
     * subset Y were independent
     * 
     * @var float
     */
    private $lift;
    
    /**
     * Apriori constructor.
     *
     * @param float $support
     * @param float $confidence
     */
    public function __construct(string $combinationKey, string $transactionKey, float $support = 0.0, float $confidence = 0.0)
    {
        if($support < 0 || $confidence < 0)
        {
            throw new \InvalidArgumentException('The support and confidence most be greater than 0.');
        }
        
        $this->combinationKey = $combinationKey;
        $this->transactionKey = $transactionKey;
        $this->support = $support;
        $this->confidence = $confidence;
    }
    
    /**
     * Calculates support for item set $sample. Support is the relative amount 
     * of sets containing $sample in the data pool.
     *
     * @param string    $set
     * @param mixed     $min
     * @param mixed     $max
     *
     * @return float
     */
    private function support(string $set, $min = '-inf', $max = 'inf') : float
    {
        return $this->frequency($set) / Redis::command('ZCOUNT', [$this->transactionKey, $min, $max]);
    }
    
    /**
     * Counts occurrences of $set as subset in data pool.
     *
     * @param string    $key
     * @param string    $set
     *
     * @return int
     */
    private function frequency(string $set) : int
    {
        if(is_null($frequency = Redis::command('ZSCORE', [$this->combinationKey, $set])))
        {
            throw new \InvalidArgumentException('Ups!, the key or set you passed was not found.');
        }
        
        return $frequency;
    }
    
    /**
     * Returns frequent item sets only.
     *
     * @param mixed[][] $samples
     *
     * @return mixed[][]
     */
    private function frequent(array $samples) : array
    {
        return array_filter($samples, function ($entry) {
            return $this->support($entry) >= $this->support;
        });
    }
    
    /**
     * Implements the Redis ZSCAN command on the combinations subset
     * 
     * @param array     $elements
     * @param string    $key
     * @param int       $count
     * @param int       $cursor
     * 
     * @return array
     */
    private function zscan(array $elements, int $count = self::COUNT, int $cursor = 0) : array
    {
        sort($elements, 1);
        
        $smaples = null;
        
        for($i = 0; $i < count($elements); $i++)
        {
            $temp = Redis::command(
                'ZSCAN', [
                    $this->combinationKey, 
                    $cursor, 
                    'match', '*' . self::START_SEPARATION_PATTERN . $elements[$i] . self::END_SEPARATION_PATTERN . '*', 
                    'count', $count
                ]
            )[1];
            
            if($i == 0) 
            {
                $samples = $temp;
                continue;
            }
            
            $samples = array_intersect_key($samples, $temp);
        }
        
        $value = self::setString($elements, self::START_SEPARATION_PATTERN, self::END_SEPARATION_PATTERN);
        
        unset($samples[$value]);
        
        return $samples;
    }
    
    /**
     * Sets the combination string to look for
     * 
     * @param array     $elements
     * @param string    $recurring
     * 
     * @return string
     */ 
    private static function setString(array $elements, $start = '', $end = '') : string
    {
        $set = array_map('strval', $elements);
        
        sort($set, 1);
        
        $value = '';
        
        foreach($set as $element) 
        {
            $value = $value . $start . $element . $end;
        }
        
        return $value;
    }
    
    /**
     * Gets the samples associated with a given item or subset with their 
     * associated support
     * 
     * @param array     $elements
     * @param int       $count
     * @param int       $cursor
     * @param boolean   $filter
     * 
     * @return array
     */
    public function samples(array $elements, int $count = self::COUNT, int $cursor = 0, bool $filter = true) : array
    {
        $samples = [];
        
        foreach( $this->zscan($elements, $count, $cursor) as $sample => $value )
        {
            if( $filter && $this->support($sample) >= $this->support ) $samples[$sample] = $this->support($sample);
            elseif( !$filter) $samples[$sample] = $this->support($sample);
        }
        
        return $samples;
    }
    
    /**
     * Sets the prediction elements
     * 
     * @param string    $key
     * @param string    $combination
     * 
     * @return array
     */
    private function setKey(string $key, string $combination) : array
    {
        $key = $this->trimPatterns($key);
        
        $combination = $this->trimPatterns($combination);
        
        return array_values(array_diff(explode(',', $key), explode(',', $combination)));
    }
    
    /**
     * Trims the start and ending patterns of a combination string
     * 
     * @param string $combination
     * 
     * @return string
     */
    private function trimPatterns(string $combination) : string
    {
        return rtrim(
            str_replace(
                self::START_SEPARATION_PATTERN,'',str_replace(self::END_SEPARATION_PATTERN, ',', $combination)
            ), ','
        );
    }
    
    /**
     * Gets the possible assoication rules for a given element or subset
     * 
     * @param array     $samples
     * @param array     $elements
     * @param boolean   $lift
     * 
     * @return array
     */ 
    public function rules(array $samples, array $elements, bool $lift = false) : array
    {
        $string = self::setString($elements, self::START_SEPARATION_PATTERN, self::END_SEPARATION_PATTERN); 
        
        $support = $this->support($string);
        
        $rules = [];
        
        foreach(array_reverse($samples) as $key => $value)
        {
            if( $this->confidence <= ( $confidence = $value / $support ) )
            {
                if( $lift && $this->lift <= ( $lift = $value / ( $support * $this->support(str_replace($string, '', $key)) ) ) )
                {
                    $rules[] = [
                        'lift'       => $lift,
                        'confidence' => $confidence,
                        'support'    => $support,
                        'key'        => $this->setKey($key, $string),
                    ];
                    
                    continue;
                }
                
                $rules[] = [
                    'confidence' => $confidence,
                    'support'    => $support,
                    'key'        => $this->setKey($key, $string),
                ];
            }
            
        }
        
        return $rules;
    }
    
    /**
     * Predicts the possible items to be bought along with the combination entered
     * 
     * @param array     $elements
     * @param boolean   $lift
     * @param int       $cursor
     * @param int       $count
     * @param boolean   $filter
     * 
     * @return array
     */
    public function predictions(array $elements, bool $lift = false, int $count = self::COUNT, int $cursor = 0, bool $filter = true) : array
    {
        $samples = $this->samples($elements, $count, $cursor, $filter);
        
        return $this->rules($samples, $elements, $lift);
    }
}