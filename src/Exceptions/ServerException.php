<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

/**
 * Thrown when Siigo responds with HTTP 408, 500, 503, or 504 — Siigo
 * failed to process the request in time, or failed unexpectedly on its
 * side. A response was received (unlike {@see ConnectionException}, for
 * which no response ever arrives), it just reports server-side failure.
 */
final class ServerException extends SiigoException {}
