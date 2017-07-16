<?php

namespace App;

use Illuminate\Support\Facades\Redis as Redis;

class Combination extends Association
{
    /**
     * @var int constant MIN_SIZE
     */
    const MIN_SIZE = 2;

    /**
     * Instantiate a Combinaiton object.
     *
     * @param string $combinationKey
     * @param string $transactionKey
     */
    public function __construct(string $combinationKey, string $transactionKey)
    {
        $this->combinationKey = $combinationKey;
        $this->transactionKey = $transactionKey;
    }

    /**
     * Create the combination's subsets.
     *
     * @param array $set
     * @param int   $size
     *
     * @return array
     */
    private static function combinations(array $set = [], $size = 0) : array
    {
        if ($size == 0) {
            return [[]];
        }

        if ($set == []) {
            return [];
        }

        $prefix = [array_shift($set)];

        $result = [];

        foreach (self::combinations($set, $size - 1) as $suffix) {
            $result[] = array_merge($prefix, $suffix);
        }

        foreach (self::combinations($set, $size) as $next) {
            $result[] = $next;
        }

        return $result;
    }

    /**
     * Generate the combination subsets for each 'i' in size 'n'.
     *
     * @param array $set
     * @param int   $size
     *
     * @return array
     */
    private static function subsets(array $set, $size = null) : array
    {
        $subsets = [];

        for ($i = 1; $i <= $size; $i++) {
            $subsets[$i] = self::combinations($set, $i);
        }

        return $subsets;
    }

    /**
     * Prepares each element to be entered into the working set.
     *
     * @param array $elements
     *
     * @return array
     */
    private static function prepare(array $elements) : array
    {
        $elements = array_map('strval', $elements);

        natsort($elements);

        $set = [];

        foreach ($elements as $element) {
            if (strpos($element, self::END_SEPARATION_PATTERN) !== false || strpos($element, self::START_SEPARATION_PATTERN) !== false) {
                throw new \InvalidArgumentException(
                    'Elements cannot contain '.self::END_SEPARATION_PATTERN.' or '.self::START_SEPARATION_PATTERN
                );
            }

            $set[] = self::START_SEPARATION_PATTERN.$element.self::END_SEPARATION_PATTERN;
        }

        return $set;
    }

    /**
     * @param array    $set
     * @param int|null $size
     * @param int      $score
     *
     * @return mixed
     */
    private static function validateInputs(array $set, $size, int $score)
    {
        if ($size > count($set) || (!is_null($size) && $size < 1)) {
            throw new \InvalidArgumentException('Ups! Size must be lesser or equal to the set count & greater than 0.');
        }

        if (count($set) < 1) {
            throw new \InvalidArgumentException('Ups! The set count must be at least 0.');
        }

        if ($score == 0 || !is_numeric($score)) {
            throw new \InvalidArgumentException('Ups! The score must be an integer other than 0.');
        }
    }

    /**
     * @param int $size
     * @param int $count
     *
     * @return int
     */
    private function setSize($size, int $count) : int
    {
        if (is_null($size) && $count <= self::MAX_SIZE) {
            return $count;
        } elseif (is_null($size) && $count > self::MAX_SIZE) {
            return self::MIN_SIZE;
        }
    }

    /**
     * Removes the zero score members in the Redis zset.
     *
     * @return void
     */
    public function clean()
    {
        Redis::command('ZREMRANGEBYSCORE', [$this->transactionKey, '-inf', 0]);

        for ($i = 0; $i <= self::MAX_SIZE; $i++) {
            Redis::command('ZREMRANGEBYSCORE', [$this->combinationKey.$i, '-inf', 0]);
        }
    }

    /**
     * Removes zset keys.
     *
     * @return int
     */
    public function destroy() : int
    {
        for ($i = 0; $i <= self::MAX_SIZE; $i++) {
            Redis::command('DEL', [$this->combinationKey.$i]);
        }

        return Redis::command('DEL', [$this->transactionKey]);
    }

    /**
     * Renames zset keys.
     *
     * @param string $oldCombinationKey
     * @param string $oldTransactionKey
     *
     * @return void
     */
    public function reassign(string $oldCombinationKey, string $oldTransactionKey)
    {
        $cnt = Redis::command('EXISTS', [$oldTransactionKey]);

        if ($cnt > 0) {
            Redis::command('RENAME', [$oldTransactionKey, $this->transactionKey]);
        }

        for ($i = 0; $i <= self::MAX_SIZE; $i++) {
            $cnt = Redis::command('EXISTS', [$oldCombinationKey.$i]);

            if ($cnt > 0) {
                Redis::command('RENAME', [$oldCombinationKey.$i, $this->combinationKey.$i]);
            }
        }
    }

    /**
     * Add each imploded subset to redis using the ZINCRBY command.
     *
     * @param array $set
     * @param int   $size
     * @param mixed $txId
     * @param int   $score
     *
     * @return void
     */
    public function zincrby(array $set, int $size = null, $txId = null, int $score = 1)
    {
        self::validateInputs($set, $size, $score);

        $key = $this->combinationKey;
        $txKey = $this->transactionKey;

        $count = count($set);

        $size = $this->setSize($size, $count);

        $set = self::prepare($set);

        $subsets = self::subsets($set, $size);

        foreach ($subsets as $i => $subset) {
            foreach ($subset as $value) {
                Redis::command('ZINCRBY', [$key.$i, (int) $score, implode('', $value)]);
            }
        }

        if (is_null($txId)) {
            $txId = time();
        }

        Redis::command('ZINCRBY', [$txKey, $score, $txId]);
    }
}
