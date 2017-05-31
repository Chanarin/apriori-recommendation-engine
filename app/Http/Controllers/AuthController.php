<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GuzzleHttp\Client;

class AuthController extends Controller
{
    /**
     * @param stdClass  $response
     * 
     * @return void
     */
    private function queueCookie(\stdClass $response)
    {
        if(property_exists($response, 'refresh_token'))
        {
            $refreshToken = $response->refresh_token;
            
            $cookie = app()->make('cookie');
            $crypt  = app()->make('encrypter');

            $encryptedToken = $crypt->encrypt($refreshToken);
            
            $cookie->queue('refreshToken',
                $crypt->encrypt($encryptedToken),
                36000, 
                null,
                null,
                false,
                true
            );
        }
    }
    
    /**
     * @param Request   $request
     * 
     * @return mixed
     */
    public function auth(Request $request)
    {
        $grantType = $request->get('grant_type');
        
        $args = [
            'username'      => $request->get('username'),
            'password'      => $request->get('password'),
            'client_id'     => $request->get('client_id'),
            'client_secret' => $request->get('client_secret')
        ];
        
        $response = $this->proxy($grantType, $args);
        
        $this->queueCookie($response);
        
        if(property_exists($response, 'error'))
        {
            return $this->success($response, 400);
        }
        
        return $this->success($response, 200);
    }
    
    /**
     * @param string    $grantType
     * @param array     $args
     * 
     * @return mixed
     */
    private function proxy(string $grantType, array $args)
    {
        $args = array_merge(['grant_type' => $grantType], $args);
        
        $client = new Client(['base_uri' => app()->make('url')->to('/')]);
        
        try
        {
            $guzzleResponse = $client->post('/oauth/access_token', [
                'form_params' => $args
            ]);
        }
        catch(\GuzzleHttp\Exception\BadResponseException $e) 
        {
            $guzzleResponse = $e->getResponse();
        }
        
        return json_decode($guzzleResponse->getBody());
    }
    
    /**
     * @return mixed
     */
    public function attemptRefresh()
    {
        $crypt = app()->make('encrypter');
        $request = app()->make('request');

        return $this->proxy('refresh_token', [
            'refresh_token' => $crypt->decrypt($request->cookie('refreshToken'))
        ]);
    }
}