<?php


use App\User;

class AuthControllerTest extends TestCase
{
    public $user = null;

    private function setCredentials()
    {
        $this->call('POST', '/users', [
            'email'                 => 'acarste@okstate.edu',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'name'                  => 'Alex Carstens',
        ]);

        $this->user = User::get()->last();
    }

    public function test_proxy_auth()
    {
        $this->setCredentials();

        $response = $this->call('POST', '/oauth/login', [
            'username'      => $this->user->email,
            'password'      => 'password',
            'grant_type'    => 'password',
            'client_id'     => $this->user->client,
            'client_secret' => $this->user->secret,
        ]);

        $accessToken = json_decode($response->content())->data->access_token;

        $refreshToken = json_decode($response->content())->data->refresh_token;

        $this->assertEquals($response->status(), 200);

        $response = $this->call('POST', '/oauth/refresh_token', [
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->user->client,
            'client_secret' => $this->user->secret,
        ]);

        $this->assertEquals($response->status(), 200);

        $id = $this->user->id;

        $this->call('DELETE', "/users/{$id}", [
            'access_token' => $accessToken,
        ]);
    }
}
