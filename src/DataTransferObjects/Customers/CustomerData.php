<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\Enums\CustomerType;
use Jonathan8312\Siigo\Enums\PersonType;
use Jonathan8312\Siigo\Resources\Customers;

/**
 * The payload sent to `POST /v1/customers` and `PUT /v1/customers/{id}`.
 *
 * `PUT` is a full replace, not a partial patch — Siigo's own docs state
 * omitted fields are left empty rather than preserved from the previous
 * state. Always pass a complete CustomerData to
 * {@see Customers::update()}, not just the
 * fields that changed.
 *
 * `id_type` stays a plain string (not an enum): no catalog endpoint for
 * identification types was confirmed during research (see
 * docs/known-issues.md), so the SDK cannot validate it client-side.
 */
final class CustomerData
{
    /**
     * @param  list<string>  $name  1 element for a company, 2 for a person (given + last names)
     * @param  list<FiscalResponsibility>  $fiscalResponsibilities
     * @param  list<Phone>  $phones
     * @param  list<Contact>  $contacts
     * @param  list<CustomField>  $customFields
     */
    public function __construct(
        public readonly PersonType $personType,
        public readonly string $idType,
        public readonly string $identification,
        public readonly array $name,
        public readonly array $fiscalResponsibilities,
        public readonly Address $address,
        public readonly CustomerType $type = CustomerType::Customer,
        public readonly ?string $checkDigit = null,
        public readonly ?string $commercialName = null,
        public readonly int $branchOffice = 0,
        public readonly bool $active = true,
        public readonly bool $vatResponsible = false,
        public readonly array $phones = [],
        public readonly array $contacts = [],
        public readonly ?string $comments = null,
        public readonly ?RelatedUsers $relatedUsers = null,
        public readonly array $customFields = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'person_type' => $this->personType->value,
            'id_type' => $this->idType,
            'identification' => $this->identification,
            'check_digit' => $this->checkDigit,
            'name' => $this->name,
            'commercial_name' => $this->commercialName,
            'branch_office' => $this->branchOffice,
            'active' => $this->active,
            'vat_responsible' => $this->vatResponsible,
            'fiscal_responsibilities' => array_map(
                static fn (FiscalResponsibility $responsibility): array => $responsibility->toArray(),
                $this->fiscalResponsibilities,
            ),
            'address' => $this->address->toArray(),
            'phones' => array_map(static fn (Phone $phone): array => $phone->toArray(), $this->phones),
            'contacts' => array_map(static fn (Contact $contact): array => $contact->toArray(), $this->contacts),
            'comments' => $this->comments,
            'related_users' => $this->relatedUsers?->toArray(),
            'custom_fields' => array_map(
                static fn (CustomField $field): array => $field->toArray(),
                $this->customFields,
            ),
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
