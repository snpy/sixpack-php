<?php

namespace SeatGeek\Sixpack\Response;

class Participation extends Response
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
        return $this->response->experiment;
    }

    public function getAlternative()
    {
        if (!$this->getSuccess()) {
            return $this->control;
        }

        return $this->response->alternative->name;
    }
}
