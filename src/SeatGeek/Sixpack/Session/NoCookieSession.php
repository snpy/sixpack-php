<?php

namespace SeatGeek\Sixpack\Session;

class NoCookieSession extends AbstractSession
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
