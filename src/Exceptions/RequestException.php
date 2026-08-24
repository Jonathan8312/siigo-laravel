<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

use Jonathan8312\Siigo\Http\ClientConfiguration;

/**
 * Catch-all for any non-successful Siigo response not specifically
 * mapped to {@see AuthenticationException} (401), {@see ValidationException}
 * (400), {@see NotFoundException} (404), {@see RateLimitException} (429),
 * or {@see ServerException} (408/500/503/504). This includes 403, 409,
 * 415, and any other undocumented status code — no additional semantics
 * are inferred for status codes Siigo has not documented.
 *
 * Also thrown, regardless of status code, when a response body exceeds
 * {@see ClientConfiguration::$maxResponseBytes}.
 */
final class RequestException extends SiigoException {}
