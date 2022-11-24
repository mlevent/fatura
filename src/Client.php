<?php

declare(strict_types=1);

namespace Mlevent\Fatura;

use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\BadResponseException;

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
        'content-type' => 'application/x-www-form-urlencoded',
    ];
    
    /**
     * request
     *
     * @param string     $url
     * @param array|null $parameters
     * @param boolean    $post
     */
    public function __construct(string $url, ?array $parameters = null, bool $post = true)
    {
        $client = new \GuzzleHttp\Client(self::$headers);
        try {
            $request = $client->request($post ? 'POST' : 'GET', $url, ['form_params' => $parameters]);
            if ($response = json_decode($request->getBody()->getContents(), true)) {
                if (is_array($response)) {
                    $this->response = $response;
                }
            }
            if (!$this->response || isset($this->response['error']) || !empty($this->response['data']['hata'])) {
                throw new ApiException('İstek başarısız oldu.', $parameters, $this->response, $request->getStatusCode());
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new BadResponseException($e->getMessage(), $parameters, null, $e->getCode());
        }
    }

    /**
     * get
     *
     * @param  string|null  $element
     * @return string|array
     */
    public function get(?string $element = null): string|array
    {
        return is_null($element) 
            ? $this->response
            : $this->response[$element];
    }

    /**
     * object
     *
     * @param  string|null   $element
     * @return string|object
     */
    public function object(?string $element = null): string|object
    {
        $response = json_decode(json_encode($this->response, JSON_FORCE_OBJECT), false);
        
        return is_null($element) 
            ? $response
            : $response->$element;
    }
}