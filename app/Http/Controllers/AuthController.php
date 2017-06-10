<?php   

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
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
        
        return $this->response($response);
    }
    
    /**
     * @param stdClass  $response
     * 
     * @return mixed
     */
    private function response(\stdClass $response)
    {
        if(property_exists($response, 'error'))
        {
            return $this->error($response->error, 400);
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
          
        $response = app()->handle(Request::create('/oauth/access_token', 'POST', $args));
        
        return json_decode($response->content());
    }
    
    /**
     * @return mixed
     */
    public function attemptRefresh(Request $request)
    {
        $grantType = $request->get('grant_type');
        
        $args = [
            'refresh_token' => $request->get('refresh_token'),
            'client_id'     => $request->get('client_id'),
            'client_secret' => $request->get('client_secret')
        ];
        
        $response = $this->proxy($grantType, $args);
        
        return $this->response($response);
    }
}