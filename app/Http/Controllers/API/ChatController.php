<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatHistory;
use App\Models\Topic;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use OpenApi\Annotations as OA;

class ChatController extends APIController
{
    protected string $openApiUrl = 'https://api.openai.com/v1/chat/completions';
    /**
     * @OA\Get(
     *     path="/api/chat",
     *     summary="List user chats",
     *     tags={"chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user chats",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    public function index()
    {
        $chats = Chat::query()->where('user_id', Auth::id())->with('history')->paginate(10);
        return $this->successResponse($chats);
    }

    /**
     * @OA\Post(
     *     path="/api/chat/topic",
     *     summary="Create chat using topic description",
     *     tags={"chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="topic_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="string", example="Refrigerator repair tips...")
     *         )
     *     )
     * )
     */
    public function createWithTopic(Request $request)
    {
        $topic_id = $request->input('topic_id');
        $topic = Topic::query()->firstWhere('id', $topic_id);
        return $this->create($request->merge(['message'=>$topic->description, 'topic_id'=>$topic_id]));
    }

    /**
     * @OA\Post(
     *     path="/api/chat",
     *     summary="Send a message to GPT specialized in home appliance repair",
     *     description="Sends a user message to the GPT API and saves the conversation if the user is authenticated. GPT responds only about home appliance repairs.",
     *     tags={"chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="My washing machine won't start. What should I check?"),
     *             @OA\Property(property="chat_id", type="integer", example=null),
     *             @OA\Property(property="topic_id", type="integer", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="GPT response and chat ID",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="ai_message", type="string", example="Check the door latch and power supply..."),
     *                 @OA\Property(property="chat_id", type="integer", example=12)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server or API error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="OpenAI API error: ...")
     *         )
     *     )
     * )
     */


    public function create(Request $request)
    {
        $client = new Client();
        $apikey = config('services.openai.api_key');
        $message = $request->input('message');
        $chat_id = $request->input('chat_id' , null);
        $topic_id = $request->input('topic_id' , null);

        try {
            $response = $client->post($this->openApiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' .$apikey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a specialized assistant in the field of home appliance and electronics repair. Your role is to assist users in diagnosing, troubleshooting, and repairing a wide range of household and electronic devices, such as refrigerators, televisions, washing machines, mobile phones, vacuum cleaners, microwaves, air conditioners, and other appliances. Please respond only with repair-related guidance. Do not answer questions outside this field.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $message
                        ]
                    ]
                ]
            ]);

            $body = json_decode($response->getBody(), true);

            if ($response->getStatusCode() === 200 && Auth::check()) {
                $user_id = Auth::id();
                if ($chat_id === null) {
                    $title = '';
                    if ($topic_id !== null) {
                        $title = Topic::query()->find($topic_id)->title;
                    }else{
                        $title = $message;
                    }
                    $chat_id = $this->storeChat($topic_id , $user_id, $title);
                }
                $this->storeHistory($chat_id, $user_id, $message, 'user');
                $this->storeHistory($chat_id, $user_id, $body['choices'][0]['message']['content'] ?? 'No response received.', 'assistant');
            }

            return $this->successResponse([
                'ai_message' => $body['choices'][0]['message']['content'],
                'chat_id' => $chat_id
            ]);

        } catch (ConnectException $e) {
            return $this->errorResponse('Connection error: ' . $e->getMessage(), 500);

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            return $this->errorResponse('OpenAI API error: ' . $responseBody, 500);

        } catch (\Exception $e) {
            return $this->errorResponse('Unexpected error: ' . $e->getMessage(), 500);
        } catch (GuzzleException $e) {
            return $this->errorResponse('Guzzle error: ' . $e->getMessage(), 500);
        }
    }

    private function storeChat($topic_id , $user_id, $title)
    {
        $chat = Chat::query()->create([
            'title' => $title,
            'topic_id' => $topic_id,
            'user_id' => $user_id
        ]);

        return $chat->id;
    }
    private function storeHistory($chat_id, $user_id, $message, $role)
    {
        ChatHistory::query()->create([
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'message' => $message,
            'role' => $role
        ]);
        return ;
    }
    /**
     * @OA\Get(
     *     path="/api/chat/{chat}",
     *     summary="Get chat history",
     *     tags={"chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat history",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     )
     * )
     */
    public function show(Chat $chat)
    {
            $chatHistory = ChatHistory::query()->where('chat_id' , $chat->id)->get();
        return $this->successResponse($chatHistory);
    }

    /**
     * @OA\Post(
     *     path="/api/chat/{chat}",
     *     summary="Delete chat",
     *     tags={"chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="string", example="Chat deleted successfully.")
     *         )
     *     )
     * )
     */
    public function destroy(Chat $chat)
    {
        $chat->delete();
        return $this->successResponse('Chat deleted successfully.');
    }
}
