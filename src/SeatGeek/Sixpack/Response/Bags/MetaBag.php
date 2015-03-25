<?php

namespace SeatGeek\Sixpack\Response\Bags;

class MetaBag
{
    private $httpCode;
    private $url;

    public function __construct(array $info)
    {
        $this->httpCode = isset($info['http_code']) ? (int) $info['http_code'] : null;
        $this->url      = isset($info['url']) ? $info['url'] : null;
    }

    public function getHttpCode()
    {
        return $this->httpCode;
    }

    public function getUrl()
    {
        return $this->url;
    }
}