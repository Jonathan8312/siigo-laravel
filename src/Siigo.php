<?php

declare(strict_types=1);

namespace Jonathan8312\Siigo;

use Jonathan8312\Siigo\Auth\AuthCredentials;
use Jonathan8312\Siigo\Auth\AuthenticationManager;
use Jonathan8312\Siigo\Http\Client;
use Jonathan8312\Siigo\Resources\Catalogs;
use Jonathan8312\Siigo\Resources\CreditNotes;
use Jonathan8312\Siigo\Resources\Customers;
use Jonathan8312\Siigo\Resources\Invoices;
use Jonathan8312\Siigo\Resources\PaymentReceipts;
use Jonathan8312\Siigo\Resources\Products;
use Jonathan8312\Siigo\Support\CatalogCache;
use SensitiveParameter;

/**
 * The SDK's main entry point.
 *
 * Bound as a singleton in Laravel's container, wrapping the default
 * company credentials read once from configuration at boot. This class
 * is immutable: {@see self::withCredentials()} never mutates the
 * instance it is called on, it always returns a new, detached Siigo
 * instance authenticated as a different company. That is what makes the
 * container singleton safe under Octane and other long-running workers
 * — no per-company state is ever written to shared state.
 *
 * Further resource accessors (customers(), products(), invoices(), ...)
 * are added in later phases, once the corresponding Siigo endpoints are
 * implemented — see docs/known-issues.md and docs/research/siigo-api-co
 * for what has already been investigated.
 */
final class Siigo
{
    public function __construct(
        private readonly Client $client,
        private readonly AuthenticationManager $auth,
        private readonly ?CatalogCache $catalogCache = null,
    ) {}

    /**
     * Siigo's read-only master/reference data (account groups, taxes,
     * price lists, warehouses, sellers, document types, payment types,
     * cost centers). Cached per-company (see {@see CatalogCache} and
     * `SIIGO_CATALOG_CACHE_TTL_SECONDS` in config/siigo.php) — this data
     * changes rarely, and re-fetching it on every invoice/payment
     * receipt would burn through Siigo's rate limit fast. This SDK does
     * not persist it anywhere beyond that cache: matching Siigo's ids to
     * your own application's master data is your application's
     * responsibility, not this package's (see README "What it does not
     * do").
     */
    public function catalogs(): Catalogs
    {
        return new Catalogs($this->client, $this->catalogCache, fn (): string => $this->auth->credentialsFingerprint());
    }

    /**
     * Customers/third parties: create, list, find, update, and delete.
     */
    public function customers(): Customers
    {
        return new Customers($this->client);
    }

    /**
     * Products/services: create, list, find, update, and delete.
     */
    public function products(): Products
    {
        return new Products($this->client);
    }

    /**
     * Sales invoices: create, list, find, update, delete, annul, and
     * the DIAN/PDF/XML/email endpoints. See docs/invoices.md.
     */
    public function invoices(): Invoices
    {
        return new Invoices($this->client);
    }

    /**
     * Credit notes: create, list, find, and PDF — no confirmed `PUT`,
     * `DELETE`, or annul endpoint. See docs/credit-notes.md.
     */
    public function creditNotes(): CreditNotes
    {
        return new CreditNotes($this->client);
    }

    /**
     * Payment receipts to suppliers (recibos de pago/egreso): create,
     * list, find, update, and delete. See docs/payment-receipts.md.
     */
    public function paymentReceipts(): PaymentReceipts
    {
        return new PaymentReceipts($this->client);
    }

    /**
     * Return a new, detached Siigo instance authenticated as a different
     * company, reusing this instance's transport configuration (base
     * URL, Partner-Id, timeouts, retry policy).
     *
     * The default instance (and any container singleton it came from) is
     * never modified by this call.
     */
    public function withCredentials(
        #[SensitiveParameter] string $username,
        #[SensitiveParameter] string $accessKey,
    ): self {
        $auth = $this->auth->withCredentials(new AuthCredentials($username, $accessKey));

        return new self($this->client->withAuthenticationManager($auth), $auth, $this->catalogCache);
    }

    /**
     * SDK-internal accessor to the underlying HTTP client, used by
     * resource classes and by tests. Not intended for direct use by
     * consumers of the package.
     */
    public function client(): Client
    {
        return $this->client;
    }
}
