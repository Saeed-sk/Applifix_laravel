<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\APIController;
use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="contact",
 *     description="Contact us form operations"
 * )
 */
class ContactUsController extends APIController
{
    /**
     * @OA\Get(
     *     path="/api/contact",
     *     summary="Get list of contact messages",
     *     tags={"contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of contact messages",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="subject", type="string", example="Service Inquiry"),
     *                 @OA\Property(property="message", type="string", example="I need help with my appliance"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $messages = ContactUs::latest()->paginate(10);
        return $this->successResponse($messages);
    }

    /**
     * @OA\Post(
     *     path="/api/contact",
     *     summary="Submit a contact form",
     *     tags={"contact"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","subject","message"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="subject", type="string", example="Service Inquiry"),
     *             @OA\Property(property="message", type="string", example="I need help with my appliance")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message sent successfully"),
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $contact = ContactUs::create($validator->validated());
        return $this->successResponse($contact, 'Message sent successfully', 201);
    }

    /**
     * @OA\Get(
     *     path="/api/contact/{id}",
     *     summary="Get a specific contact message",
     *     tags={"contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact message details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="subject", type="string", example="Service Inquiry"),
     *                 @OA\Property(property="message", type="string", example="I need help with my appliance"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found"
     *     )
     * )
     */
    public function show(ContactUs $contactUs)
    {
        return $this->successResponse($contactUs);
    }

    /**
     * @OA\Delete(
     *     path="/api/contact/{id}",
     *     summary="Delete a contact message",
     *     tags={"contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Message deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found"
     *     )
     * )
     */
    public function destroy(ContactUs $contactUs)
    {
        $contactUs->delete();
        return $this->successResponse(null, 'Message deleted successfully');
    }
}
