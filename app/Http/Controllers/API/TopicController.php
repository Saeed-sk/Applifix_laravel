<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class TopicController extends APIController
{
    /**
     * @OA\Get(
     *     path="/api/topics",
     *     summary="Get list of topics",
     *     tags={"topics"},
     *     @OA\Response(
     *         response=200,
     *         description="List of topics",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return $this->successResponse(Topic::query()->latest()->paginate(10));
    }

    /**
     * @OA\Post(
     *     path="/api/topics",
     *     summary="Create a new topic",
     *     tags={"topics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "description", "image"},
     *                 @OA\Property(property="title", type="string", example="Refrigerator Repair"),
     *                 @OA\Property(property="description", type="string", example="Common issues with home refrigerators"),
     *                 @OA\Property(property="image", type="file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Topic created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function create(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validated->fails()) {
            return $this->errorResponse($validated->errors());
        }

        $data = $validated->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $data['src'] = $image->store('images/topics', 'public');
        }

        $topic = Topic::create($data);
        return $this->successResponse($topic);
    }

    /**
     * @OA\Post(
     *     path="/api/topics/{id}",
     *     summary="Update an existing topic",
     *     tags={"topics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the topic to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "description"},
     *                 @OA\Property(property="title", type="string", example="Updated Refrigerator Repair"),
     *                 @OA\Property(property="description", type="string", example="Updated guide for common refrigerator issues"),
     *                 @OA\Property(property="image", type="file", description="Optional. New image for the topic")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Topic updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Topic updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Topic not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function update(Request $request,$id)
    {
        // Find the topic or return 404
        $topic = Topic::findOrFail($id);

        // Validate incoming request; image is optional on update
        $validated = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'required|string|max:65000',
            'image'       => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validated->fails()) {
            return $this->errorResponse($validated->errors(), 422);
        }

        $data = $validated->validated();

        // If a new image is uploaded, delete the old one and store the new
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($topic->src && Storage::disk('public')->exists($topic->src)) {
                Storage::disk('public')->delete($topic->src);
            }
            $image       = $request->file('image');
            $data['src'] = $image->store('images/topics', 'public');
        }

        // Update the topic with validated data
        $topic->update($data);

        return $this->successResponse($topic);
    }


    /**
     * @OA\Delete(
     *     path="/api/topics",
     *     summary="Delete a topic",
     *     tags={"topics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id"},
     *             @OA\Property(property="id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Topic deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="null", example="null"),
     *             @OA\Property(property="message", type="string", example="topic deleted successfully"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Topic not found"
     *     )
     * )
     */
    public function destroy(Request $request)
    {
        if (!$request->has('id')) {
            return $this->errorResponse('id is required');
        }

        $topic = Topic::find($request->input('id'));

        if (!$topic) {
            return $this->errorResponse('topic not found', 404);
        }

        if ($topic->src && Storage::disk('public')->exists($topic->src)) {
            Storage::disk('public')->delete($topic->src);
        }

        $topic->delete();

        return $this->successResponse('', 'topic deleted successfully',200);
    }
}
