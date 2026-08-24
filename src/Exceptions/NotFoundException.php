<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

/**
 * Thrown when Siigo responds with HTTP 404: the requested resource does
 * not exist.
 */
final class NotFoundException extends SiigoException {}
