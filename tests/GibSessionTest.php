<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\BadResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/MockGib.php';

final class GibSessionTest extends TestCase
{
    private const UUID = '11111111-1111-4111-8111-111111111111';

    private function gib(): MockGib
    {
        $gib = new MockGib();
        $gib->respond = function ($entry) {
            $path = $entry['request']->getUri()->getPath();
            if ($path === '/index.jsp') return new Response(200, ['Set-Cookie' => 'PORTAL=ready; Path=/; Secure'], '<html>portal</html>');
            if ($path === '/earsiv-services/assos-login') {
                $user = $entry['form']['userid'] ?? '';
                return new Response(200, ['Set-Cookie' => 'JSESSIONID='.$user.'; Path=/; Secure'], json_encode(['token' => 'token-'.$user]));
            }
            return ['data' => ['sonuc' => '1']];
        };
        return $gib;
    }

    public function testLoginBootstrapsPortalAndKeepsCookiesPerInstance(): void
    {
        $first = $this->gib()->login('first', 'test');
        $second = $this->gib()->login('second', 'test');
        self::assertCount(2, $first->requests);
        $boot = $first->requests[1];
        self::assertSame('GET', $boot['request']->getMethod());
        self::assertSame('token-first', $boot['form']['token']);
        self::assertStringContainsString('JSESSIONID=first', $boot['request']->getHeaderLine('Cookie'));
        self::assertTrue($first->completeSmsVerification('AB12CD', 'operation', [self::UUID]));
        self::assertTrue($second->completeSmsVerification('AB12CD', 'operation', [self::UUID]));
        foreach ([[$first, 'first'], [$second, 'second']] as [$gib, $user]) {
            $request = $gib->commands('0lhozfib5410mp')[0];
            self::assertStringContainsString('JSESSIONID='.$user, $request['request']->getHeaderLine('Cookie'));
            self::assertStringContainsString('PORTAL=ready', $request['request']->getHeaderLine('Cookie'));
            self::assertSame('https://earsivportal.efatura.gov.tr', $request['request']->getHeaderLine('Origin'));
            self::assertSame(300, $request['options']['timeout']);
            self::assertSame(['DATA' => [['belgeTuru' => 'FATURA', 'ettn' => self::UUID]], 'SIFRE' => 'AB12CD', 'OID' => 'operation', 'OPR' => 1], $request['payload']);
        }
    }

    public function testTokenOnlyClientBootstrapsOnceAndChangingTokenDropsCookies(): void
    {
        $gib = $this->gib()->setToken('saved-token');
        $gib->getPhoneNumber();
        $gib->getPhoneNumber();
        self::assertCount(3, $gib->requests);
        self::assertSame('/index.jsp', $gib->requests[0]['request']->getUri()->getPath());
        $gib->setToken('another-token')->getPhoneNumber();
        self::assertSame('', $gib->requests[3]['request']->getHeaderLine('Cookie'));
        self::assertSame('another-token', $gib->requests[3]['form']['token']);
    }

    public static function signingResponses(): array
    {
        return [[['sonuc' => '1'], true], [['sonuc' => 1], true], [['sonuc' => '0'], false], [[], false], [null, false]];
    }

    #[DataProvider('signingResponses')]
    public function testSigningResultMustReportSuccess(mixed $data, bool $expected): void
    {
        $gib = $this->gib();
        $gib->respond = fn () => ['data' => $data];
        self::assertSame($expected, $gib->completeSmsVerification('AB12CD', 'operation', [self::UUID]));
        self::assertSame($expected ? 1 : 0, $gib->rowCount());
        self::assertCount(1, $gib->commands('0lhozfib5410mp'));
    }

    public function testProviderSigningErrorIsNotReportedAsSuccess(): void
    {
        $gib = $this->gib();
        $gib->respond = fn () => ['data' => ['error' => 'Signing failed']];
        $this->expectException(ApiException::class);
        $gib->completeSmsVerification('AB12CD', 'operation', [self::UUID]);
    }

    public function testSigningTimeoutIsNeverReplayed(): void
    {
        $gib = $this->gib();
        $gib->respond = function ($entry) { throw new ConnectException('timeout', $entry['request']); };
        try {
            $gib->completeSmsVerification('AB12CD', 'operation', [self::UUID]);
            self::fail('Timeout accepted.');
        } catch (BadResponseException) {
            self::assertCount(1, $gib->requests);
            self::assertSame(0, $gib->rowCount());
        }
    }

    public function testExpiredReadLogsInOnceAndUsesTheNewToken(): void
    {
        $gib = $this->gib()->setCredentials('first', 'test')->setToken('old-token');
        $default = $gib->respond;
        $gib->respond = function ($entry) use ($default) {
            if (($entry['form']['cmd'] ?? '') === 'EARSIV_PORTAL_TASLAKLARI_GETIR') {
                return $entry['form']['token'] === 'old-token'
                    ? ['error' => '1', 'messages' => [['type' => '4', 'text' => 'Oturum zamanaşımına uğradı']]]
                    : ['data' => []];
            }
            return $default($entry);
        };
        self::assertSame([], $gib->getAll('01/10/2026', '01/10/2026'));
        self::assertSame(['old-token', 'token-first'], array_column(array_column($gib->commands('EARSIV_PORTAL_TASLAKLARI_GETIR'), 'form'), 'token'));
        self::assertSame(1, count(array_filter($gib->requests, fn ($entry) => ($entry['form']['assoscmd'] ?? '') === 'anologin')));
    }

    public function testRepeatedSessionExpiryDoesNotLoopOrTryLegacyDetail(): void
    {
        $gib = $this->gib()->setCredentials('first', 'test')->setToken('old-token');
        $default = $gib->respond;
        $gib->respond = fn ($entry) => isset($entry['form']['cmd'])
            ? ['error' => '1', 'messages' => [['type' => '4', 'text' => 'Oturum zamanaşımına uğradı']]] : $default($entry);
        $this->expectException(Mlevent\Fatura\Exceptions\SessionExpiredException::class);
        try { $gib->getDocument(self::UUID); }
        finally {
            self::assertCount(2, $gib->commands('EARSIV_PORTAL_FATURA_GETIR'));
            foreach ($gib->commands('EARSIV_PORTAL_FATURA_GETIR') as $entry) self::assertSame('RG_BASITFATURA', $entry['form']['pageName']);
        }
    }

    public function testExpiredSigningRequestIsNotRetried(): void
    {
        $gib = $this->gib()->setCredentials('first', 'test');
        $gib->respond = fn () => ['error' => '1', 'messages' => [['type' => '4', 'text' => 'Oturum zamanaşımına uğradı']]];
        $this->expectException(Mlevent\Fatura\Exceptions\SessionExpiredException::class);
        try { $gib->completeSmsVerification('AB12CD', 'operation', [self::UUID]); }
        finally { self::assertCount(1, $gib->requests); }
    }

    public function testFailedLogoutStillClearsLocalCredentialsAndToken(): void
    {
        $gib = $this->gib()->login('first', 'test');
        $gib->respond = fn () => ['error' => '1'];
        $this->expectException(ApiException::class);
        try { $gib->logout(); }
        finally {
            self::assertSame('', $gib->getToken());
            self::assertSame(['username' => null, 'password' => null], $gib->getCredentials());
        }
    }
}
