<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !password_verify($request->password, $user->password)) {
            return $this->error('用户名或密码错误', 401);
        }

        $request->session()->regenerate();
        $token = bin2hex(random_bytes(32));

        session([
            'user_id' => $user->id,
            'username' => $user->username,
            'login_token' => $token,
            'login_time' => time(),
        ]);

        return $this->success([
            'user_id' => $user->id,
            'username' => $user->username,
            'token' => $token,
        ], '登录成功');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return $this->success(null, '登出成功');
    }

    public function user(Request $request)
    {
        $userId = session('user_id');
        if (!$userId) {
            return $this->error('未登录或登录已过期', 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 401);
        }

        return $this->success([
            'user_id' => $user->id,
            'username' => $user->username,
        ]);
    }
}
