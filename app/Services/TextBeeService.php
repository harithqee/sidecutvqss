<?php

namespace App\Services;

use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class TextBeeService
{
    /**
     * Compile a MessageTemplate's message_body with the given variables.
     */
    public function compile(MessageTemplate $template, array $variables): array
    {
        preg_match_all('/{{\s*(\w+)\s*}}/', $template->message_body, $matches);
        $placeholders = array_unique($matches[1]);

        $missing = array_filter($placeholders, fn ($key) => !array_key_exists($key, $variables) || $variables[$key] === null);

        if (!empty($missing)) {
            throw new InvalidArgumentException(
                "Missing required variables for template '{$template->name}': " . implode(', ', $missing)
            );
        }

        $message = preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($m) use ($variables) {
            return $variables[$m[1]] ?? $m[0];
        }, $template->message_body);

        return ['name' => $template->name, 'message' => $message];
    }

    /**
     * Compile a template by its database ID and send it via TextBee.
     */
    public function sendById(int $templateId, string $phone, array $variables): array
    {
        $template = MessageTemplate::findOrFail($templateId);

        $compiled = $this->compile($template, $variables);
        $response = $this->send($phone, $compiled['message']);

        return [
            'template' => $compiled['name'],
            'message' => $compiled['message'],
            'textbee_response' => $response,
        ];
    }

    /**
     * Send a raw, already-composed message via TextBee.
     */
    public function send(string $phone, string $message): array
    {
        $apiKey = config('services.textbee.api_key');
        $endpoint = config('services.textbee.endpoint');

        if (!$apiKey) {
            throw new InvalidArgumentException('TEXTBEE_API_KEY is not set in .env');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(10)->post($endpoint, [
            'recipients' => [$phone],
            'message' => $message,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('TextBee API error: ' . $response->body());
        }

        return $response->json();
    }
}