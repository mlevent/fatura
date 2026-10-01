<?php

use Mlevent\Fatura\Enums\DocumentType;
use Mlevent\Fatura\Exceptions\ApiException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/MockGib.php';

final class GibDocumentDetailTest extends TestCase
{
    private const UUID = '11111111-1111-4111-8111-111111111111';

    public function testInvoiceUsesTheEditScreenRoute(): void
    {
        $gib = new MockGib();
        $gib->respond = fn () => ['data' => ['faturaUuid' => self::UUID]];
        self::assertSame(['faturaUuid' => self::UUID], $gib->getDocument(self::UUID));
        self::assertSame('RG_BASITFATURA', $gib->requests[0]['form']['pageName']);
    }

    public function testLegacyRouteIsTriedOnlyForReadFailure(): void
    {
        $gib = new MockGib();
        $gib->respond = fn ($entry) => $entry['form']['pageName'] === 'RG_BASITFATURA'
            ? ['data' => ['hata' => 'Düzenlenmek üzere fatura getirilemedi.']]
            : ['data' => ['faturaUuid' => self::UUID]];
        self::assertSame(['faturaUuid' => self::UUID], $gib->getDocument(self::UUID));
        self::assertSame(['RG_BASITFATURA', 'RG_TASLAKLAR'], array_column(array_column($gib->requests, 'form'), 'pageName'));
    }

    public function testEmptyDetailIsNotAccepted(): void
    {
        $gib = new MockGib();
        $gib->respond = fn () => ['data' => []];
        $this->expectException(ApiException::class);
        $gib->getDocument(self::UUID);
    }

    public function testReceiptRouteIsUnchanged(): void
    {
        $gib = new MockGib(DocumentType::ProducerReceipt);
        $gib->respond = fn () => ['data' => ['uuid' => self::UUID]];
        $gib->getDocument(self::UUID);
        self::assertSame('RG_MUSTAHSIL', $gib->requests[0]['form']['pageName']);
        self::assertCount(1, $gib->requests);
    }
}
