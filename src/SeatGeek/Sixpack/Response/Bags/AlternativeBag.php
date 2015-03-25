<?php

namespace SeatGeek\Sixpack\Response\Bags;

class AlternativeBag
{
    private $name;

    public function __construct(array $info)
    {
        $this->name = isset($info['name']) ? $info['name'] : null;
    }

    public function getName()
    {
        return $this->name;
    }
}