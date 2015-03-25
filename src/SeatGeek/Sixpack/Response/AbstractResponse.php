<?php

namespace SeatGeek\Sixpack\Response;

abstract class AbstractResponse
{
    protected $response;
    /** @var MetaBag */
    protected $meta;

    public function __construct($jsonResponse, $meta)
    {
        $this->response = json_decode($jsonResponse);
        $this->meta     = new MetaBag((array) $meta);
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
        return $this->response->client_id;
    }
}
