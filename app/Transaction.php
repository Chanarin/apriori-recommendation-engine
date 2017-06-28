<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'items', 'id',
    ];

    /**
     * The attributes that are going to be entered as JSON in the database.
     *
     * @var array
     */
    protected $casts = [
        'items' => 'array',
    ];

    /**
     * Instantiate a Transaction.
     */
    public function __construct(array $items = [])
    {
        if (count($items)) {
            $this->items = array_unique($items);
        }
    }

    /**
     * Belongs-to-one Key relationship.
     * 
     * @method belongsToOne(App\RedisKey)
     * 
     * @return mixed
     */
    public function redisKey()
    {
        return $this->belongsToOne('App\RedisKey');
    }

    /**
     * Clean transaction combinations in Redis.
     *
     * @param RedisKey $redisKey
     *
     * @return void
     */
    public function clean(RedisKey $redisKey)
    {
        $combination = new Combination($redisKey->combinations_key, $redisKey->transactions_key);

        $combination->zincrby($this->items, null, $this->id, -1);

        $combination->clean();
    }
}
