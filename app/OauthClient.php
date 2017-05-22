<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OauthClient extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'id',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'secret',
    ];
    
    /**
     * @param string    $id
     * @param string    $secret
     * @param string    $name
     * 
     * @return OauthClient
     */ 
    public function setOauthClient(string $id, string $secret, string $name) : OauthClient
    {
		$this->id = $id;
		$this->secret = $secret;
		$this->name = $name;
		
		return $this;
    }
}