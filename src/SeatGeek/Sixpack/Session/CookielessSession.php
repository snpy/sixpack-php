<?php

namespace SeatGeek\Sixpack\Session;

class CookielessSession extends AbstractSession
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
