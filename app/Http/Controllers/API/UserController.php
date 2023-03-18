<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'      => 'required',
                'username' => 'required',
                'email'     => 'required|email|unique:users',
                'password'  => 'required|min:8'
            ]);

            //if validation fails
            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 'Authentication Failed', 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required',
                'password' => 'required',
            ]);

            $credential = request(['email', 'password']);

            if (!Auth::attempt($credential)) {
                return ResponseFormatter::error(['message' => 'Unauthorized'], 'Authenticated Failed', 500);
            }

            $user = User::where('email', $request->email)->first();

            if (!Hash::check($request->password, $user->password, [])) {
                throw new Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Authenticated');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 'Authentication Failed', 500);
        }
    }

    public function fetchProfile(Request $request)
    {
        return ResponseFormatter::success([$request->user()], 'Data user login');
    }

    public function updateProfile(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'email' => 'required',
                'username' => 'required',
                'phone' => 'required',
            ]);

            $data = $request->all();
            $user = Auth::user();
            $user->update($data);

            return ResponseFormatter::success($user, 'Profile updated.');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 'Update Profile Failed', 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->user()->currentAccessToken()->delete();

            return ResponseFormatter::success($token, 'Logout.');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong',
                'error' => $error,
            ], 'Something went wrong', 500);
        }
    }
}
