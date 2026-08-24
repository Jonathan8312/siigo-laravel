<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Unit\Exceptions;

use Jonathan8312\Siigo\Exceptions\SiigoError;
use PHPUnit\Framework\TestCase;

final class SiigoErrorTest extends TestCase
{
    public function test_parses_the_real_siigo_error_shape(): void
    {
        $errors = SiigoError::manyFromResponseBody([
            'Status' => 400,
            'Errors' => [[
                'Code' => 'parameter_required',
                'Message' => 'The field code is required',
                'Params' => ['code'],
                'Detail' => 'Check the API documentation',
            ]],
        ]);

        $this->assertCount(1, $errors);
        $this->assertSame('parameter_required', $errors[0]->code);
        $this->assertSame('The field code is required', $errors[0]->message);
        $this->assertSame(['code'], $errors[0]->params);
        $this->assertSame('Check the API documentation', $errors[0]->detail);
    }

    public function test_parses_multiple_errors_in_one_response(): void
    {
        $errors = SiigoError::manyFromResponseBody([
            'Errors' => [
                ['Code' => 'parameter_required', 'Message' => 'a', 'Params' => [], 'Detail' => null],
                ['Code' => 'invalid_email', 'Message' => 'b', 'Params' => [], 'Detail' => null],
            ],
        ]);

        $this->assertCount(2, $errors);
        $this->assertSame('parameter_required', $errors[0]->code);
        $this->assertSame('invalid_email', $errors[1]->code);
    }

    public function test_returns_an_empty_list_for_a_non_array_body(): void
    {
        $this->assertSame([], SiigoError::manyFromResponseBody(null));
        $this->assertSame([], SiigoError::manyFromResponseBody('not an array'));
    }

    public function test_returns_an_empty_list_when_errors_key_is_missing_or_not_an_array(): void
    {
        $this->assertSame([], SiigoError::manyFromResponseBody(['Status' => 500]));
        $this->assertSame([], SiigoError::manyFromResponseBody(['Errors' => 'not an array']));
    }

    public function test_tolerates_missing_or_malformed_fields_within_an_entry(): void
    {
        $errors = SiigoError::manyFromResponseBody(['Errors' => [['Params' => 'not-an-array']]]);

        $this->assertCount(1, $errors);
        $this->assertNull($errors[0]->code);
        $this->assertNull($errors[0]->message);
        $this->assertSame([], $errors[0]->params);
        $this->assertNull($errors[0]->detail);
    }
}
