<?php

namespace SeatGeek\Sixpack\Session;

class CookieSession extends AbstractSession
{
    protected $cookiePrefix;

    protected function getDefaults()
    {
        return parent::getDefaults() + array('cookiePrefix' => 'sixpack');
    }

    protected function retrieveClientId()
    {
        return $this->request->cookies->get($this->cookiePrefix . '_client_id');
    }

    protected function storeClientId($clientId)
    {
        $cookieName = $this->cookiePrefix . '_client_id';
        setcookie($cookieName, $clientId, time() + (60 * 60 * 24 * 30 * 100), '/');
    }
}
