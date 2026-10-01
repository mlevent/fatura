<?php

declare(strict_types=1);

namespace Mlevent\Fatura;

use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\BadResponseException;
use Mlevent\Fatura\Exceptions\SessionExpiredException;

class Client
{
    /**
     * @var array response
     */
    protected array $response = [];

    /**
     * @var array headers
     */
    protected static $headers = [
        'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
    ];
    
    /**
     * request
     *
     * @param string     $url
     * @param array|null $parameters
     * @param boolean    $post
     * @param array      $options Guzzle seçenekleri ve expect_json (varsayılan true)
     */
    public function __construct(string $url, ?array $parameters = null, bool $post = true, array $options = [])
    {
        try {
            $expectJson = $options['expect_json'] ?? true;
            unset($options['expect_json']);
            $options[$post ? 'form_params' : 'query'] = $parameters;
            $request = (new \GuzzleHttp\Client)->request($post ? 'POST' : 'GET', $url, array_replace([
                'headers' => self::$headers, 
                'timeout' => 30,
                'connect_timeout' => 10,
            ], $options));
            if (!$expectJson) {
                return;
            }
            if ($response = json_decode($request->getBody()->getContents(), true)) {
                if (is_array($response)) {
                    $this->response = $response;
                }
            }
            if (self::hasApiError($this->response)) {
                foreach ((array) ($this->response['messages'] ?? []) as $message) {
                    if (in_array($this->response['error'] ?? null, [1, '1'], true)
                        && is_array($message) && in_array($message['type'] ?? null, [4, '4'], true)
                        && is_string($message['text'] ?? null)
                        && str_contains($message['text'], 'Oturum zamanaşımına uğradı')) {
                        throw new SessionExpiredException('GİB oturumu sona erdi.', $parameters, $this->response, $request->getStatusCode());
                    }
                }
                throw new ApiException('İstek başarısız oldu.', $parameters, $this->response, $request->getStatusCode());
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new BadResponseException($e->getMessage(), $parameters, null, $e->getCode());
        }
    }

    protected static function hasApiError(array $response): bool
    {
        if (!$response || isset($response['error'])) {
            return true;
        }
        $data = $response['data'] ?? null;
        if (!is_array($data)) {
            return false;
        }
        if (!empty($data['error']) || !empty($data['hata'])) {
            return true;
        }
        if (array_is_list($data)) {
            foreach ($data as $row) {
                if (is_array($row) && (!empty($row['error']) || !empty($row['hata']))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * get
     *
     * @param  string|null $element
     * @return mixed
     */
    public function get(?string $element = null): mixed
    {
        if ($element === null) {
            return $this->response;
        }
        return $this->response[$element] ?? null;
    }

    /**
     * object
     *
     * @param  string|null $element
     * @return mixed
     */
    public function object(?string $element = null): mixed
    {
        $response = json_decode(json_encode($this->response, JSON_FORCE_OBJECT), false);
        
        if ($element === null) {
            return $response;
        }
        return $response->{$element} ?? null;
    }
}
