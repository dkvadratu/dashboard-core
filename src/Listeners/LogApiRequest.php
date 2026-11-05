<?php

namespace Dkvadratu\DashboardCore\Listeners;

use Dkvadratu\DashboardCore\Models\ApiRequestLog;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Throwable;

class LogApiRequest
{
    public function handle(RequestHandled $event): void
    {
        $request = $event->request;
        $isApi = $request->is('api', 'api/*') || in_array('api', $request->route()?->gatherMiddleware() ?? [], true);
        if (! config($isApi ? 'dashboard.log_api_requests' : 'dashboard.log_http_requests')) {
            return;
        }

        try {
            $detailed = (bool) config('dashboard.log_detailed');
            ApiRequestLog::query()->create([
                'request_type' => $isApi ? 'api' : 'http',
                'method' => substr($request->method(), 0, 10),
                'path' => mb_substr($request->path(), 0, 2048),
                'status' => $event->response->getStatusCode(),
                'ip_address' => $request->ip(),
                'request_headers' => $detailed ? $this->headers($request->headers->all()) : null,
                'response_headers' => $detailed ? $this->headers($event->response->headers->all()) : null,
                'request_body' => $detailed ? $this->body($request->getContent(), $request->header('Content-Type'), allowForm: true) : null,
                'response_body' => $detailed ? $this->body($event->response->getContent(), $event->response->headers->get('Content-Type')) : null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @return array<mixed>|null */
    private function body(string|false $body, ?string $contentType, bool $allowForm = false): ?array
    {
        if (! is_string($body) || strlen($body) > 65536) {
            return null;
        }

        $contentType = strtolower($contentType ?? '');
        if (str_contains($contentType, 'json')) {
            $decoded = json_decode($body, true, 32);
        } elseif ($allowForm && str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($body, $decoded);
        } else {
            return null;
        }

        return is_array($decoded) ? $this->redact($decoded) : null;
    }

    /**
     * @param  array<string, array<string>>  $headers
     * @return array<mixed>|null
     */
    private function headers(array $headers): ?array
    {
        $headers = $this->redact($headers);
        foreach (['authorization', 'proxy-authorization', 'cookie', 'set-cookie', 'x-api-key', 'x-auth-token', 'x-csrf-token', 'x-xsrf-token', 'location', 'referer'] as $key) {
            if (array_key_exists($key, $headers)) {
                $headers[$key] = ['[REDACTED]'];
            }
        }

        return strlen(json_encode($headers, JSON_INVALID_UTF8_SUBSTITUTE)) <= 65536 ? $headers : null;
    }

    /**
     * @param  array<mixed>  $values
     * @return array<mixed>
     */
    private function redact(array $values): array
    {
        $normalize = static fn (string $key): string => str_replace('-', '_', strtolower($key));
        $sensitive = array_map($normalize, config('dashboard.redacted_keys', []));
        foreach ($values as $key => $value) {
            if (in_array($normalize((string) $key), $sensitive, true) || in_array($normalize((string) str((string) $key)->afterLast('.')), $sensitive, true)) {
                $values[$key] = is_array($value) ? ['[REDACTED]'] : '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
