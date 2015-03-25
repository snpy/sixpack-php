<?php

namespace SeatGeek\Sixpack\Response\Bags;

class ResponseBag
{
    private $clientId;
    private $experiment;
    private $alternative;

    public function __construct(array $info)
    {
        $this->clientId = isset($info['client_id']) ? $info['client_id'] : null;
        $this->experiment = isset($info['experiment']) ? new ExperimentBag($info['experiment']) : null;
        $this->alternative = isset($info['alternative']) ? new AlternativeBag($info['alternative']) : null;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getExperiment()
    {
        return $this->experiment;
    }

    public function getAlternative()
    {
        return $this->alternative;
    }
}