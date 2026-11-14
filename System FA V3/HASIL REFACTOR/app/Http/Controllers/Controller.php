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

    /**
     * Resolves the actual authenticated user account and full name.
     * Standardizes actor data so that generic names ('operator', 'gudang') are never used.
     */
    protected function resolveActor(\Illuminate\Http\Request $request): array
    {
        $user = null;

        // 1. Check Sanctum token or authenticated user
        if ($request->user('sanctum')) {
            $user = $request->user('sanctum');
        } elseif ($request->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($token && $token->tokenable) {
                $user = $token->tokenable;
            }
        } elseif (auth()->user()) {
            $user = auth()->user();
        }

        if ($user) {
            return [
                'username' => $user->username,
                'full_name' => $user->full_name ?: $user->username,
                'role' => $user->role ?: 'User'
            ];
        }

        // 2. Check X-Actor-* headers from frontend
        $headerUser = trim((string)$request->header('X-Actor-Username'));
        $headerFull = trim((string)urldecode((string)$request->header('X-Actor-Fullname')));
        $headerRole = trim((string)$request->header('X-Actor-Role'));

        if (!empty($headerUser) && !in_array(strtolower($headerUser), ['operator', 'gudang', 'tester', '-'])) {
            $dbUser = \Illuminate\Support\Facades\DB::table('users')->where('username', $headerUser)->first();
            return [
                'username' => $headerUser,
                'full_name' => $dbUser?->full_name ?: ($headerFull ?: $headerUser),
                'role' => $dbUser?->role ?: ($headerRole ?: 'User')
            ];
        }

        // 3. Check request inputs (operator, username, pic_warehouse, pic_assembly, inspector)
        $inputs = [
            $request->input('operator'),
            $request->input('username'),
            $request->input('actor_username'),
            $request->input('pic_warehouse'),
            $request->input('pic_assembly'),
            $request->input('inspector')
        ];

        foreach ($inputs as $cand) {
            $cand = trim((string)$cand);
            if (!empty($cand) && !in_array(strtolower($cand), ['operator', 'gudang', 'tester', '-', 'null', 'undefined'])) {
                $dbUser = \Illuminate\Support\Facades\DB::table('users')->where('username', $cand)->first();
                if ($dbUser) {
                    return [
                        'username' => $dbUser->username,
                        'full_name' => $dbUser->full_name ?: $dbUser->username,
                        'role' => $dbUser->role ?: 'User'
                    ];
                }
                return [
                    'username' => $cand,
                    'full_name' => $cand,
                    'role' => 'User'
                ];
            }
        }

        // 4. Default fallback: Administrator NKRI
        return [
            'username' => 'Administrator NKRI',
            'full_name' => 'Administrator NKRI',
            'role' => 'admin'
        ];
    }
}
