<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use Illuminate\Http\Response;
use App\User;
use App\RedisKey;

class UserRedisKeyControllerTest extends TestCase
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
    
    public function test_store_redis_key()
    {
        $this->setCredentials();
        
        $id = $this->user->id;
        
        $response = $this->call('POST', "/users/{$id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => 'Master_Key',
        ]);
        
        $this->assertEquals($response->status(), 200);
        
        $this->destroyCredentials();
    }
    
    public function test_store_redis_key_failure()
    {
        $this->setCredentials();
        
        $id = $this->user->id + 1;
        
        $response = $this->call('POST', "/users/{$id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => '',
        ]);
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('POST', "/users/{$this->user->id}/redis_keys", [
            'access_token' => '',
            'master_key'   => 'Master_Key',
        ]);
        
        $this->assertTrue($response->original["error"] == "invalid_request");
        
        $response = $this->call('POST', "/users/{$this->user->id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => '',
        ]);
        
        $this->assertEquals($response->status(), 422);
        
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