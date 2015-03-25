<?php

namespace SeatGeek\Sixpack\Response;

use Symfony\Component\HttpFoundation\ParameterBag;

class MetaBag extends ParameterBag
{
    public function __construct(array $meta)
    {
        foreach ($meta as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function getHttpCode()
    {
        return $this->get('http_code');
    }

    public function getUrl()
    {
        return $this->get('url');
    }
}