<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Support\Facades\Hash;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'client',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'password', 'secret'
    ];
    
    /**
     * Has-many transactions relationship
     * 
     * @return mixed
     */
    public function redisKeys()
    {
        return $this->hasMany('App\RedisKey');
    }
    
    /**
     * Add a transaction to a redis key
     * 
     * @return boolean
     */
    public function addRedisKey(RedisKey $redisKey)
    {
        return $this->redisKeys()->save($redisKey);
    }
    
    /**
     * Verify user's credentials.
     *
     * @param  string $email
     * @param  string $password
     * @param  string $clientId
     * 
     * @return int|boolean
     */
    public function verify($email, $password, $clientId){
        
        $user = User::where('email', $email)->first();
        
        if($user && Hash::check($password, $user->password) && $clientId == $user->client){
            return $user->id;
        }
        
        return false;
    }
}
