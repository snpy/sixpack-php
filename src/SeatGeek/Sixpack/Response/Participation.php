<?php

namespace SeatGeek\Sixpack\Response;

class Participation extends AbstractResponse
{
    private $control;

    public function __construct($jsonResponse, $meta, $control = null)
    {
        $this->control = $control;

        parent::__construct($jsonResponse, $meta);
    }

    public function getExperiment()
    {
        return $this->isSuccess() ? $this->response->getExperiment() : null;
    }

    public function getAlternative()
    {
        return $this->isSuccess() ? $this->response->getAlternative() : null;
    }

    public function getAlternativeName()
    {
        return ($alt = $this->getAlternative()) ? $alt->getName() : $this->control;
    }
}
