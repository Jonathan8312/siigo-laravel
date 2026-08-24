<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

/**
 * Thrown when Siigo responds with HTTP 401, when POST /auth itself is
 * rejected, when Siigo returns an unexpected shape from POST /auth, or
 * when a request is attempted without any credentials configured (no
 * config default and no Siigo::withCredentials() call). statusCode() is
 * null for the two pre-flight cases, since no HTTP response exists yet.
 */
final class AuthenticationException extends SiigoException {}
