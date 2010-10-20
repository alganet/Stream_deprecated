<?php

namespace Respect\Stream\Wrappers;

class Http extends Socket
{
    const STATE_CONNECTED=1;
    const STATE_STATUS=5;
    const STATE_HEADERS=9;
    const STATE_BODY=13;

    public $onStatus = array();
    public $onSuccess = array();
    public $onRedirect = array();
    public $onClientError = array();
    public $onServerError = array();
    public $onHeader = array();
    public $onBody = array();
    public $state = null;
    public $requests = array();
    public $currentRequest = array();

    public function __construct($address)
    {
        parent::__construct($address);
        $this->state = self::STATE_CONNECTED;
        $this->registerEvent('onLine', array($this, 'handleLine'));
    }

    protected function handleLine($line)
    {
        switch ($this->state) {
            case self::STATE_STATUS:
                list($version, $code, $message) = explode(' ', $line, 3);
                $codeClass = substr($code, 0, 1);
                $params = array(
                    $code,
                    $this->currentRequest['url'],
                    $this->currentRequest['data']
                );
                $this->callEvent('onStatus', $params);
                switch ($codeClass) {
                    case 2:
                        $this->callEvent('onSuccess', $params);
                        break;
                    case 3:
                        $this->callEvent('onRedirect', $params);
                        break;
                    case 4:
                        $this->callEvent('onClientError', $params);
                        break;
                    case 5:
                        $this->callEvent('onServerError', $params);
                        break;
                }
                $this->state = self::STATE_HEADERS;
                break;
            case self::STATE_HEADERS:
                if (13 === ord($line)) {
                    $this->state = self::STATE_BODY;
                    $this->registerEvent('onData', array($this, 'handleData'));
                    $this->unregisterEvent('onLine');
                    return;
                }
                $headerLine = explode(':', $line, 2);
                foreach ($headerLine as &$l)
                    $l = trim($l);
                $this->callEvent('onHeader', $headerLine);
        }
    }

    protected function handleData($data)
    {
        $this->bodyBuffer .= $data;
    }

    protected function httpRequest($type, $headers, $url, $data=null)
    {
        $type = strtoupper($type);
        $relativeUrl = '';
        $urlParts = parse_url($url);

        if (isset($urlParts['path']))
            $relativeUrl .= $urlParts['path'];
        else
            $relativeUrl .= '/';

        if (isset($urlParts['query']))
            $relativeUrl .= "?{$urlParts['query']}";

        $headers['Host'] = parse_url($this->getAddress(), PHP_URL_HOST);
        $headersString = array();
        foreach ($headers as $key => $value) {
            $key = trim($key);
            $value = trim($value);
            $headersString[] = "$key: $value";
        }
        $headersString = implode("\r\n", $headersString);
        $data = $data ? : $data . "\r\n";
        echo "$type $relativeUrl HTTP/1.1\r\n$headersString\r\n$data";
        $this->write("$type $relativeUrl HTTP/1.1\r\n$headersString\r\n$data");
    }

    protected function enqueue($type, $headers, $url, $data=null)
    {
        $this->requests[] = compact('type', 'headers', 'url', 'data');
    }

    public function performWrite()
    {
        if (self::STATE_CONNECTED !== $this->state)
            return;
        if (empty($this->writeBuffer) && !empty($this->requests))
            call_user_func_array(
                array($this, 'httpRequest'),
                $this->currentRequest = array_shift($this->requests)
            );
        parent::performWrite();
        if (empty($this->writeBuffer))
            $this->state = self::STATE_STATUS;
    }

    public function onStatus($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onSuccess($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onRedirect($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onClientError($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onServerError($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onHeader($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onBody($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function get($url, array $headers=array())
    {
        $this->enqueue(__FUNCTION__, $headers, $url);
    }

    public function head($url)
    {
        $this->enqueue(__FUNCTION__, $headers, $url);
    }

    public function delete($url)
    {
        $this->enqueue(__FUNCTION__, array(), $url);
    }

    public function post($data, $url, $headers=array())
    {
        $this->enqueue(__FUNCTION__, $headers, $url, $data);
    }

    public function put($data, $url, $headers=array())
    {
        $this->enqueue(__FUNCTION__, $headers, $url, $data);
    }

}