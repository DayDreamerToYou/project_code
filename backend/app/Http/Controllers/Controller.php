<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function success(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        $response = ['success' => true];
        if ($message) {
            $response['message'] = $message;
        }
        if ($data !== null) {
            $response['data'] = $data;
        }
        return response()->json($response, $code);
    }

    protected function error(string $message = '操作失败', int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $code);
    }

    protected function paginated(mixed $data, array $pagination, string $message = ''): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
        ];
        if ($message) {
            $response['message'] = $message;
        }
        return response()->json($response);
    }
}
