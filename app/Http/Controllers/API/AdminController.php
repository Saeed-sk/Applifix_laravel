<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="admin",
 *     description="Admin panel operations"
 * )
 */
class AdminController extends APIController
{
    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Get list of all users",
     *     tags={"admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */
    public function users()
    {
        $this->authorizeAdmin();
        $users = User::latest()->paginate(10);
        return $this->successResponse($users);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}",
     *     summary="Update user details",
     *     tags={"admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="role", type="string", enum={"user", "admin"}, example="user"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function updateUser(Request $request, $id)
    {
        $this->authorizeAdmin();
        
        $user = User::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:user,admin',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user->update($validator->validated());
        return $this->successResponse($user, 'User updated successfully');
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/users/{id}",
     *     summary="Delete a user",
     *     tags={"admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully"
     *     )
     * )
     */
    public function deleteUser($id)
    {
        $this->authorizeAdmin();
        $user = User::findOrFail($id);
        $user->delete();
        return $this->successResponse(null, 'User deleted successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/admin/error-logs",
     *     summary="Get system error logs",
     *     tags={"admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"error", "warning", "info"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of error logs",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="level", type="string", example="error"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="context", type="object"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     )
     * )
     */
    public function errorLogs(Request $request)
    {
        $this->authorizeAdmin();
        
        $query = ErrorLog::query();
        
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }
        
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }
        
        $logs = $query->latest()->paginate(20);
        return $this->successResponse($logs);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/visits",
     *     summary="Get website visits statistics",
     *     tags={"admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Visit statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_visits", type="integer", example=1000),
     *                 @OA\Property(property="unique_visitors", type="integer", example=500),
     *                 @OA\Property(property="visits_by_date", type="array", @OA\Items(
     *                     @OA\Property(property="date", type="string", format="date"),
     *                     @OA\Property(property="count", type="integer")
     *                 ))
     *             )
     *         )
     *     )
     * )
     */
    public function visits(Request $request)
    {
        $this->authorizeAdmin();
        
        $query = Visit::query();
        
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $statistics = [
            'total_visits' => $query->count(),
            'unique_visitors' => $query->distinct('ip_address')->count(),
            'visits_by_date' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                    ->groupBy('date')
                                    ->orderBy('date')
                                    ->get()
        ];
        
        return $this->successResponse($statistics);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Get admin dashboard statistics",
     *     tags={"admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_users", type="integer", example=100),
     *                 @OA\Property(property="total_topics", type="integer", example=50),
     *                 @OA\Property(property="total_chats", type="integer", example=200),
     *                 @OA\Property(property="recent_errors", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function dashboard()
    {
        $this->authorizeAdmin();
        
        $statistics = [
            'total_users' => User::count(),
            'total_topics' => \App\Models\Topic::count(),
            'total_chats' => \App\Models\Chat::count(),
            'recent_errors' => ErrorLog::whereDate('created_at', today())->count()
        ];
        
        return $this->successResponse($statistics);
    }

    private function authorizeAdmin()
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized access');
        }
    }
} 