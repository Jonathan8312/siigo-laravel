<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo\Tests\Staging;

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Auth\CacheTokenRepository;
use Jonathan8312\Siigo\DataTransferObjects\Customers\Address;
use Jonathan8312\Siigo\DataTransferObjects\Customers\City;
use Jonathan8312\Siigo\DataTransferObjects\Customers\CustomerData;
use Jonathan8312\Siigo\DataTransferObjects\Customers\FiscalResponsibility;
use Jonathan8312\Siigo\Enums\PersonType;
use Jonathan8312\Siigo\Exceptions\NotFoundException;
use Jonathan8312\Siigo\Exceptions\RequestException;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Http\ClientConfiguration;
use Jonathan8312\Siigo\Resources\Customers;

/**
 * Fase 3 real-world verification. Unlike every other staging test in
 * this suite, this one performs writes (create/update/delete) — that
 * side effect is deliberate and accepted here: it round-trips a
 * customer this test itself creates (a random, never-reused
 * identification), never touching pre-existing sandbox data, and cleans
 * up after itself by deleting what it created.
 *
 * This resolves several ambiguities left open by the Fase 0 research
 * (docs/research/siigo-api-co/03-customers.md): PUT's full-replace
 * semantics, the DELETE endpoint's real response, and whether find()
 * 404s after a delete.
 */
final class CustomersStagingTest extends StagingTestCase
{
    public function test_creates_finds_updates_and_deletes_a_real_customer(): void
    {
        $customers = $this->customers();
        $identification = (string) random_int(1_000_000_000, 1_999_999_999);

        $created = $customers->create(new CustomerData(
            personType: PersonType::Person,
            idType: '13',
            identification: $identification,
            name: ['SiigoLaravelSdk', 'StagingTest'],
            fiscalResponsibilities: [new FiscalResponsibility('R-99-PN')],
            address: new Address('Cra. 18 #79A - 42', new City('Co', '19', '19001')),
        ));

        $this->assertNotSame('', $created->id);
        $this->assertSame($identification, $created->identification);

        $found = $customers->find($created->id);
        $this->assertSame($created->id, $found->id);

        $updated = $customers->update($created->id, new CustomerData(
            personType: PersonType::Person,
            idType: '13',
            identification: $identification,
            name: ['SiigoLaravelSdk', 'Updated'],
            fiscalResponsibilities: [new FiscalResponsibility('R-99-PN')],
            address: new Address('Cra. 18 #79A - 42', new City('Co', '19', '19001')),
        ));
        $this->assertSame(['SiigoLaravelSdk', 'Updated'], $updated->name);

        try {
            $customers->delete($created->id);
        } catch (RequestException $exception) {
            if ($exception->errorCode() === 'disabled_functionality') {
                $this->markTestIncomplete(
                    "DELETE /v1/customers/{$created->id} was rejected by Siigo as disabled_functionality ".
                    '(confirmed real behavior on this sandbox account, see docs/known-issues.md) — the test '.
                    'customer above was not cleaned up automatically.'
                );
            }

            throw $exception;
        }

        $this->expectException(NotFoundException::class);
        $customers->find($created->id);
    }

    private function customers(): Customers
    {
        $credentials = new AuthCredentials(self::env('SIIGO_USERNAME'), self::env('SIIGO_ACCESS_KEY'));

        $config = new ClientConfiguration(
            baseUrl: self::envOrDefault('SIIGO_BASE_URL', 'https://api.siigo.com'),
            partnerId: self::envOrDefault('SIIGO_PARTNER_ID', 'TREBOLDEV'),
            connectTimeout: 5.0,
            timeout: 30.0,
        );

        $app = new Application;
        $app['config'] = new ConfigRepository(['cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array']]]]);
        $tokens = new CacheTokenRepository(new CacheManager($app));

        $auth = new AuthenticationManager(new HttpFactory, $credentials, $config, $tokens);

        return new Customers(new Client(new HttpFactory, $auth, $config));
    }
}
