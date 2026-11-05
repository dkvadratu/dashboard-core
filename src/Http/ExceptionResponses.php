<?php

namespace Dkvadratu\DashboardCore\Http;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ExceptionResponses
{
    public static function configure(Exceptions $exceptions): void
    {
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api', 'api/*') || $request->expectsJson());
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();
            if ($status < 400 || config('app.debug')) {
                return $response;
            }

            if ($status < 500 && $response instanceof JsonResponse) {
                $response->headers->set('Cache-Control', 'no-store, private');

                return $response;
            }

            $headers = $response->headers->all();
            foreach (['content-type', 'content-length', 'content-encoding', 'content-disposition', 'etag', 'last-modified', 'expires'] as $header) {
                unset($headers[$header]);
            }
            $headers['Cache-Control'] = 'no-store, private';
            $headers['X-Content-Type-Options'] = 'nosniff';
            $message = Response::$statusTexts[$status] ?? 'Error';

            return ($request->is('api', 'api/*') || $request->expectsJson()
                ? new JsonResponse(['message' => $message], $status, $headers)
                : new Response($message, $status, [...$headers, 'Content-Type' => 'text/plain; charset=UTF-8']))->prepare($request);
        });
    }
}
