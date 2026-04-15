<?php

namespace App\Services;

use App\Models\AiChatMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    private const MODEL = 'gemini-2.5-flash';

    private const SYSTEM_INSTRUCTION = 'You are a fitness assistant for SmartFit. Answer ONLY questions about workouts, sports, nutrition, health and calories. For any other topics politely explain that you specialize only in fitness. Be friendly and motivating. Always reply in the same language the user writes in.';

    private const CALORIE_PROMPT = 'Look at this food photo. Identify the dishes/ingredients, estimate the portion weight and calculate: calories, protein, fat, carbohydrates. Give a brief answer with a summary nutrition table. If the photo does not contain food — say so.';

    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL;
    }

    public function chat(int $telegramUserId, string $message): string
    {
        $history = AiChatMessage::query()
            ->where('telegram_user_id', $telegramUserId)
            ->latest()
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        $contents = $history->map(fn ($msg) => [
            'role' => $msg->role,
            'parts' => [['text' => $msg->content]],
        ])->toArray();

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => self::SYSTEM_INSTRUCTION]],
            ],
            'contents' => $contents,
        ];

        $reply = $this->callGemini(':generateContent', $payload);

        AiChatMessage::create([
            'telegram_user_id' => $telegramUserId,
            'role' => 'user',
            'content' => $message,
            'is_photo' => false,
        ]);

        AiChatMessage::create([
            'telegram_user_id' => $telegramUserId,
            'role' => 'model',
            'content' => $reply,
            'is_photo' => false,
        ]);

        return $reply;
    }

    public function chatPhoto(int $telegramUserId, string $base64Image, string $mimeType): string
    {
        $payload = [
            'system_instruction' => [
                'parts' => [['text' => self::SYSTEM_INSTRUCTION]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['text' => self::CALORIE_PROMPT],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $base64Image,
                        ],
                    ],
                ],
            ]],
        ];

        $reply = $this->callGemini(':generateContent', $payload);

        AiChatMessage::create([
            'telegram_user_id' => $telegramUserId,
            'role' => 'user',
            'content' => '📷 Photo analyzed',
            'is_photo' => true,
        ]);

        AiChatMessage::create([
            'telegram_user_id' => $telegramUserId,
            'role' => 'model',
            'content' => $reply,
            'is_photo' => true,
        ]);

        return $reply;
    }

    private function callGemini(string $endpoint, array $payload): string
    {
        $url = $this->baseUrl . $endpoint . '?key=' . $this->apiKey;

        $response = Http::timeout(30)
            ->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return 'Sorry, an error occurred. Please try again.';
        }

        $data = $response->json();

        return $data['candidates'][0]['content']['parts'][0]['text']
            ?? 'Failed to get a response.';
    }
}
