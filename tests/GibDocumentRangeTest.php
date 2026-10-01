<?php

use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\InvalidFormatException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/MockGib.php';

final class GibDocumentRangeTest extends TestCase
{
    private const FIRST = '11111111-1111-4111-8111-111111111111';
    private const SECOND = '22222222-2222-4222-8222-222222222222';
    private const THIRD = '33333333-3333-4333-8333-333333333333';
    private const FOURTH = '44444444-4444-4444-8444-444444444444';
    public static function listMethods(): array
    {
        return [['getAll'], ['getAllIssuedToMe'], ['getRequests']];
    }

    private function gib(): MockGib
    {
        $gib = new MockGib();
        $gib->respond = function ($entry) {
            $start = DateTimeImmutable::createFromFormat('!d/m/Y', $entry['payload']['baslangic']);
            $end = DateTimeImmutable::createFromFormat('!d/m/Y', $entry['payload']['bitis']);
            return $start->diff($end)->days > 7
                ? ['data' => [['error' => 'Seçilen tarih aralığı 7 günden fazla olamaz!']]]
                : ['data' => []];
        };
        return $gib;
    }

    #[DataProvider('listMethods')]
    public function testLongRangesCoverEveryDayWithoutOverlap(string $method): void
    {
        $gib = $this->gib();
        self::assertSame([], $gib->$method('01/09/2026', '01/10/2026'));
        self::assertSame([
            ['01/09/2026', '08/09/2026'], ['09/09/2026', '16/09/2026'],
            ['17/09/2026', '24/09/2026'], ['25/09/2026', '01/10/2026'],
        ], array_map(fn ($r) => [$r['payload']['baslangic'], $r['payload']['bitis']], $gib->requests));
    }

    public static function boundaries(): array
    {
        return [
            ['01/10/2026', '01/10/2026', [['01/10/2026', '01/10/2026']]],
            ['24/09/2026', '01/10/2026', [['24/09/2026', '01/10/2026']]],
            ['23/09/2026', '01/10/2026', [['23/09/2026', '30/09/2026'], ['01/10/2026', '01/10/2026']]],
            ['25/02/2024', '05/03/2024', [['25/02/2024', '03/03/2024'], ['04/03/2024', '05/03/2024']]],
            ['28/12/2025', '06/01/2026', [['28/12/2025', '04/01/2026'], ['05/01/2026', '06/01/2026']]],
        ];
    }

    #[DataProvider('boundaries')]
    public function testRangeBoundaries(string $start, string $end, array $expected): void
    {
        $gib = $this->gib();
        self::assertSame([], $gib->getAll($start, $end));
        self::assertSame($expected, array_map(fn ($r) => [$r['payload']['baslangic'], $r['payload']['bitis']], $gib->requests));
    }

    public function testFilteringAndPaginationRunOnceAfterAllWindows(): void
    {
        $gib = $this->gib();
        $responses = [
            [['ettn' => self::FIRST, 'onayDurumu' => 'Onaylandı'], ['ettn' => self::SECOND, 'onayDurumu' => 'Onaylanmadı']],
            [['ettn' => self::THIRD, 'onayDurumu' => 'Onaylandı'], ['ettn' => self::FOURTH, 'onayDurumu' => 'Onaylandı']],
        ];
        $gib->respond = function () use (&$responses) { return ['data' => array_shift($responses)]; };
        self::assertSame([self::THIRD], $gib->onlySigned()->sortDesc()->setLimit(1, 1)->selectColumn('ettn')->getAll('01/09/2026', '16/09/2026'));
        self::assertSame(3, $gib->rowCount());
    }

    public function testLaterWindowFailureDoesNotReturnPartialResults(): void
    {
        $gib = $this->gib();
        $gib->respond = fn ($entry) => $entry['payload']['baslangic'] === '01/09/2026'
            ? ['data' => [['ettn' => self::FIRST]]] : ['data' => [['error' => 'Rejected']]];
        $this->expectException(ApiException::class);
        $gib->getAll('01/09/2026', '16/09/2026');
    }

    public static function invalidRanges(): array
    {
        return [['02/10/2026', '01/10/2026'], ['31/02/2026', '01/03/2026'], ['x01/10/2026', '01/10/2026'], ['2026-10-01', '01/10/2026']];
    }

    #[DataProvider('invalidRanges')]
    public function testInvalidDatesAreRejectedBeforeRequest(string $start, string $end): void
    {
        $gib = $this->gib();
        try {
            $gib->getAll($start, $end);
            self::fail('Invalid range accepted.');
        } catch (InvalidFormatException) {
            self::assertSame([], $gib->requests);
        }
    }

    public function testHourlySearchAndDocumentTypeArePreserved(): void
    {
        $gib = $this->gib();
        $gib->getAllIssuedToMe('01/09/2026', '16/09/2026', '24');
        self::assertCount(2, $gib->requests);
        foreach ($gib->requests as $entry) self::assertSame('24', $entry['payload']['hourlySearchInterval']);
        $gib->requests = [];
        $gib->testMode()->getAll('01/09/2026', '16/09/2026');
        foreach ($gib->requests as $entry) self::assertSame('Buyuk', $entry['payload']['hangiTip']);
    }

    public function testLastDocumentSearchesTheWholeYearBeforeApplyingItsLimit(): void
    {
        $gib = $this->gib();
        $old = '11111111-1111-4111-8111-111111111111';
        $new = '22222222-2222-4222-8222-222222222222';
        $gib->respond = fn ($entry) => ['data' => ($entry['form']['cmd'] ?? '') === 'EARSIV_PORTAL_FATURA_GETIR'
            ? ['faturaUuid' => $entry['payload']['ettn']]
            : [['ettn' => $entry['payload']['bitis'] === curdate('d/m/Y') ? $new : $old, 'belgeTuru' => 'FATURA']]];
        self::assertSame(['faturaUuid' => $new], $gib->getLastDocument());
        self::assertGreaterThan(40, count($gib->commands('EARSIV_PORTAL_TASLAKLARI_GETIR')));
        self::assertCount(1, $gib->commands('EARSIV_PORTAL_FATURA_GETIR'));
    }

    public static function malformedLists(): array
    {
        return [[null], ['unexpected'], [['documents' => []]], [['scalar row']]];
    }

    #[DataProvider('malformedLists')]
    public function testMalformedListsCannotLookLikeEmptySuccessfulQueries(mixed $data): void
    {
        $gib = $this->gib();
        $gib->respond = fn () => ['data' => $data];
        $this->expectException(ApiException::class);
        $gib->getAll('01/10/2026', '01/10/2026');
    }

    public function testDuplicateIdsWithinOneResponseRemainAnError(): void
    {
        $gib = $this->gib();
        $gib->respond = fn () => ['data' => [['ettn' => self::FIRST], ['ettn' => self::FIRST]]];
        $this->expectException(ApiException::class);
        $gib->getAll('01/10/2026', '01/10/2026');
    }

    public function testSameDocumentInDifferentWindowsIsMergedBeforeCounting(): void
    {
        $gib = $this->gib();
        $gib->respond = fn ($entry) => ['data' => [['ettn' => self::FIRST, 'onayDurumu' => $entry['payload']['bitis']]]];
        self::assertSame([['ettn' => self::FIRST, 'onayDurumu' => '16/09/2026']], $gib->getAll('01/09/2026', '16/09/2026'));
        self::assertSame(1, $gib->rowCount());
    }
}
