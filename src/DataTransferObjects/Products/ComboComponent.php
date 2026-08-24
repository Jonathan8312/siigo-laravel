<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Products;

/**
 * A component sent when creating/updating a `Combo`-type product,
 * referencing another product by its `code`. Siigo returns a
 * differently-shaped object for the same field — see
 * {@see ComboComponentDetails}.
 */
final class ComboComponent
{
    public function __construct(
        public readonly string $code,
        public readonly float $quantity,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['code' => $this->code, 'quantity' => $this->quantity];
    }
}
