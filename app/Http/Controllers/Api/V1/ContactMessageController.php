<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactMessageController extends Controller
{
    /**
     * Submit a contact / support message from website.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'subject' => 'nullable|string|max:150',
            'message' => 'required|string|max:3000',
        ]);

        try {
            $user = $request->user('sanctum');

            $contactMessage = ContactMessage::create([
                'user_id' => $user?->id,
                'name' => trim($validated['name']),
                'phone' => trim($validated['phone']),
                'email' => ! empty($validated['email']) ? strtolower(trim($validated['email'])) : null,
                'subject' => ! empty($validated['subject']) ? trim($validated['subject']) : 'General Inquiry',
                'message' => trim($validated['message']),
                'status' => 'unread',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thank you! Your message has been received. Our support team will get in touch with you shortly.',
                'data' => [
                    'id' => $contactMessage->id,
                    'created_at' => $contactMessage->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Contact message submit error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send your message. Please try again or reach us via WhatsApp/Phone.',
            ], 500);
        }
    }
}

