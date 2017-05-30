<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use Illuminate\Http\Response;
use App\User;
use App\RedisKey;
use App\Transaction;

class TransactionControllerTest extends TestCase
{
    public $user = null;
    
    public $accessToken = null;
    
    public $redisKey = null;
    
    public $transaction = null;
    
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
        
        $id = $this->redisKey->id;
        
        $this->call('POST', "/redis_keys/{$id}/transactions", [
            'access_token' => $this->accessToken,
            'items'        => [1,2,3,4],
        ]);
        
        $this->transaction = Transaction::get()->last();
    }
    
    public function test_show_transactions()
    {
        $this->setCredentials();
        
        $id = $this->transaction->id;
        
        $response = $this->call('GET', "/transactions/{$id}?access_token={$this->accessToken}");
        
        $this->assertEquals($response->status(), 200);
        
        $response = $this->call('GET', "/transactions/{$id}1?access_token={$this->accessToken}");
        
        $this->assertEquals($response->status(), 422);
        
        $response = $this->call('GET', "/transactions/{$id}?access_token={$this->accessToken}1");
        
        $this->assertTrue($response->original['error'] == "access_denied");
        
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