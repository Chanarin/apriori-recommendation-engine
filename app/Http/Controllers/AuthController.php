<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function issueAccessToken()
    {
        $response = app()->make('oauth2-server.authorizer')->issueAccessToken();
        
        if(isset($response['refresh_token']))
        {
            $refreshToken = $response['refresh_token'];
            
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
        
        return response()->json($response);
    }
}