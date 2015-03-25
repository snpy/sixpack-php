<?php

namespace SeatGeek\Sixpack\Response;

class Participation extends AbstractResponse
{
    private $control;

    public function __construct($jsonResponse, $meta, $control = null)
    {
        if ($control !== null) {
            $this->control = $control;
        }

        parent::__construct($jsonResponse, $meta);
    }

    public function getExperiment()
    {
        return $this->response->getExperiment();
    }

    public function getAlternative()
    {
        if (!$this->getSuccess()) {
            return $this->control;
        }

        return $this->response->getAlternative();
    }
}
