<?php

namespace SeatGeek\Sixpack\Session;

use InvalidArgumentException;
use SeatGeek\Sixpack\Response;
use Symfony\Component\HttpFoundation\Request;

class Session
{
    // configuration
    protected $baseUrl      = 'http://localhost:5000';
    protected $cookiePrefix = 'sixpack';
    protected $timeout      = 500;
    protected $forcePrefix  = 'sixpack-force-';

    protected $clientId;
    protected $request;

    public function __construct($options = array(), Request $request = null)
    {
        isset($options['baseUrl']) && ($this->baseUrl = $options['baseUrl']);
        isset($options['cookiePrefix']) && ($this->cookiePrefix = $options['cookiePrefix']);
        isset($options['timeout']) && ($this->timeout = $options['timeout']);
        isset($options['forcePrefix']) && ($this->forcePrefix = $options['forcePrefix']);

        $this->setClientId(isset($options['clientId']) ? $options['clientId'] : null);

        $this->request = $request ?: Request::createFromGlobals();
    }

    protected function setClientId($clientId = null)
    {
        if ($clientId === null) {
            $clientId = $this->retrieveClientId();
        }
        if ($clientId === null) {
            $clientId = $this->generateClientId();
        }
        $this->clientId = $clientId;
        $this->storeClientId($clientId);
    }

    public function getClientid()
    {
        return $this->clientId;
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

    protected function generateClientId()
    {
        // This is just a first pass for testing. not actually unique.
        // TODO, NOT THIS
        $md5      = strtoupper(md5(uniqid(rand(), true)));
        $clientId = substr($md5, 0, 8) . '-' . substr($md5, 8, 4) . '-' . substr($md5, 12, 4) . '-' . substr($md5, 16, 4) . '-' . substr($md5, 20);

        return $clientId;
    }

    public function setTimeout($milliseconds)
    {
        $this->timeout = $milliseconds;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function isForced($experiment)
    {
        return $this->request->query->has($this->forcePrefix . $experiment);
    }

    protected function forceAlternative($experiment, $alternatives)
    {
        $forcedAlt = $this->request->query->get($this->forcePrefix . $experiment);

        if (!in_array($forcedAlt, $alternatives)) {
            throw new InvalidArgumentException('Invalid forced alternative');
        }

        $mockJson = json_encode(array(
            'status'      => 'ok',
            'alternative' => array('name' => $forcedAlt),
            'experiment'  => array('version' => 0, 'name' => $experiment),
            'client_id'   => null,
        ));
        $mockMeta = array('http_code' => 200, 'called_url' => '');

        return array($mockJson, $mockMeta);
    }

    public function status()
    {
        return $this->sendRequest('/_status');
    }

    public function convert($experiment, $kpi = null)
    {
        list($rawResp, $meta) = $this->sendRequest('convert', array(
            'experiment' => $experiment,
            'kpi'        => $kpi,
        ));

        return new Response\Conversion($rawResp, $meta);
    }

    public function participate($experiment, $alternatives, $trafficFraction = 1)
    {
        if (count($alternatives) < 2) {
            $message = sprintf('At least two alternatives are required; %d given', count($alternatives));
            throw new InvalidArgumentException($message);
        }

        foreach ($alternatives as $alt) {
            if (!preg_match('#^[a-z0-9][a-z0-9\-_ ]*$#i', $alt)) {
                throw new InvalidArgumentException(sprintf('Invalid Alternative Name: %s', $alt));
            }
        }

        if (floatval($trafficFraction) < 0 || floatval($trafficFraction) > 1) {
            throw new InvalidArgumentException('Invalid Traffic Fraction; only [0,1] are allowed');
        }

        if ($this->isForced($experiment)) {
            list($rawResp, $meta) = $this->forceAlternative($experiment, $alternatives);
        } else {
            list($rawResp, $meta) = $this->sendRequest('participate', array(
                'experiment'       => $experiment,
                'alternatives'     => $alternatives,
                'traffic_fraction' => $trafficFraction,
            ));
        }

        return new Response\Participation($rawResp, $meta, $alternatives[0]);
    }

    protected function getUserAgent()
    {
        return $this->request->server->get('HTTP_USER_AGENT');
    }

    protected function getIpAddress()
    {
        $invalidIps = array('127.0.0.1', '::1');

        return reset(array_diff($this->request->getClientIps(), $invalidIps)) ? : null;
    }

    protected function sendRequest($endpoint, $params = array())
    {
        if (isset($params['experiment']) && !preg_match('#^[a-z0-9][a-z0-9\-_ ]*$#i', $params['experiment'])) {
            throw new InvalidArgumentException(sprintf('Invalid Experiment Name: %s', $params['experiment']));
        }

        $params = array_merge(array(
            'client_id'  => $this->clientId,
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
        ), $params);

        $url = $this->baseUrl . '/' . $endpoint;

        $params = preg_replace('/%5B(?:[0-9]+)%5D=/', '=', http_build_query($params));
        $url .= '?' . $params;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->timeout);
        // Make sub 1 sec timeouts work, according to: http://ravidhavlesha.wordpress.com/2012/01/08/curl-timeout-problem-and-solution/
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);

        $return = curl_exec($ch);
        $meta   = curl_getinfo($ch);

        // handle failures in call dispatcher
        return array($return, $meta);
    }
}
