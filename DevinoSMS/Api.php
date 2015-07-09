<?php

/**
 * Реализует основные методы Devino REST API
 *
 * @author Sintsov Roman <romiras_spb@mail.ru>
 */

namespace SmsClient\DevinoSMS;

use SmsClient\Client\DevinoClient as Client;

class Api extends ApiMethod {
    /**
     * @var string логин
     */
    private $login;
    /**
     * @var string пароль
     */
    private $password;
    /**
     * @var string ID сессии
     */
    private $sessionId;
    /**
     * @var Client
     */
    private $client;

    public function __construct($login, $password) {
        $this->login = $login;
        $this->password = $password;
        $this->client = new Client();
    }

    /**
     * Обертка для отправки запроса
     *
     * @param string $requestType тип запроса (POST или GET)
     * @param string $method метод
     * @param array $params массив параметров
     * @return string результат ответа сервиса
     * @throws Exception
     */
    private function request($requestType, $method, $params) {
        try {
            $response = $this->client->request($requestType, $method, $params);
        } catch (Exception $e) {
            throw new Exception($e->getErrorMessage($e->getCode()), $e->getCode());
        }

        return $response;
    }

    /**
     * Получить ID сессии
     *
     * @return string Идентификатор сессии
     * @throws Exception
     */
    public function getSessionID() {
        $response = $this->request('get', self::METHOD_SESSION_ID, [
            'login' => $this->login,
            'password' => $this->password
        ]);

        $this->sessionId = $response;
        return $this->sessionId;
    }

    /**
     * Запроса баланса
     *
     * @return double Баланс
     * @throws Exception
     */
    public function getBalance() {
        return $this->request('get', self::METHOD_GET_BALANCE, [
            'sessionId' => $this->sessionId
        ]);
    }

    /**
     * Отправка SMS-сообщения
     *
     * @param string  $sourceAddress отправитель. До 11 латинских символов или до 15 цифровых.
     * @param string|array  $destinationAddress дрес или массив адресов назначения. (Код страны+код сети+номер телефона, Пример: 79031234567
     * @param string  $data Текст сообщения
     * @param mixed   $sendDate дата отправки сообщения. Строка вида (YYYY-MM-DDTHH:MM:SS) или Timestamp. Необязательный параметр.
     * @param integer $validity Время жизни сообщения в минутах. Необязательный параметр
     *
     * @return array массив идентификаторов сообщений
     * @throws Exception
     */
    public function send($sourceAddress, $destinationAddress, $data, $sendDate = null, $validity = 0) {
        $method = (is_array($destinationAddress)) ? self::METHOD_SMS_SEND_BULK : self::METHOD_SMS_SEND;
        return $this->request(
            'post',
            $method,
            $this->getRequestParams($sourceAddress, $destinationAddress, $data, $sendDate, $validity)
        );
    }

    /**
     * Отправка SMS-сообщения с учетом часового пояса получателя.
     *
     * @param string  $sourceAddress отправитель. До 11 латинских символов или до 15 цифровых.
     * @param string  $destinationAddress адрес назначения. (Код страны+код сети+номер телефона, Пример: 79031234567
     * @param string  $data Текст сообщения
     * @param mixed   $sendDate дата отправки сообщения по местному времени получателя. Строка вида (YYYY-MM-DDTHH:MM:SS) или Timestamp
     * @param integer $validity Время жизни сообщения в минутах. Необязательный параметр
     *
     * @return array массив идентификаторов сообщений
     * @throws Exception
     * @throws \Exception
     */
    public function sendByTimeZone($sourceAddress, $destinationAddress, $data, $sendDate, $validity = 0) {
        if (!is_string($destinationAddress)) {
            throw new \Exception('Неверный параматер адресат назначения');
        }
        return $this->request(
            'post',
            self::METHOD_SMS_SEND_BY_TIME_ZONE,
            $this->getRequestParams($sourceAddress, $destinationAddress, $data, $sendDate, $validity)
        );
    }

    /**
     * Формирует набор данных параметров для отправки SMS уведолмения
     *
     * @param string  $sourceAddress отправитель. До 11 латинских символов или до 15 цифровых.
     * @param mixed   $destinationAddress адрес или массив адресов назначения. (Код страны+код сети+номер телефона, Пример: 79031234567
     * @param string  $data Текст сообщения
     * @param mixed   $sendDate дата отправки сообщения. Строка вида (YYYY-MM-DDTHH:MM:SS) или Timestamp
     * @param integer $validity Время жизни сообщения в минутах
     *
     * @return array Массив с параметрами
     */
    private function getRequestParams($sourceAddress, $destinationAddress, $data, $sendDate, $validity){
        $params = array(
            'sessionId' => $this->sessionId,
            'sourceAddress' => $sourceAddress,
            'data' => $data
        );

        if (is_array($destinationAddress)) {
            $params['DestinationAddresses'] = $destinationAddress;
        } else {
            $params['destinationAddress'] = $destinationAddress;
        }

        if (is_int($sendDate)) {
            $params['sendDate'] = date('Ymd\TH:i:s', $sendDate);
        } elseif (null !== $sendDate) {
            $params['sendDate'] = $sendDate;
        }

        if ($validity) {
            $params['validity'] = $validity;
        }

        return $params;
    }

}