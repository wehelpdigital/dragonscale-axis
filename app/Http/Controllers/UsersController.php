<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    /**
     * Display the users page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = User::select('id', 'name', 'email', 'created_at')
                    ->where('delete_status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->paginate(10);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('users.add');
    }

    /**
     * Show the form for editing a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return redirect()->route('users.index')->with('error', 'User ID is required.');
        }

        try {
            $user = User::where('id', $id)
                       ->where('delete_status', 'active')
                       ->firstOrFail();

            return view('users.edit', compact('user'));
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'User not found.');
        }
    }

    /**
     * Store a newly created user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'verify_password' => 'required|same:password',
        ], [
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 2 characters long.',
            'name.max' => 'Name must be less than 50 characters.',
            'name.regex' => 'Name can only contain letters and spaces.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'verify_password.required' => 'Please verify your password.',
            'verify_password.same' => 'Passwords do not match.',
        ]);

        // Custom email validation - check if email exists for active users only
        $emailExists = User::where('email', $request->email)
                          ->where('delete_status', 'active')
                          ->exists();

        if ($emailExists) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['email' => ['This email address is already registered.']]
                ], 422);
            }
            return redirect()->back()->withErrors(['email' => 'This email address is already registered.'])->withInput();
        }

        // Custom password validation - simplified
        $password = $request->password;
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if (!empty($errors)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['password' => $errors]
                ], 422);
            }
            return redirect()->back()->withErrors(['password' => $errors])->withInput();
        }

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'dob' => '2000-01-01', // Default date of birth
                'avatar' => 'images/avatar-1.jpg', // Default avatar
                'email_verified_at' => now(), // Mark email as verified
                'delete_status' => 'active',
            ]);

            // Check if it's an AJAX request
            if ($request->ajax()) {
                // Set session flash message for the next request
                session()->flash('success', 'User created successfully!');

                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully!',
                    'redirect_url' => route('users.index'),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ], 201);
            }

            // For regular form submission, redirect with success message
            return redirect()->route('users.index')->with('success', 'User created successfully!');

        } catch (\Exception $e) {
            // Check if it's an AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create user. Please try again.',
                    'error' => $e->getMessage()
                ], 500);
            }

            // For regular form submission, redirect back with error
            return redirect()->back()->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email',
            'password' => 'nullable|string|min:8',
        ], [
            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 2 characters long.',
            'name.max' => 'Name must be less than 50 characters.',
            'name.regex' => 'Name can only contain letters and spaces.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);

        // Custom email validation - check if email exists for active users only (excluding current user)
        $emailExists = User::where('email', $request->email)
                          ->where('delete_status', 'active')
                          ->where('id', '!=', $id)
                          ->exists();

        if ($emailExists) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['email' => ['This email address is already registered.']]
            ], 422);
        }

        // Custom password validation (only if password is provided)
        $password = $request->password;
        $errors = [];

        if (!empty($password)) {
            if (strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            }

            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'Password must contain at least one uppercase letter.';
            }

            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'Password must contain at least one lowercase letter.';
            }

            if (!preg_match('/\d/', $password)) {
                $errors[] = 'Password must contain at least one number.';
            }

            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'Password must contain at least one special character.';
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => ['password' => $errors]
                ], 422);
            }
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
            ];

            // Only update password if provided
            if (!empty($password)) {
                $updateData['password'] = Hash::make($password);
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a user can be deleted and return validation info
     */
    public function checkDeleteValidation($id)
    {
        try {
            $user = User::where('id', $id)
                       ->where('delete_status', 'active')
                       ->firstOrFail();
            $currentUser = Auth::user();

            // Check if user is trying to delete themselves
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'canDelete' => false,
                    'reason' => 'self_delete',
                    'message' => 'You cannot delete your own account.'
                ]);
            }

            // Check if this is the last active user
            $activeUsersCount = User::where('delete_status', 'active')->count();
            if ($activeUsersCount <= 1) {
                return response()->json([
                    'canDelete' => false,
                    'reason' => 'last_user',
                    'message' => 'Cannot delete the only remaining user.'
                ]);
            }

            return response()->json([
                'canDelete' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'canDelete' => false,
                'reason' => 'not_found',
                'message' => 'User not found.'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $currentUser = Auth::user();

            // Check if user is trying to delete themselves
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.'
                ], 422);
            }

            // Check if this is the last active user
            $activeUsersCount = User::where('delete_status', 'active')->count();
            if ($activeUsersCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the only remaining user.'
                ], 422);
            }

            $user->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        /**
     * Check if email exists in database
     */
    public function checkEmail(Request $request)
    {
        try {
            $email = $request->email;
            $excludeId = $request->exclude_id;

            // Validate email parameter
            if (!$email) {
                return response()->json([
                    'exists' => false,
                    'message' => 'Email parameter is required.'
                ], 400);
            }

            $query = User::where('email', $email)
                        ->where('delete_status', 'active');

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $exists = $query->exists();

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'This email is already registered.' : 'Email is available.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'message' => 'Error checking email: ' . $e->getMessage()
            ], 500);
        }
    }
}
