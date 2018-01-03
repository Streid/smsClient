<?php
/**
 * Клиент для взаимодействия с Devino REST API
 *
 * @author Sintsov Roman <romiras_spb@mail.ru>
 */

namespace SmsClient\Client;

use SmsClient\DevinoSMS\Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Class DevinoClient
 * @package smsClient\Client
 */
class DevinoClient implements ClientInterface
{
    /**
     * @var string базовый адрес для отправки запросов
     */
    private $baseUrl = 'https://integrationapi.net/rest/{method}';

    /**
     * @var array GuzzleConfig
     */
    private $config = [
        'timeout' => 20,
        'connect_timeout' => 20,
        'force_ip_resolve' => 'v4',
    ];

    /**
     *
     * @param string $requestType тип запроса (POST или GET)
     * @param string $method
     * @param array $params
     *
     * @return string
     *
     * @throws \Exception
     * @throws Exception
     */
    public function request($requestType, $method, $params = [])
    {
        if (!$this->isValidRequest($requestType)) {
            throw new \Exception('Недопустимый тип запроса - используйте GET или POST');
        }
        $client = new \GuzzleHttp\Client($this->getConfig());
        try {
            $response = $client->$requestType($this->getUrl($method), ['form_params' => $params]);
        } catch (ClientException $e) {
            $failResponse = $e->getResponse();
            $body = json_decode($failResponse->getBody());
            throw new Exception($failResponse->getReasonPhrase() . ($body->Desc) ? $body->Desc : '' . ' [ClientException]', $failResponse->getStatusCode());
        } catch (ServerException $e) {
            $failResponse = $e->getResponse();
            $body = json_decode($failResponse->getBody());
            throw new Exception($failResponse->getReasonPhrase() . ($body->Desc) ? $body->Desc : '' . ' [ServerException]', $failResponse->getStatusCode());
        }
        if ($response->getStatusCode() == 200) {
            return json_decode($response->getBody());
        } else {
            throw new Exception($response->getReasonPhrase(), $response->getStatusCode());
        }
    }

	/**
	 * @return array
	 */
	public function getConfig()
	{
		return $this->config;
	}
	/**
	 * @param array $config
	 */
	public function setConfig(array $config)
	{
		$this->config = $config;
	}

    /**
     * Валидация типа запроса
     * @param string $request тип запроса
     * @return bool true - если данный тип разрешен, false в противном случае
     */
    private function isValidRequest($request)
    {
        $allowRequest = array('post', 'get');
        return (in_array(strtolower($request), $allowRequest)) ? true : false;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    private function getUrl($method)
    {
        return strtr($this->baseUrl, ['{method}' => $method]);
    }
}
