<?php

namespace App\Services\Acumbamail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Acumbamail's API (https://acumbamail.com/apidoc/).
 *
 * Every function is POST https://acumbamail.com/api/1/{function}/ with its
 * arguments as form data, the account's auth_token among them; a dict
 * argument travels as dict[key]=value. Acumbamail answers 200/201 when it
 * worked, 400 for a bad argument, 401 for a wrong token and 429 when a
 * function has been called too often.
 *
 * The token is ACUMBAMAIL_API_KEY (config services.acumbamail.key) and is
 * never written anywhere else.
 */
class AcumbamailClient
{
    private string $baseUrl;

    private ?string $token;

    private int $timeout;

    public function __construct(?string $token = null, ?string $baseUrl = null, ?int $timeout = null)
    {
        $this->token = $token ?? config('services.acumbamail.key');
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('services.acumbamail.url', 'https://acumbamail.com/api/1')), '/');
        $this->timeout = $timeout ?? (int) config('services.acumbamail.timeout', 15);
    }

    public function isConfigured(): bool
    {
        return trim((string) $this->token) !== '';
    }

    /**
     * addSubscriber: put an address on a list. update_subscriber=1 makes adding
     * someone already there a harmless update; complete_json=1 asks for the
     * subscriber back ({"email": ..., "id": ...}). Returns the subscriber id
     * when Acumbamail gives one.
     */
    public function addSubscriber(int $listId, string $email, ?bool $doubleOptin = null): ?string
    {
        $response = $this->call('addSubscriber', [
            'list_id' => $listId,
            'merge_fields' => ['email' => $email],
            'double_optin' => ($doubleOptin ?? (bool) config('services.acumbamail.double_optin', 0)) ? 1 : 0,
            'update_subscriber' => 1,
            'complete_json' => 1,
        ]);

        $json = json_decode($response, true);
        if (is_array($json) && isset($json['id'])) {
            return (string) $json['id'];
        }
        if (is_int($json) || (is_string($json) && ctype_digit($json))) {
            return (string) $json;
        }
        $body = trim($response);

        return ctype_digit($body) ? $body : null;
    }

    /** unsubscribeSubscriber: take an address off a list (it stays known to Acumbamail as unsubscribed). */
    public function unsubscribeSubscriber(int $listId, string $email): void
    {
        $this->call('unsubscribeSubscriber', [
            'list_id' => $listId,
            'email' => $email,
        ]);
    }

    private function call(string $function, array $arguments): string
    {
        if (! $this->isConfigured()) {
            throw new AcumbamailException('ACUMBAMAIL_API_KEY is not set.', 0);
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/' . $function . '/', array_merge(
                    ['auth_token' => (string) $this->token, 'response_type' => 'json'],
                    $arguments
                ));
        } catch (ConnectionException $e) {
            throw new AcumbamailException('Could not reach Acumbamail: ' . $e->getMessage(), 0);
        }

        $status = $response->status();
        if ($response->successful()) {
            return (string) $response->body();
        }

        $said = trim(strip_tags((string) $response->body()));
        $said = $said !== '' ? ': ' . mb_substr($said, 0, 200) : '';

        throw new AcumbamailException(match ($status) {
            401 => 'Acumbamail refused the API key (401). Check ACUMBAMAIL_API_KEY.',
            429 => 'Acumbamail says too many calls for now (429); the next sync carries on.',
            400 => 'Acumbamail did not accept it (400)' . $said,
            default => 'Acumbamail answered HTTP ' . $status . $said,
        }, $status);
    }
}
