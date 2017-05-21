<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

use Illuminate\Http\Request;

class Controller extends BaseController
{
    protected function success($data, $code)
    {
        return response()->json(['data' => $data], $code);
    }
    
    protected function error($message, $code)
    {
        return response()->json(['message' => $message, 'code' => $code], $code);
    }
    
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        return $this->error($errors, 422);
    }
}
