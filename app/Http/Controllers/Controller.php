<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Http\Request;

use LucaDegasperi\OAuth2Server\Facades\Authorizer;
use Gate;
use App\User;

class Controller extends BaseController
{
    public function success($data, $code)
    {
        return response()->json(['data' => $data], $code);
    }
    
    public function error($message, $code)
    {
        return response()->json(['message' => $message, 'code' => $code], $code);
    }
    
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        return $this->error($errors, 422);
    }
    
    /**
     * Check if the user is authorized to perform a given action.
     *
     * @param Request  $request
     * @param array $resource
     * @param mixed|array $arguments
     * 
     * @return boolean
     */
    protected function authorizeUser(Request $request, $resource, $arguments = []){
    	
    	$user 	 = User::find(Authorizer::getResourceOwnerId());
    	
    	$action	 = $this->getAction($request);
    	
        $ability = '{' . $action .'}-{' . $resource . '}';
        
    	return Gate::forUser($user)->allows($ability, $arguments);
    }
    
    /**
     * Get the requested action method.
     *
     * @param Request  $request
     * 
     * @return string
     */
    protected function getAction(Request $request)
    {
        return explode('@', $request->route()[1]["uses"], 2)[1];
    }

    /**
     * Get the parameters in route.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getArgs(Request $request){
        return $request->route()[2];
    }
        
    /**
     * Check if user is authorized.
     *
     * This method will be called by "Authorize" Middleware for every controller.
     * Controller that needs to be authorized must override this method.
     *
     * @param Request  $request
     * 
     * @return bool
     */
    public function isAuthorized(Request $request){
        return false;
    }
    
    
}
