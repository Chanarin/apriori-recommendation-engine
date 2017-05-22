<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use App\User;
use App\OauthClient;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('oauth', ['except' => ['store']]);
    }
    
    public function store(Request $request)
    {
        $this->validateRequest($request);
        
        $secret = $this->generateCredentials();
        $client = $this->generateCredentials();
		
		(new OauthClient)->setOauthClient($client, $secret, $request->get('name'))->save();
		
		$user = (new User)->setUser($request, $client, $secret);
		$user->save();
				
		return $this->success('User  successfully created', 201);
    }
    
    private function generateCredentials() : string
    {
        return (string) bin2hex(random_bytes(20));
    }
    
    private function validateRequest(Request $request)
    {

		$this->validate($request, [
			'email'    => 'required|email|unique:users', 
			'password' => 'required|min:6',
			'name'     => 'required|max:180',
		]);
	}
}