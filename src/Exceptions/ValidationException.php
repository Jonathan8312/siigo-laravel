<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

/**
 * Thrown when Siigo responds with HTTP 400.
 *
 * Siigo reuses 400 for a wide range of `Code` values beyond field
 * validation — configuration errors (`company_settings`), business rule
 * violations (`already_exists`, `duplicated_document`,
 * `blocked_transactions`), and missing/invalid headers
 * (`header_required`, `invalid_partner_id`) all share this status.
 * Inspect {@see SiigoException::errors()} / {@see SiigoException::errorCode()}
 * to branch on the specific `Code` Siigo returned.
 */
final class ValidationException extends SiigoException {}
