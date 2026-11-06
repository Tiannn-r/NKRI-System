<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function successResponse($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'ok' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message = 'Error', int $code = 400, $data = null)
    {
        return response()->json([
            'ok' => false,
            'error' => $message,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
