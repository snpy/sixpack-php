<?php

namespace SeatGeek\Sixpack\Response\Bags;

class ExperimentBag
{
    private $name;
    private $version;

    public function __construct(array $info)
    {
        $this->name    = isset($info['name']) ? $info['name'] : null;
        $this->version = isset($info['version']) ? $info['version'] : null;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getVersion()
    {
        return $this->version;
    }
}