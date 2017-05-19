<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Http\Request;

class Controller extends BaseController
{
    protected function createSuccessResponse($data, $code)
    {
        return response()->json(['data' => $data], $code);
    }
    
    protected function callErrorResponse($data, $code)
    {
        return response()->json(['message' => $data, 'code' => $code], $code);
    }
    
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        return $this->callErrorResponse($errors, 422);
    }
}
