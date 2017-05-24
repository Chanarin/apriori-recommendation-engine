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
        $this->middleware('authorize:' . __CLASS__ , ['except' => ['store']]);
    }
    
    public function show($id)
    {
        if($user = User::find($id))
        {
            return $this->success($user, 200);
        }
        
        return $this->error("The user with {$id} doesn't exist", 422);
    }
    
    public function index()
    {
        return $this->success(User::all(), 200);
    }
    
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
		if(!$user) return $this->error("The user with {$id} doesn't exist", 404);
		
		if($request->get('email') !== $user->email)
		{
		    $this->validateRequest($request);
		}
		else
		{
		    $this->validate($request, [
		        'password' => 'required|min:6',
		        'name'     => 'required|max:180',
		    ]);
		}

		$user->setUser($request)->save();
		
		return $this->success("User with id {$id} updated successfully.", 200);
    }
    
    public function credentials($id)
    {
        $user = User::find($id);
        
		if(!$user) return $this->error("The user with id {$id} doesn't exist", 404);
        
        OauthClient::find($user->client)->delete();
		
		$secret = $this->generateCredentials();
        $client = $this->generateCredentials();
        
		(new OauthClient)->setOauthClient($client, $secret, $user->name)->save();
		
		$user->setUser(null, $client, $secret)->save();
		
		return $this->success("User with id {$id} credentials updated successfully.", 200);
    }
    
    public function store(Request $request)
    {
        $this->validateRequest($request);
        
        $secret = $this->generateCredentials();
        $client = $this->generateCredentials();
		
		(new OauthClient)->setOauthClient($client, $secret, $request->get('name'))->save();
		
		$user = (new User)->setUser($request, $client, $secret);
		$user->save();
				
		return $this->success("User with {$user->id} successfully created.", 201);
    }
    
    public function destroy($id)
    {
        $user = User::find($id);
        
        foreach($user->redisKeys as $redisKey)
        {
            $redisKey->remove();
            $redisKey->delete();
        }
        
        OauthClient::find($user->client)->delete();
        
        $user->delete();
        
        return $this->success("User with {$id} successfully deleted.", 200);
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
	
	public function isAuthorized(Request $request)
	{
		$resource = "users";
		
		$user = User::find($this->getArgs($request)["id"]);
		
		return $this->authorizeUser($request, $resource, $user);
	}
}