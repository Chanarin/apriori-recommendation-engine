<?php

use App\RedisKey;
use App\Transaction;
use App\User;

class AprioriControllerTest extends TestCase
{
    public $user = null;

    public $accessToken = null;

    public $redisKey = null;

    public $transaction = null;

    private function setCredentials()
    {
        $this->call('POST', '/users', [
            'email'                 => 'acarste@okstate.edu',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'name'                  => 'Alex Carstens',
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
            'items'        => [1, 2, 3, 4],
        ]);

        $this->transaction = Transaction::get()->last();
    }

    public function test_recommend()
    {
        $this->setCredentials();

        $id = $this->redisKey->id;

        $response = $this->call('GET', "/redis_keys/{$id}/recommend?access_token={$this->accessToken}&items[]=1");

        $this->assertEquals($response->status(), 200);

        $this->arrayHasKey($response->original['data'][0], 'confidence');
        $this->arrayHasKey($response->original['data'][0], 'support');
        $this->arrayHasKey($response->original['data'][0], 'lift');
        $this->arrayHasKey($response->original['data'][0], 'key');

        $response = $this->call('GET', "/redis_keys/{$id}/recommend?access_token={$this->accessToken}&items=1");

        $this->assertEquals($response->status(), 500);

        $response = $this->call('GET', "/redis_keys/{$id}/recommend?access_token={$this->accessToken}&items[]=100");

        $this->assertEquals($response->status(), 422);

        $response = $this->call('GET', "/redis_keys/{$id}/recommend?access_token={$this->accessToken}1&items[]=1");

        $this->assertTrue($response->original['error'] == 'access_denied');

        $this->destroyCredentials();
    }

    public function test_raw_scan()
    {
        $this->setCredentials();

        $id = $this->redisKey->id;

        $response = $this->call('GET', "/redis_keys/{$id}/raw_scan?access_token={$this->accessToken}&items[]=1");

        $this->assertEquals($response->status(), 200);

        $this->assertTrue(count($response->original['data']) == 2);
        $this->assertTrue(is_numeric($response->original['data'][0]));
        $this->assertTrue(is_array($response->original['data'][1]));

        $response = $this->call('GET', "/redis_keys/{$id}/raw_scan?access_token={$this->accessToken}");

        $this->assertEquals($response->status(), 422);

        $response = $this->call('GET', "/redis_keys/{$id}/raw_scan?access_token={$this->accessToken}&items[]=100");

        $this->assertEquals($response->status(), 422);

        $this->destroyCredentials();
    }

    public function test_total()
    {
        $this->setCredentials();

        $id = $this->redisKey->id;

        $response = $this->call('GET', "/redis_keys/{$id}/total?access_token={$this->accessToken}");

        $this->assertEquals($response->status(), 200);

        $this->assertTrue(is_numeric($response->original['data']['transaction count']));

        $this->destroyCredentials();
    }

    public function test_combination_count()
    {
        $this->setCredentials();

        $id = $this->redisKey->id;

        $response = $this->call('GET', "/redis_keys/{$id}/combination_count?access_token={$this->accessToken}");

        $this->assertEquals($response->status(), 200);

        $this->assertTrue(is_numeric($response->original['data']['combination count']));

        $this->destroyCredentials();
    }

    public function test_frequency()
    {
        $this->setCredentials();

        $id = $this->redisKey->id;

        $response = $this->call('GET', "/redis_keys/{$id}/frequency?access_token={$this->accessToken}&items[]=1&items[]=2");

        $this->assertEquals($response->status(), 200);

        $this->assertTrue(is_numeric($response->original['data']['frequency']));

        $this->destroyCredentials();
    }

    public function test_support()
    {
        $this->setCredentials();

        $id = $this->redisKey->id;

        $response = $this->call('GET', "/redis_keys/{$id}/support?access_token={$this->accessToken}&items[]=1&items[]=2");

        $this->assertEquals($response->status(), 200);

        $this->assertTrue(is_numeric($response->original['data']['support']));

        $response = $this->call('GET', "/redis_keys/{$id}/support?access_token={$this->accessToken}&items=1");

        $this->assertEquals($response->status(), 500);

        $this->destroyCredentials();
    }

    private function destroyCredentials()
    {
        $id = $this->user->id;

        $this->call('DELETE', "/users/{$id}", [
            'access_token' => $this->accessToken,
        ]);
    }
}
