<?php

namespace SmsClient\Client;

/**
 * Interface ClientInterface
 * @package smsClient\Client
 */
interface ClientInterface
{
    /**
     * @param string $requestType тип запроса
     * @param string $method
     * @param array $params
     *
     * @return string
     */
    public function request($requestType, $method, $params = []);
}
