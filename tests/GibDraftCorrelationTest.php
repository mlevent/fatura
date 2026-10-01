<?php

use GuzzleHttp\Exception\ConnectException;
use Mlevent\Fatura\Enums\DocumentType;
use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\DraftCorrelationException;
use Mlevent\Fatura\Exceptions\DraftPreflightException;
use Mlevent\Fatura\Models\InvoiceModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/MockGib.php';

final class GibDraftCorrelationTest extends TestCase
{
    private const OLD = '11111111-1111-4111-8111-111111111111';
    private const CREATED = '22222222-2222-4222-8222-222222222222';
    private const OTHER = '33333333-3333-4333-8333-333333333333';

    private function payload(): array
    {
        return ['faturaTarihi' => '01/10/2026', 'saat' => '12:30:01', 'vknTckn' => '11111111111',
            'odenecekTutar' => 120.0, 'paraBirimi' => 'TRY', 'siparisNumarasi' => 'ORDER-1',
            'malHizmetTable' => [['malHizmet' => 'Kalem', 'miktar' => 2, 'birimFiyat' => 50, 'kdvOrani' => 20]]];
    }

    private function gib(array $lists, array $details, mixed $created = 'Fatura başarıyla oluşturuldu.'): MockGib
    {
        $gib = new MockGib();
        $gib->respond = function ($entry) use (&$lists, $details, $created) {
            $command = $entry['form']['cmd'] ?? '';
            if ($command === 'EARSIV_PORTAL_TASLAKLARI_GETIR') {
                $ids = count($lists) > 1 ? array_shift($lists) : ($lists[0] ?? []);
                if ($ids instanceof Throwable) throw $ids;
                return ['data' => array_map(fn ($id) => ['ettn' => $id, 'aliciVknTckn' => '11111111111', 'belgeTuru' => 'FATURA'], $ids)];
            }
            if ($command === 'EARSIV_PORTAL_FATURA_OLUSTUR') {
                if ($created instanceof Throwable) throw $created;
                return ['data' => $created];
            }
            if ($command === 'EARSIV_PORTAL_FATURA_GETIR') {
                $id = $entry['payload']['ettn'];
                $detail = $details[$id] ?? [];
                if ($detail instanceof Throwable) throw $detail;
                return ['data' => ['faturaUuid' => $id] + $detail];
            }
            throw new RuntimeException('Unexpected command: '.$command);
        };
        return $gib;
    }

    public function testExistingInvoiceForSameRecipientIsNeverReused(): void
    {
        $gib = $this->gib([[self::OLD], [self::OLD], [self::OLD, self::CREATED]], [self::OLD => $this->payload(), self::CREATED => $this->payload()]);
        self::assertTrue($gib->createDraft($this->payload()));
        self::assertSame(self::CREATED, $gib->lastId());
        self::assertCount(1, $gib->commands('EARSIV_PORTAL_FATURA_OLUSTUR'));
        self::assertGreaterThanOrEqual(4, count($gib->commands('EARSIV_PORTAL_TASLAKLARI_GETIR')));
        foreach ($gib->commands('EARSIV_PORTAL_TASLAKLARI_GETIR') as $request) {
            self::assertSame('01/10/2026', $request['payload']['baslangic']);
            self::assertSame('01/10/2026', $request['payload']['bitis']);
        }
    }

    public function testDifferentInvoiceContentsDoNotWinByBeingLast(): void
    {
        $other = $this->payload();
        $other['malHizmetTable'][0]['malHizmet'] = 'Defter';
        $gib = $this->gib([[], [self::CREATED, self::OTHER]], [self::CREATED => $this->payload(), self::OTHER => $other]);
        self::assertTrue($gib->createDraft($this->payload()));
        self::assertSame(self::CREATED, $gib->lastId());
    }

    public function testMultipleMatchingNewInvoicesAreRejected(): void
    {
        $gib = $this->gib([[], [self::CREATED, self::OTHER]], [self::CREATED => $this->payload(), self::OTHER => $this->payload()]);
        $this->expectException(DraftCorrelationException::class);
        try { $gib->createDraft($this->payload()); }
        finally { self::assertSame('', $gib->lastId()); self::assertCount(1, $gib->commands('EARSIV_PORTAL_FATURA_OLUSTUR')); }
    }

    public function testMissingCandidateDetailsDoNotPermitAnotherCandidateToWin(): void
    {
        $gib = $this->gib([[], [self::CREATED, self::OTHER]], [self::CREATED => $this->payload(), self::OTHER => new ApiException('Detail unavailable')]);
        $this->expectException(DraftCorrelationException::class);
        $gib->createDraft($this->payload());
    }

    public function testFailedPreflightDoesNotCreateAnInvoice(): void
    {
        $gib = $this->gib([new ApiException('List unavailable')], []);
        $this->expectException(DraftPreflightException::class);
        try { $gib->createDraft($this->payload()); }
        finally { self::assertCount(0, $gib->commands('EARSIV_PORTAL_FATURA_OLUSTUR')); }
    }

    public function testExistingQueryFiltersCannotHidePreflightDocuments(): void
    {
        $gib = $this->gib([[self::OLD], [self::OLD]], [self::OLD => $this->payload()]);
        $gib->onlySigned()->setLimit(1)->selectColumn('ettn');
        $this->expectException(DraftCorrelationException::class);
        $gib->createDraft($this->payload());
    }

