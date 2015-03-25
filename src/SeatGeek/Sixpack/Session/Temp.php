<?php

namespace SeatGeek\Sixpack\Session;

class Temp extends Session
{
    protected function retrieveClientId()
    {
        return;
    }

    protected function storeClientId($clientId)
    {
        return;
    }
}
