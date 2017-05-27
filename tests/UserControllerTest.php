<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

use Illuminate\Http\Response;
use App\User;

class UserControllerTest extends TestCase
{
    public function test_store_user()
    {
        $response = $this->call('POST', '/users', [
            'email'    => 'acarste@okstate.edu',
            'password' => 'password',
            'name'     => 'Alex',
        ]);
        
        $this->assertEquals(201, $response->status());
    }
    
    public function test_oauth_access_token()
    {
        $user = User::get()->last();
        
        $response = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ]);
        
        $this->assertArrayHasKey('access_token', $response->original);
        $this->assertArrayHasKey('token_type', $response->original);
        $this->assertArrayHasKey('expires_in', $response->original);
    }
    
    public function test_oauth_access_token_fail()
    {
        $user = User::get()->last();
        
        $response = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => '',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_credentials');
        
        $response = $this->call('POST', '/oauth/access_token', [
            'username'      => '',
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_credentials');
        
        $response = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => '',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ]);
        
        $this->assertTrue($response->original['error'] == 'unsupported_grant_type');
        
        $response = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => '',
            'client_secret' => $user->secret,
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_client');
        
        $response = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => '',
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_client');
    }
    
    public function test_update_user_credentials()
    {
        $user = User::get()->last();
        
        $accessToken = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ])->original['access_token'];
        
        $response = $this->call('PUT', "/users/{$user->id}/credentials", [
            'access_token' => $accessToken
        ]);
        
        $this->assertEquals(200, $response->status());
    }
    
    public function test_update_user_credentials_fail()
    {
        $user = User::get()->last();
        
        $accessToken = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ])->original['access_token'];
        
        $id = $user->id + 1;
        
        $response = $this->call('PUT', "/users/{$id}/credentials", [
            'access_token' => $accessToken
        ]);
        
        $this->assertEquals(403, $response->status());
        
        $response = $this->call('PUT', "/users/{$user->id}/credentials", [
            'access_token' => ''
        ]);
        
        $this->assertTrue($response->original['error'] == 'invalid_request');
    }
    
    public function test_show_user()
    {
        $user = User::get()->last();
        
        $accessToken = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ])->original['access_token'];
        
        $response = $this->call('GET', "/users/{$user->id}?access_token={$accessToken}");
        
        $this->assertEquals(200, $response->status());
    }
    
    public function test_show_user_fail()
    {
        $user = User::get()->last();
        
        $accessToken = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ])->original['access_token'];
        
        $id = $user->id + 1;
        
        $response = $this->call('GET', "/users/{$id}?access_token={$accessToken}");
        
        $this->assertEquals(403, $response->status());
        
        $response = $this->call('GET', "/users/{$id}?access_token=1");
       
        $this->assertTrue($response->original['error'] == 'access_denied');
    }
    
    public function test_update_user()
    {
        $user = User::get()->last();
        
        $accessToken = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ])->original['access_token'];
        
        $response = $this->call('PATCH', "/users/{$user->id}",[
            'email'        => $user->email,
            'access_token' => $accessToken,
            'password'     => 'password',
            'name'         => 'Alex Carstens',
        ]);
        
       $this->assertEquals($response->status(), 200);
    }
    
    public function test_destroy_user()
    {
        $user = User::get()->last();
        
        $accessToken = $this->call('POST', '/oauth/access_token', [
            'username'      => $user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $user->client,
            'client_secret' => $user->secret,
        ])->original['access_token'];
        
        $response = $this->call('DELETE', "/users/{$user->id}",[
            'access_token' => $accessToken,
        ]);
        
       $this->assertEquals($response->status(), 200);
    }
}