    public function testModelAndLastIdAreClearedWhenNewUuidCannotBeResolved(): void
    {
        $model = new InvoiceModel('11111111111', uuid: self::OLD, tarih: '01/10/2026');
        $gib = $this->gib([[]], []);
        $this->expectException(DraftCorrelationException::class);
        try { $gib->createDraft($model); }
        finally { self::assertSame('', $model->getUuid()); self::assertSame('', $gib->lastId()); }
    }

    public function testReturnedUuidIsUsedOnlyAfterMatchingItsDetail(): void
    {
        $gib = $this->gib([[]], [self::CREATED => $this->payload()], ['ettn' => self::CREATED]);
        self::assertTrue($gib->createDraft($this->payload()));
        self::assertSame(self::CREATED, $gib->lastId());
    }

    public static function unsafeResponses(): array
    {
        return [[['ettn' => self::OLD]], [['ettn' => self::CREATED, 'success' => false]], [['unexpected' => 'response']], ['başarısız']];
    }

    #[DataProvider('unsafeResponses')]
    public function testConflictingOrUnrelatedCreationResponsesAreRejected(mixed $response): void
    {
        $gib = $this->gib([[self::OLD]], [self::OLD => $this->payload(), self::CREATED => $this->payload()], $response);
        $this->expectException(DraftCorrelationException::class);
        $gib->createDraft($this->payload());
    }

    public function testUpdatePreservesItsUuidAndDoesNotSearchForAnotherInvoice(): void
    {
        $gib = $this->gib([], []);
        $payload = $this->payload() + ['belgeNumarasi' => 'GIB2026000000001', 'faturaUuid' => self::OLD];
        self::assertTrue($gib->createDraft($payload));
        self::assertSame(self::OLD, $gib->lastId());
        self::assertCount(1, $gib->requests);
        self::assertSame(self::OLD, $gib->requests[0]['payload']['faturaUuid']);
    }

    public function testCreationTransportFailureIsNeverRetried(): void
    {
        $gib = $this->gib([[]], [], new ConnectException('timeout', new GuzzleHttp\Psr7\Request('POST', 'https://example.test')));
        $this->expectException(DraftCorrelationException::class);
        try { $gib->createDraft($this->payload()); }
        finally { self::assertCount(1, $gib->commands('EARSIV_PORTAL_FATURA_OLUSTUR')); }
    }

    public function testConcurrentCreationOnTheSameHostStopsBeforeSending(): void
    {
        $first = $this->gib([[], [self::CREATED]], [self::CREATED => $this->payload()]);
        $second = $this->gib([[]], []);
        $default = $first->respond;
        $first->respond = function ($entry) use ($default, $second) {
            if (($entry['form']['cmd'] ?? '') === 'EARSIV_PORTAL_FATURA_OLUSTUR') {
                try {
                    $second->createDraft($this->payload());
                    self::fail('Concurrent creation accepted.');
                } catch (DraftPreflightException) {
                    self::assertSame([], $second->requests);
                }
            }
            return $default($entry);
        };
        self::assertTrue($first->createDraft($this->payload()));
    }

    public function testReturnedDetailForADifferentUuidBlocksListCorrelation(): void
    {
        $gib = $this->gib([[], [self::CREATED, self::OTHER]], [self::CREATED => $this->payload()]);
        $default = $gib->respond;
        $gib->respond = fn ($entry) => ($entry['payload']['ettn'] ?? '') === self::OTHER
            ? ['data' => ['faturaUuid' => self::OLD] + $this->payload()] : $default($entry);
        $this->expectException(DraftCorrelationException::class);
        $gib->createDraft($this->payload());
    }

    public function testMissingCorrelationFieldsFailBeforeCreating(): void
    {
        $gib = $this->gib([[]], []);
        $this->expectException(DraftPreflightException::class);
        try { $gib->createDraft(['faturaTarihi' => '01/10/2026']); }
        finally { self::assertSame([], $gib->requests); }
    }

    public static function receiptTypes(): array
    {
        return [[DocumentType::ProducerReceipt, 'odenecekTutar', 'uuid'], [DocumentType::SelfEmployedReceipt, 'netAlinanToplam', 'ettn']];
    }

    #[DataProvider('receiptTypes')]
    public function testReceiptCreationUsesItsOwnDateAndTotal(DocumentType $type, string $total, string $uuidKey): void
    {
        $payload = ['tarih' => '01/10/2026', 'vknTckn' => '11111111111', $total => 120];
        $gib = new MockGib($type);
        $gib->respond = fn ($entry) => ['data' => match (true) {
            $entry['form']['cmd'] === 'EARSIV_PORTAL_TASLAKLARI_GETIR' => [],
            str_ends_with($entry['form']['cmd'], 'OLUSTUR') => [$uuidKey => self::CREATED],
            default => [$uuidKey => self::CREATED] + $payload,
        }];
        self::assertTrue($gib->createDraft($payload));
        self::assertSame(self::CREATED, $gib->lastId());
    }

    public function testLocalizedNumbersDoNotPreventAnExactContentMatch(): void
    {
        $detail = $this->payload();
        $detail['odenecekTutar'] = '120,00';
        $detail['malHizmetTable'][0]['birimFiyat'] = '50,0000';
        $gib = $this->gib([[], [self::CREATED]], [self::CREATED => $detail]);
        self::assertTrue($gib->createDraft($this->payload()));
    }
}
