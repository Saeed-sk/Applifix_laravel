<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="chat-history",
 *     description="Chat history operations"
 * )
 */
class ChatHistoryController extends APIController
{
    /**
     * @OA\Get(
     *     path="/api/chat-history",
     *     summary="Get user's chat history",
     *     tags={"chat-history"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of chat history entries",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="chat_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="message", type="string", example="How can I fix my dishwasher?"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $history = ChatHistory::where('user_id', Auth::id())
            ->with('chat')
            ->latest()
            ->paginate(20);
        return $this->successResponse($history);
    }

    /**
     * @OA\Post(
     *     path="/api/chat-history",
     *     summary="Create a new chat history entry",
     *     tags={"chat-history"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chat_id","message","role"},
     *             @OA\Property(property="chat_id", type="integer", example=1),
     *             @OA\Property(property="message", type="string", example="How can I fix my dishwasher?"),
     *             @OA\Property(property="role", type="string", enum={"user", "assistant"}, example="user")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="History entry created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chat history entry created"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|exists:chats,id',
            'message' => 'required|string',
            'role' => 'required|in:user,assistant'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $history = ChatHistory::create([
            'chat_id' => $request->chat_id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'role' => $request->role
        ]);

        return $this->successResponse($history, 'Chat history entry created', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/chat-history/{id}",
     *     summary="Get a specific chat history entry",
     *     tags={"chat-history"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat history entry details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="chat_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="message", type="string", example="How can I fix my dishwasher?"),
     *                 @OA\Property(property="role", type="string", example="user"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="History entry not found"
     *     )
     * )
     */
    public function show(ChatHistory $chatHistory)
    {
        // Check if the history belongs to the authenticated user
        if ($chatHistory->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized access', 403);
        }
        return $this->successResponse($chatHistory);
    }

    /**
     * @OA\Delete(
     *     path="/api/chat-history/{id}",
     *     summary="Delete a chat history entry",
     *     tags={"chat-history"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="History entry deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Chat history entry deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="History entry not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     )
     * )
     */
    public function destroy(ChatHistory $chatHistory)
    {
        // Check if the history belongs to the authenticated user
        if ($chatHistory->user_id !== Auth::id()) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $chatHistory->delete();
        return $this->successResponse(null, 'Chat history entry deleted');
    }

}
