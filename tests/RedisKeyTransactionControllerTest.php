<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use Illuminate\Http\Response;
use App\User;
use App\RedisKey;
use App\Transaction;

class RedisKeyTransactionControllerTest extends TestCase
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
        
        $id = $this->user->id;
        
        $this->call('POST', "/users/{$id}/redis_keys", [
            'access_token' => $this->accessToken,
            'master_key'   => 'Master_Key',
        ]);
        
        $this->redisKey = RedisKey::get()->last();
    }
    
    public function test_index_transactions()
    {
        $this->setCredentials();
        
        $id = $this->redisKey->id;
        
        $this->call('POST', "/redis_keys/{$id}/transactions", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,4],
        ]);
        
        $response = $this->call('GET', "/redis_keys/{$id}/transactions?access_token={$this->accessToken}");
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('GET', "/redis_keys/{$id}1/transactions?access_token={$this->accessToken}");
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('GET', "/redis_keys/{$id}/transactions?access_token={$this->accessToken}1");
        
        $this->assertTrue($response->original['error'] == "access_denied");
        
        $response = $this->call('GET', "/redis_keys/{$id}/transactions?access_token={$this->accessToken}");
        
        $this->destroyCredentials();
    }
    
    public function test_update_transactions()
    {
        $this->setCredentials();
        
        $id = $this->redisKey->id;
        
        $this->call('POST', "/redis_keys/{$id}/transactions", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,4],
        ]);
        
        $transaction = Transaction::get()->last();
        
        $response = $this->call('PUT', "/redis_keys/{$id}/transactions/{$transaction->id}", [
            'access_token' => $this->accessToken,
            'items'        => [8,7,6,5],
        ]);
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('PUT', "/redis_keys/{$id}1/transactions/{$transaction->id}", [
            'access_token' => $this->accessToken,
            'items'        => [8,7,6,5],
        ]);
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('PUT', "/redis_keys/{$id}/transactions/{$transaction->id}", [
            'access_token' => $this->accessToken,
            'items'        => [8,7,6,''],
        ]);
        
        $this->assertEquals($response->status(), 422);
        
        $response = $this->call('PUT', "/redis_keys/{$id}/transactions/{$transaction->id}", [
            'access_token' => '',
            'items'        => [8,7,6,5],
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_request');
        
        $response = $this->call('PUT', "/redis_keys/{$id}/transactions/{$transaction->id}1", [
            'access_token' => $this->accessToken,
            'items'        => [8,7,6,5],
        ]);
        
        $this->assertEquals($response->status(), 404);
        
        $this->destroyCredentials();
    }
    
    public function test_store_transactions()
    {
        $this->setCredentials();
        
        $id = $this->redisKey->id;
        
        $response = $this->call('POST', "/redis_keys/{$id}/transactions", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,4],
        ]);
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('POST', "/redis_keys/{$id}1/transactions", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,4],
        ]);
        
        $this->assertEquals($response->status(), 403);
        
        $response = $this->call('POST', "/redis_keys/{$id}/transactions", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,''],
        ]);
        
        $this->assertEquals($response->status(), 422);
        
        $response = $this->call('POST', "/redis_keys/{$id}/transactions", [
            'access_token' => '',
            'items'        => [1,2,3,''],
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_request');
        
        $this->destroyCredentials();
    }
    
    public function test_store_async_transaction()
    {
        $this->setCredentials();
        
        $id = $this->redisKey->id;
        
        $response = $this->call('POST', "/redis_keys/{$id}/transactions_async", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,4],
        ]);
        
        $this->assertEquals($response->status(), 200);
        
        $this->destroyCredentials();
    }
    
    public function test_destroy_transaction()
    {
        $this->setCredentials();
        
        $id = $this->redisKey->id;
        
        $this->call('POST', "/redis_keys/{$id}/transactions", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,4],
        ]);
        
        $transaction = Transaction::get()->last();
        
        $id = $this->redisKey->id;
        
        $response = $this->call('DELETE', "/redis_keys/{$id}/transactions/{$transaction->id}", [
            'access_token' => $this->accessToken
        ]);
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('DELETE', "/redis_keys/{$id}/transactions/{$transaction->id}", [
            'access_token' => ''
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_request');
        
        $response = $this->call('DELETE', "/redis_keys/{$id}/transactions/{$transaction->id}1", [
            'access_token' => $this->accessToken
        ]);
        
        $this->assertEquals($response->status(), 404);
        
        $response = $this->call('DELETE', "/redis_keys/{$id}1/transactions/{$transaction->id}", [
            'access_token' => $this->accessToken
        ]);
        
        $this->assertEquals($response->status(), 403);
        
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