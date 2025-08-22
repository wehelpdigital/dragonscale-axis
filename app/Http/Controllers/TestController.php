<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TestController extends Controller
{
    public function testUsers()
    {
        $users = User::all(['id', 'name', 'email', 'delete_status', 'created_at']);

        return response()->json([
            'users' => $users,
            'total_users' => $users->count()
        ]);
    }

    public function testPassword($email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found']);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'delete_status' => $user->delete_status,
                'has_password' => !empty($user->password),
                'password_length' => strlen($user->password)
            ]
        ]);
    }

        public function testAuth($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found']);
        }

        $passwordCheck = Hash::check($password, $user->password);

        return response()->json([
            'user_found' => true,
            'email' => $email,
            'password_check' => $passwordCheck,
            'delete_status' => $user->delete_status,
            'can_login' => $passwordCheck && ($user->delete_status === 'active' || $user->delete_status === null),
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password_hash' => substr($user->password, 0, 20) . '...',
                'created_at' => $user->created_at
            ]
        ]);
    }

        public function testLoginProcess(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            return response()->json(['error' => 'Email and password are required']);
        }

        // Use the same logic as LoginController - only look for active users
        $user = User::where('email', $email)
                   ->where(function($query) {
                       $query->where('delete_status', 'active')
                             ->orWhereNull('delete_status');
                   })
                   ->first();

        if (!$user) {
            return response()->json(['error' => 'No active user found with this email']);
        }

        // Test the exact same logic as LoginController
        $credentials = ['email' => $email, 'password' => $password];

                // Test password check
        $passwordCheck = Hash::check($password, $user->password);

        // Test authentication with our custom logic
        $canLogin = $passwordCheck && ($user->delete_status === 'active' || $user->delete_status === null);

        return response()->json([
            'email' => $email,
            'user_found' => true,
            'user_id' => $user->id,
            'delete_status' => $user->delete_status,
            'password_check' => $passwordCheck,
            'can_login' => $canLogin,
            'reason' => $canLogin ? 'User is active and password is correct' : 'User is deleted or password is incorrect'
        ]);
    }
}
