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
    
    public function test_index_redis_keys()
    {
        $this->setCredentials();
        
        $id = $this->user->id;
        
        $this->call('POST', "/users/{$id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => 'Master_Key',
        ]);
        
        $response = $this->call('GET', "users/{$id}/redis_keys?access_token={$this->accessToken}");
 
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('GET', "users/{$id}1/redis_keys?access_token={$this->accessToken}");
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('GET', "users/{$id}/redis_keys?access_token={$this->accessToken}1");
        
        $this->assertTrue($response->original["error"] == "access_denied");
        
        $this->destroyCredentials();
    }
    
    public function test_update_redis_keys()
    {
        $this->setCredentials();
        
        $id = $this->user->id;
        
        $this->call('POST', "/users/{$id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => 'Master_Key',
        ]);
        
        $redisKey = RedisKey::get()->last();
        
        $response = $this->call('PATCH', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}",[
            "access_token" => $this->accessToken,
            "master_key"   => "Another_Master_key",
        ]);
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('PATCH', "/users/{$redisKey->user_id}1/redis_keys/{$redisKey->id}",[
            "access_token" => $this->accessToken,
            "master_key"   => "Another_Master_key",
        ]);
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('PATCH', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}1",[
            "access_token" => $this->accessToken,
            "master_key"   => "Another_Master_key",
        ]);
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('PATCH', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}",[
            "access_token" => '1',
            "master_key"   => "Another_Master_key",
        ]);
        
        $this->assertTrue($response->original["error"] == "access_denied");
        
        $response = $this->call('PATCH', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}",[
            "access_token" => $this->accessToken,
            "master_key"   => "Another_Master_Key",
        ]);
        
        $this->assertEquals($response->status(), 422);
        
        $response = $this->call('PATCH', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}",[
            "access_token" => $this->accessToken,
            "master_key"   => "",
        ]);
        
        $this->assertEquals($response->status(), 422);
        
        $this->destroyCredentials();
    }
    
    public function test_destroy_redis_key()
    {
        $this->setCredentials();
        
        $id = $this->user->id;
        
        $this->call('POST', "/users/{$id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => 'Master_Key',
        ]);
        
        $redisKey = RedisKey::get()->last();
        
        $response = $this->call('DELETE', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}",[
            "access_token" => $this->accessToken
        ]);
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('DELETE', "/users/{$redisKey->user_id}1/redis_keys/{$redisKey->id}",[
            "access_token" => $this->accessToken
        ]);
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('DELETE', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}1",[
            "access_token" => $this->accessToken
        ]);
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('DELETE', "/users/{$redisKey->user_id}/redis_keys/{$redisKey->id}1",[
            "access_token" => '1'
        ]);
        
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