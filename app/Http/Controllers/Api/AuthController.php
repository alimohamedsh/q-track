<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * تسجيل الدخول
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required'    => 'البريد الإلكتروني مطلوب.',
            'email.email'       => 'صيغة البريد الإلكتروني غير صحيحة.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.min'      => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'بيانات الدخول غير صحيحة.',
            ], 401);
        }

        $user = Auth::user();

        // إلغاء التوكنز القديمة للمستخدم (اختياري - لمنع التجمع)
        $user->tokens()->where('name', 'auth-token')->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل الدخول بنجاح',
            'data'    => [
                'token'       => $token,
                'token_type'  => 'Bearer',
                'user'        => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                ],
                'roles'       => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ], 200);
    }

    /**
     * تسجيل الخروج وإلغاء التوكن الحالي
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل الخروج بنجاح',
        ], 200);
    }
}
