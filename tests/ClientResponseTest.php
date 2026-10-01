<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mlevent\Fatura\Client;
use Mlevent\Fatura\Exceptions\ApiException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientResponseTest extends TestCase
{
    public static function rejectedResponses(): array
    {
        return [
            'root error' => [['error' => 'Rejected']],
            'data hata' => [['data' => ['hata' => 'Rejected']]],
            'data error' => [['data' => ['error' => 'Rejected']]],
            'list error' => [['data' => [['error' => 'Seçilen tarih aralığı 7 günden fazla olamaz!']]]],
            'list hata' => [['data' => [['hata' => 'Rejected']]]],
            'error after a document' => [['data' => [['ettn' => 'document'], ['error' => 'Rejected']]]],
        ];
    }

    #[DataProvider('rejectedResponses')]
    public function testProviderErrorsAreExceptionsRatherThanDocuments(array $body): void
    {
        $parameters = ['token' => 'private-session-token'];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));

        try {
            new Client('https://example.test/dispatch', $parameters, true, ['handler' => $handler]);
            self::fail('A provider rejection must not be returned as a document.');
        } catch (ApiException $e) {
            self::assertSame($body, $e->getResponse());
            self::assertSame($parameters, $e->getRequest());
            self::assertSame(200, $e->getCode());
            self::assertSame('İstek başarısız oldu.', $e->getMessage());
        }
    }

    public static function successfulResponses(): array
    {
        return [
            'empty list' => [['data' => []]],
            'document list' => [['data' => [['ettn' => 'document']]]],
            'document detail' => [['data' => ['ettn' => 'document', 'hata' => '']]],
            'false error flag' => [['data' => ['error' => false]]],
            'false row error flag' => [['data' => [['ettn' => 'document', 'error' => false]]]],
            'scalar success' => [['data' => 'Fatura başarıyla oluşturuldu.']],
            'signing success' => [['data' => ['sonuc' => '1']]],
            'login success' => [['token' => 'test-token']],
        ];
    }

    #[DataProvider('successfulResponses')]
    public function testSuccessfulResponseShapesRemainSupported(array $body): void
    {
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], json_encode($body, JSON_THROW_ON_ERROR)),
        ]));
        $client = new Client('https://example.test/dispatch', [], true, ['handler' => $handler]);

        self::assertSame($body, $client->get());
    }
}
