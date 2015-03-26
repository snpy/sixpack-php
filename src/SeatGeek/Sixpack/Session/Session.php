<?php

namespace SeatGeek\Sixpack\Session;

use Buzz\Browser;
use InvalidArgumentException;
use SeatGeek\Sixpack\Client\Curl;
use SeatGeek\Sixpack\Response;
use Symfony\Component\HttpFoundation\Request;

class Session
{
    // configuration
    protected $baseUrl;
    protected $cookiePrefix;
    protected $timeout;
    protected $forcePrefix;

    protected $clientId;
    protected $request;

    public function __construct($options = array(), Request $request = null)
    {
        $this->setOptions((array)$options);

        $this->request = $request ?: Request::createFromGlobals();
    }

    protected function setOptions(array $options)
    {
        $defaults = $this->getDefaults();
        $options  = array_intersect_key($options + $defaults, $defaults);

        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->setClientId(isset($options['clientId']) ? $options['clientId'] : null);
    }

    protected function getDefaults()
    {
        return array(
            'baseUrl'      => 'http://localhost:5000',
            'cookiePrefix' => 'sixpack',
            'timeout'      => 500,
            'forcePrefix'  => 'sixpack-force-',
        );
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
        return UuidGenerator::v4();
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
            if (!$this->isValidExperimentName($alt)) {
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
        if (isset($params['experiment']) && !$this->isValidExperimentName($params['experiment'])) {
            throw new InvalidArgumentException(sprintf('Invalid Experiment Name: %s', $params['experiment']));
        }

        $browser = $this->prepareCurlBrowser();

        $return = $browser->get($this->prepareUrl($endpoint, $params))->getContent();
        $meta   = $browser->getClient()->getInfo();

        // handle failures in call dispatcher
        return array($return, $meta);
    }

    protected function prepareUrl($endpoint, $parameters)
    {
        $parameters += array(
            'client_id'  => $this->clientId,
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
        );

        // Dig out the reason of this hack.
        $parameters = preg_replace('/%5B(?:[0-9]+)%5D=/', '=', http_build_query($parameters));

        return sprintf('%s/%s?%s', $this->baseUrl, $endpoint, $parameters);
    }

    protected function prepareCurlBrowser()
    {
        $client = new Curl();
        $client->setTimeout($this->timeout / 1000);
        // Make sub 1 sec timeouts work, according to: http://ravidhavlesha.wordpress.com/2012/01/08/curl-timeout-problem-and-solution/
        $client->setOption(CURLOPT_NOSIGNAL, 1);

        return new Browser($client);
    }

    public function isValidExperimentName($name)
    {
        return 1 === preg_match('#^[a-z\d][a-z\d\-_ ]*$#i', $name);
    }
}
