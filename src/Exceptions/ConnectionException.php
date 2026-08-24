<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

/**
 * Thrown when no HTTP response was ever received from Siigo: DNS
 * failure, refused connection, TLS failure, or a connection/request
 * timeout. {@see SiigoException::response()} is always null for this
 * exception, since no response exists.
 */
final class ConnectionException extends SiigoException {}
