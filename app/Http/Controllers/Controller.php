<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
     /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $code = 404)
    {
        $response = ['success' => false, 'message' => $error];
        
        return response()->json($response, $code);
    }

    public function sendResponse($result, $message)
    {
        $response = ['success' => true, 'data' => $result, 'message' => $message];
        
        return response()->json($response, 200);
    }
}
