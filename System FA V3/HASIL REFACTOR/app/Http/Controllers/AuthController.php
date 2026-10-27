<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::whereRaw('LOWER(username) = ?', [strtolower(trim($request->username))])->first();

        if (!$user) {
            return $this->errorResponse('Username atau password salah', 401);
        }

        $passwordMatches = false;
        try {
            if (Hash::check($request->password, $user->password)) {
                $passwordMatches = true;
            }
        } catch (\Throwable $e) {
            // If stored password is plain text, Hash::check throws RuntimeException
        }

        if (!$passwordMatches && $user->password === $request->password) {
            $passwordMatches = true;
            // Auto-upgrade password to bcrypt
            $user->password = Hash::make($request->password);
            $user->save();
        }

        if (!$passwordMatches) {
            return $this->errorResponse('Username atau password salah', 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'role' => $user->role,
            'username' => $user->username,
            'full_name' => $user->full_name,
            'nik' => $user->nik,
            'department' => $user->department,
            'permissions' => $user->permissions,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nik' => $user->nik,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'role' => $user->role,
                    'department' => $user->department,
                    'permissions' => $user->permissions,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }
        return $this->successResponse(null, 'Logout berhasil');
    }

    public function me(Request $request)
    {
        $bearer = $request->bearerToken();
        $user = $request->user('sanctum') 
            ?? $request->user() 
            ?? ($bearer ? \Laravel\Sanctum\PersonalAccessToken::findToken($bearer)?->tokenable : null);

        if (!$user) {
            return $this->errorResponse('Sesi tidak valid', 401);
        }
        return $this->successResponse([
            'id' => $user->id,
            'nik' => $user->nik,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'role' => $user->role,
            'department' => $user->department,
            'permissions' => $user->permissions,
        ]);
    }

    public function operators()
    {
        $operators = User::where(function($q) {
                $q->whereRaw("LOWER(role) = 'assembly'")
                  ->orWhereRaw("LOWER(department) = 'assembly'");
            })
            ->whereNotIn(DB::raw("LOWER(username)"), ['admin', 'yasin', 'hendrik', 'miranti', 'granada', 'ramdani'])
            ->whereNotIn(DB::raw("LOWER(department)"), ['administrator', 'warehouse', 'gudang'])
            ->select('id', 'nik', 'full_name', 'username', 'role', 'department')
            ->orderBy('full_name')
            ->get();
        return $this->successResponse($operators);
    }

    public function getOperators()
    {
        return $this->operators();
    }

    public function lookup()
    {
        $users = User::select('id', 'nik', 'full_name', 'username', 'role', 'department', 'can_access_cms')
            ->orderBy('full_name', 'asc')
            ->orderBy('username', 'asc')
            ->get();
        return $this->successResponse($users);
    }

    public function getUsers()
    {
        $users = User::select('id', 'nik', 'full_name', 'username', 'role', 'department', 'permissions', 'can_access_cms', 'created_at')
            ->orderBy('id', 'asc')
            ->get();
        return $this->successResponse($users);
    }

    public function createUser(Request $request)
    {
        return $this->saveUser($request);
    }

    public function updateUser(Request $request)
    {
        return $this->saveUser($request);
    }

    public function saveUser(Request $request)
    {
        $userId = $request->input('id');
        $username = trim((string)$request->input('username', ''));

        if (!$username) {
            return $this->errorResponse('Username wajib diisi.', 422);
        }

        $data = [
            'nik' => $request->input('nik'),
            'full_name' => $request->input('full_name') ?: $username,
            'username' => $username,
            'role' => $request->input('role', 'assembly'),
            'department' => $request->input('department'),
            'permissions' => $request->input('permissions'),
        ];

        if ($request->has('can_access_cms')) {
            $data['can_access_cms'] = $request->boolean('can_access_cms');
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        // Cari user yang sudah ada berdasarkan ID atau Username
        $user = null;
        if ($userId) {
            $user = User::find($userId);
        }
        if (!$user && $username) {
            $user = User::where('username', $username)->first();
        }

        if ($user) {
            // Update user yang sudah ada
            $user->update($data);
            return $this->successResponse($user, 'Pengguna berhasil diperbarui');
        } else {
            // Buat pengguna baru, pastikan password terisi
            if (empty($data['password'])) {
                $rawPw = $request->input('password') ?: 'password123';
                $data['password'] = Hash::make($rawPw);
            }
            $user = User::create($data);
            return $this->successResponse($user, 'Pengguna baru berhasil dibuat');
        }
    }

    public function deleteUser(Request $request)
    {
        $userId = $request->input('id');
        if (!$userId) {
            return $this->errorResponse('ID pengguna diperlukan');
        }
        if ((int)$userId === 3) {
            return $this->errorResponse('Akun admin utama sistem tidak boleh dihapus.');
        }
        User::where('id', $userId)->delete();
        return $this->successResponse(null, 'Pengguna berhasil dihapus');
    }

    public function toggleCmsAccess(Request $request)
    {
        $userId = $request->input('id');
        if (!$userId) {
            return $this->errorResponse('ID pengguna diperlukan');
        }
        $user = User::findOrFail($userId);
        $user->can_access_cms = !$user->can_access_cms;
        $user->save();

        return $this->successResponse([
            'id' => $user->id,
            'can_access_cms' => (bool)$user->can_access_cms,
            'username' => $user->username
        ], 'Hak akses CMS berhasil ' . ($user->can_access_cms ? 'diberikan' : 'dicabut') . ' untuk ' . $user->username);
    }

    public function resetSession(Request $request)
    {
        $userId = $request->input('id');
        $user = User::find($userId);
        if ($user) {
            $user->tokens()->delete();
        }
        return $this->successResponse(null, 'Sesi pengguna berhasil direset');
    }
}
