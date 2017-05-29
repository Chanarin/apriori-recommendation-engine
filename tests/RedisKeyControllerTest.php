<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use Illuminate\Http\Response;
use App\User;
use App\RedisKey;

class RedisKeyControllerTest extends TestCase
{
    public $user = null;
    
    public $accessToken = null;
    
    private function setCredentials()
    {
        $this->call('POST', '/users', [
            'email'    => 'acarste@okstate.edu',
            'password' => 'password',
            'name'     => 'Alex',
        ]);
        
        $this->user = User::get()->last();
        
        $this->accessToken = $this->call('POST', '/oauth/access_token', [
            'username'      => $this->user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $this->user->client,
            'client_secret' => $this->user->secret,
        ])->original['access_token'];
    }
    
    public function test_show_redis_keys()
    {
        $this->setCredentials();
        
        $id = $this->user->id;
        
        $this->call('POST', "/users/{$id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => 'Master_Key',
        ]);
        
        $redisKey = RedisKey::get()->last();
        
        $response = $this->call('GET', "/redis_keys/{$redisKey->id}?access_token={$this->accessToken}");
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('GET', "/redis_keys/{$redisKey->id}1?access_token={$this->accessToken}");
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('GET', "/redis_keys/{$redisKey->id}?access_token={$this->accessToken}1");
        
        $this->assertTrue($response->original["error"] == "access_denied");
        
        $this->destroyCredentials();
    }
    
    private function destroyCredentials()
    {
        $id = $this->user->id;
        
        $this->call('DELETE', "/users/{$id}",[
            'access_token' => $this->accessToken,
        ]);
    }
}