<?php

namespace SeatGeek\Sixpack\Client;

use Buzz\Client\AbstractCurl;
use Buzz\Exception\RequestException;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Buzz\Exception\LogicException;

class Curl extends AbstractCurl
{
    private $lastCurl;

    /**
     * NO CHANGES; copy of:
     *
     * @see \Buzz\Client\Curl::send()
     */
    public function send(RequestInterface $request, MessageInterface $response, array $options = array())
    {
        if (is_resource($this->lastCurl)) {
            curl_close($this->lastCurl);
        }

        $this->lastCurl = static::createCurlHandle();
        $this->prepare($this->lastCurl, $request, $options);

        $data = curl_exec($this->lastCurl);

        if (false === $data) {
            $errorMsg = curl_error($this->lastCurl);
            $errorNo  = curl_errno($this->lastCurl);

            $e = new RequestException($errorMsg, $errorNo);
            $e->setRequest($request);

            throw $e;
        }

        static::populateResponse($this->lastCurl, $data, $response);
    }

    /**
     * THIS IS THE CAUSE WHY WE'RE OVERWRITING WHOLE \Buzz\Client\Curl CLASS
     *
     * @link https://github.com/kriswallsmith/Buzz/pull/188
     *
     * @see \Buzz\Client\Curl::send()
     */
    public function getInfo($opt = 0)
    {
        if (!is_resource($this->lastCurl)) {
            throw new LogicException('There is no cURL resource');
        }

        return $opt ? curl_getinfo($this->lastCurl, $opt) : curl_getinfo($this->lastCurl);
    }

    /**
     * NO CHANGES; copy of:
     *
     * @see \Buzz\Client\Curl::__destruct()
     */
    public function __destruct()
    {
        if (is_resource($this->lastCurl)) {
            curl_close($this->lastCurl);
        }
    }
}