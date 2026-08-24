<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\DataTransferObjects\Customers;

use Jonathan8312\Siigo\DataTransferObjects\Support\ArrayShape;
use Jonathan8312\Siigo\Enums\CustomerType;
use Jonathan8312\Siigo\Enums\PersonType;

/**
 * A customer/third party as returned by Siigo — from `POST`, `GET`,
 * `GET /{id}`, `PUT`, and each entry of the `GET` list's `results[]`.
 */
final class Customer
{
    /**
     * @param  list<string>  $name
     * @param  list<FiscalResponsibility>  $fiscalResponsibilities
     * @param  list<Phone>  $phones
     * @param  list<Contact>  $contacts
     * @param  list<CustomField>  $customFields
     */
    public function __construct(
        public readonly string $id,
        public readonly CustomerType $type,
        public readonly PersonType $personType,
        public readonly ?IdType $idType,
        public readonly string $identification,
        public readonly int $branchOffice,
        public readonly ?string $checkDigit,
        public readonly array $name,
        public readonly ?string $commercialName,
        public readonly bool $active,
        public readonly bool $vatResponsible,
        public readonly array $fiscalResponsibilities,
        public readonly ?AddressDetails $address,
        public readonly array $phones,
        public readonly array $contacts,
        public readonly ?string $comments,
        public readonly ?RelatedUsers $relatedUsers,
        public readonly array $customFields,
        public readonly ?CustomerMetadata $metadata,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $idType = $data['id_type'] ?? null;
        $address = $data['address'] ?? null;
        $relatedUsers = $data['related_users'] ?? null;
        $metadata = $data['metadata'] ?? null;

        return new self(
            id: ArrayShape::string($data, 'id'),
            type: CustomerType::tryFrom(ArrayShape::string($data, 'type')) ?? CustomerType::Customer,
            personType: PersonType::tryFrom(ArrayShape::string($data, 'person_type')) ?? PersonType::Person,
            idType: is_array($idType) ? IdType::fromArray($idType) : null,
            identification: ArrayShape::string($data, 'identification'),
            branchOffice: ArrayShape::int($data, 'branch_office'),
            checkDigit: ArrayShape::nullableString($data, 'check_digit'),
            name: self::stringList($data['name'] ?? null),
            commercialName: ArrayShape::nullableString($data, 'commercial_name'),
            active: ArrayShape::bool($data, 'active', true),
            vatResponsible: ArrayShape::bool($data, 'vat_responsible'),
            fiscalResponsibilities: self::mapList($data['fiscal_responsibilities'] ?? null, FiscalResponsibility::fromArray(...)),
            address: is_array($address) ? AddressDetails::fromArray($address) : null,
            phones: self::mapList($data['phones'] ?? null, Phone::fromArray(...)),
            contacts: self::mapList($data['contacts'] ?? null, Contact::fromArray(...)),
            comments: ArrayShape::nullableString($data, 'comments'),
            relatedUsers: is_array($relatedUsers) ? RelatedUsers::fromArray($relatedUsers) : null,
            customFields: self::mapList($data['custom_fields'] ?? null, CustomField::fromArray(...)),
            metadata: is_array($metadata) ? CustomerMetadata::fromArray($metadata) : null,
        );
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $json): array
    {
        if (! is_array($json)) {
            return [];
        }

        return array_values(array_filter($json, 'is_string'));
    }

    /**
     * @template TItem
     *
     * @param  \Closure(array<array-key, mixed>): TItem  $mapItem
     * @return list<TItem>
     */
    private static function mapList(mixed $json, \Closure $mapItem): array
    {
        if (! is_array($json)) {
            return [];
        }

        $items = [];

        foreach ($json as $entry) {
            if (is_array($entry)) {
                $items[] = $mapItem($entry);
            }
        }

        return $items;
    }
}
