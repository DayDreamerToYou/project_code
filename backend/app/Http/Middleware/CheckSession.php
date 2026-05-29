<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSession
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('user_id')) {
            return response()->json([
                'success' => false,
                'message' => '未登录或登录已过期',
                'logged_in' => false,
            ], 401);
        }

        return $next($request);
    }
}
