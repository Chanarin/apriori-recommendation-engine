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
        'master_key', 'transactions_key', 'combinations_key',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'user_id',
    ];
    
    /**
     * Belongs-to-one User relationship
     * 
     * @return mixed
     */
    public function user()
    {
        return $this->belongsToOne('App\User');
    }
    
    /**
     * Has-many transactions relationship
     * 
     * @return mixed
     */
    public function transactions()
    {
        return $this->hasMany('App\Transaction');
    }
    
    public function setKeys(string $masterKey)
    {
        $this->master_key = $masterKey;
        $this->transactions_key = 'transactions-' . $masterKey . '-' . time();
        $this->combinations_key = 'combinations-' . $masterKey . '-' . time();
        
        return $this;
    }
    
    /**
     * Add a transaction to a redis key
     * 
     * @return boolean
     */
    public function addTransaction(Transaction $transaction)
    {
        return $this->transactions()->save($transaction);
    }
}