<?php

namespace SeatGeek\Sixpack\Response;

use SeatGeek\Sixpack\Response\Bags;

abstract class AbstractResponse
{
    protected $response;
    protected $meta;

    public function __construct($jsonResponse, $meta)
    {
        $this->response = new Bags\ResponseBag(json_decode($jsonResponse, true));
        $this->meta     = new Bags\MetaBag((array) $meta);
    }

    public function getSuccess()
    {
        return ($this->getStatus() === 200);
    }

    public function getStatus()
    {
        return $this->meta->getHttpCode();
    }

    public function getCalledUrl()
    {
        return $this->meta->getUrl();
    }

    public function getClientId()
    {
        return $this->response->getClientId();
    }
}
