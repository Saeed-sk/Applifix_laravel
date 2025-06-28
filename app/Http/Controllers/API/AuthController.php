<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends APIController
{
    /**
     * Register a new user
     *
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration payload",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"name","email","password","password_confirmation"},
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="password"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="password")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User successfully registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration successful"),
     *             @OA\Property(
     *                 property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return $this->successResponse(
            ['user' => $user, 'token' => $token],
            'Registration successful',
            201
        );
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Authenticate user and issue token",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User login credentials",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email","password"},
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", example="password")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="jwt-token-here")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->errorResponse('Unauthorized', 401);
        }

        return $this->successResponse([
            'token' => $token,
            'user' => Auth::user()
        ], 'Login successful');
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Log out user (invalidate token)",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token is invalid or already logged out"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::parseToken());

            return $this->successResponse(null, 'Logout successful');
        } catch (TokenInvalidException $e) {
            return $this->errorResponse('Token is invalid or already logged out', 401);
        } catch (\Exception $e) {
            return $this->errorResponse('could not logout', 500);
        }
    }

    /**
     * Get authenticated user details
     *
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Auth"},
     *     summary="Get authenticated user details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com")
     *             )
     *         )
     *     )
     * )
     */
    public function me()
    {
        return $this->successResponse(JWTAuth::user(), 'User details retrieved');
    }


    /**
     * @OA\Post(
     *     path="/api/user/profile",
     *     summary="Update user profile",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "phone"},
     *                 @OA\Property(property="name", type="string", example="Ali Rezaei"),
     *                 @OA\Property(property="email", type="string", example="ali@example.com"),
     *                 @OA\Property(property="phone", type="string", example="09121234567"),
     *                 @OA\Property(property="image", type="file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Profile updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error"
     *     )
     * )
     */
    public function profile(Request $request)
    {

        try {
            $validated = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|string|email',
                'phone' => 'required|string',
                'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validated->fails()) {
                return $this->errorResponse($validated->errors(), 422);
            }

            $data = $validated->validated();
            $image = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $image = $image->store('images/users', 'public');
            }

            User::query()->where('id', auth()->id())->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'avatar' => $image
            ]);

            return $this->successResponse(null, 'Profile updated');
        } catch (\Exception $e) {
            return $this->errorResponse("Unexpected error: " . $e->getMessage(), 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/user/password",
     *     summary="Update user password",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"old_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="old_password", type="string", example="oldpass123"),
     *             @OA\Property(property="new_password", type="string", example="newpass123"),
     *             @OA\Property(property="new_password_confirmation", type="string", example="newpass123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Password updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Incorrect old password"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Unexpected error"
     *     )
     * )
     */
    public function updatePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string|min:6',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $user = Auth::guard('api')->user();

            if (!Hash::check($request->old_password, $user->password)) {
                return $this->errorResponse('The provided password was incorrect.', 401);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return $this->successResponse(null, 'Password updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse("Unexpected error: " . $e->getMessage(), 500);
        }
    }
}
