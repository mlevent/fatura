<?php

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Mlevent\Fatura\Client;
use Mlevent\Fatura\Gib;
use Psr\Http\Message\RequestInterface;

class MockGib extends Gib
{
    public array $requests = [];
    public $respond;

    protected function request(string $url, ?array $parameters = null, bool $post = true, array $options = []): Client
    {
        $options['handler'] = HandlerStack::create(function (RequestInterface $request, array $options) {
            parse_str($request->getMethod() === 'GET' ? $request->getUri()->getQuery() : (string) $request->getBody(), $form);
            $entry = ['request' => $request, 'form' => $form, 'payload' => json_decode($form['jp'] ?? '{}', true), 'options' => $options];
            $this->requests[] = $entry;
            $result = ($this->respond)($entry);
            return Create::promiseFor($result instanceof Response ? $result : new Response(200, [], json_encode($result, JSON_THROW_ON_ERROR)));
        });
        return parent::request($url, $parameters, $post, $options);
    }

    protected function waitForDraftVisibility(int $attempt): void {}

    public function commands(string $command): array
    {
        return array_values(array_filter($this->requests, fn ($entry) => ($entry['form']['cmd'] ?? '') === $command));
    }
}
