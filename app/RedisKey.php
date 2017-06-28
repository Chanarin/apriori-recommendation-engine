<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RedisKey extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'master_key', 'transactions_key', 'combinations_key',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'user_id',
    ];

    /**
     * Belongs-to-one User relationship.
     *
     * @method belongsToOne(App\User)
     * 
     * @return mixed
     */
    public function user()
    {
        return $this->belongsToOne('App\User');
    }

    /**
     * Has-many transactions relationship.
     *
     * @return mixed
     */
    public function transactions()
    {
        return $this->hasMany('App\Transaction');
    }

    /**
     * @param string $masterKey
     *
     * @return RedisKey
     */
    public function setKeys(string $masterKey) : RedisKey
    {
        $this->master_key = $masterKey;
        $this->transactions_key = 'transactions-'.$masterKey.'-'.time();
        $this->combinations_key = 'combinations-'.$masterKey.'-'.time();

        return $this;
    }

    /**
     * Add a transaction to a redis key.
     *
     * @return bool
     */
    public function addTransaction(Transaction $transaction)
    {
        return $this->transactions()->save($transaction);
    }

    /**
     * Removes keys from Redis.
     *
     * @return RedisKey
     */
    public function remove() : RedisKey
    {
        (new Combination($this->combinations_key, $this->transactions_key))->destroy();

        return $this;
    }

    /**
     * Reassign names to Redis keys.
     *
     * @param string $oldCombinationKey
     * @param string $oldTransactionKey
     *
     * @return RedisKey
     */
    public function reassign(string $oldCombinationKey, string  $oldTransactionKey) : RedisKey
    {
        (new Combination($this->combinations_key, $this->transactions_key))->reassign($oldCombinationKey, $oldTransactionKey);

        return $this;
    }

    /**
     * Deletes a RedisKey and the transactions associated with it.
     *
     * @return mixed
     */
    public function delete()
    {
        $this->transactions()->delete();

        return parent::delete();
    }
}
