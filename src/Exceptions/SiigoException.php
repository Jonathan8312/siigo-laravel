<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Exceptions;

use Jonathan8312\Siigo\Http\SiigoResponse;

/**
 * Base type for every exception thrown by the SDK.
 *
 * Instances never carry credentials, Authorization headers, or
 * unredacted request payloads. Only the HTTP method + path ("endpoint"),
 * the status code (when one was actually received), the parsed
 * {@see SiigoError} list, and the raw {@see SiigoResponse} (when one
 * exists) are preserved.
 */
abstract class SiigoException extends \RuntimeException
{
    /**
     * @param  list<SiigoError>  $errors
     */
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        private readonly ?string $endpoint = null,
        private readonly array $errors = [],
        private readonly ?SiigoResponse $response = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * The HTTP method and path that were being requested, e.g.
     * "GET v1/customers". Never includes query parameters, headers, or
     * the configured base URL.
     */
    public function endpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * @return list<SiigoError>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Convenience accessor for the `Code` of the first Siigo error entry
     * (e.g. "already_exists", "parameter_required"), when one exists.
     * Inspect {@see self::errors()} directly to handle more than one.
     */
    public function errorCode(): ?string
    {
        return $this->errors[0]->code ?? null;
    }

    /**
     * The raw Siigo response that caused this exception, when one was
     * actually received (absent for pre-flight failures such as missing
     * credentials, or transport-level failures where no response was
     * ever returned).
     */
    public function response(): ?SiigoResponse
    {
        return $this->response;
    }
}
