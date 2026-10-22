<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Check plain password fallback for legacy migration compatibility
            if ($user && $user->password === $request->password) {
                // Auto-upgrade password to bcrypt
                $user->password = Hash::make($request->password);
                $user->save();
            } else {
                return $this->errorResponse('Username atau password salah', 401);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
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
        ], 'Login berhasil');
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()?->delete();
        }
        return $this->successResponse(null, 'Logout berhasil');
    }

    public function me(Request $request)
    {
        $user = $request->user();
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
        $operators = User::where('role', 'assembly')
            ->select('id', 'nik', 'full_name', 'username')
            ->orderBy('full_name')
            ->get();
        return $this->successResponse($operators);
    }

    public function getUsers()
    {
        $users = User::select('id', 'nik', 'full_name', 'username', 'role', 'department', 'permissions', 'created_at')
            ->orderBy('id', 'asc')
            ->get();
        return $this->successResponse($users);
    }

    public function saveUser(Request $request)
    {
        $userId = $request->input('id');
        $data = [
            'nik' => $request->input('nik'),
            'full_name' => $request->input('full_name'),
            'username' => $request->input('username'),
            'role' => $request->input('role', 'assembly'),
            'department' => $request->input('department'),
            'permissions' => $request->input('permissions'),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        if ($userId) {
            $user = User::findOrFail($userId);
            $user->update($data);
            return $this->successResponse($user, 'Pengguna berhasil diperbarui');
        } else {
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
        User::where('id', $userId)->delete();
        return $this->successResponse(null, 'Pengguna berhasil dihapus');
    }

    public function resetSession(Request $request)
    {
        $userId = $request->input('id');
        $user = User::find($userId);
        if ($user) {
            $user->tokens()->delete();
        }
        return $this->successResponse(null, 'Sesi berhasil direset');
    }
}
