<?php

namespace Respect\Stream\Wrappers;

use Respect\Stream\Streamable;

class Socket implements Streamable
{

    protected $resource;
    protected $address;
    protected $readBuffer = '';
    protected $lineBuffer = '';
    protected $writeBuffer = '';
    public $onData = array();
    public $onEnd = array();
    public $onLine = array();
    public $onComplete = array();

    public function __construct($address)
    {
        $this->address = $address;
        $this->resource = stream_socket_client(
            $address, $errno, $errstr, 15,
            STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT
        );
        stream_set_blocking($this->resource, false);
    }

    public function onEnd($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onData($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onLine($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function onComplete($callback = null)
    {
        return $this->registerEvent(__FUNCTION__, $callback);
    }

    public function registerEvent($eventName, $callback=null)
    {
        return $this->{$eventName}[] = $callback;
    }

    public function unregisterEvent($eventName)
    {
        return $this->{$eventName} = array();
    }

    public function callEvent($eventName, array $params=array())
    {
        foreach ($this->{$eventName} as $ev)
            if (is_callable($ev))
                call_user_func_array($ev, $params);
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function performRead()
    {
        if (!isset($this->onData) && !isset($this->onLine) && !isset($this->onComplete))
            return;
        $read = fread($this->resource, 8192);

        if (0 !== strlen($read))
            $this->callEvent('onData', array($read));
        else
            $this->tearDown();

        if (isset($this->onComplete))
            $this->readBuffer .= $read;

        if (isset($this->onLine))
            $this->readLinesFromBuffer($read);
    }

    protected function tearDown()
    {
        if (isset($this->onComplete))
            $this->callEvent('onComplete', array($this->readBuffer));
        $this->callEvent('onEnd');
    }

    protected function readLinesFromBuffer($read)
    {
        $this->lineBuffer .= $read;
        if (false === stripos($this->lineBuffer, "\n"))
            return false;
        $lines = explode("\n", $this->lineBuffer);
        $this->lineBuffer = array_pop($lines);
        foreach ($lines as $k => $l)
            if (!empty($this->onLine))
                $this->callEvent('onLine', array($l));
            elseif (!empty($this->onData))
                return $this->callEvent(
                    'onData', array(implode("\n", array_slice($lines, $k)))
                );
    }

    public function performWrite()
    {
        if ('' === $this->writeBuffer)
            return;
        $wrote = fwrite($this->resource, $this->writeBuffer);
        $this->writeBuffer = substr($this->writeBuffer, $wrote);
    }

    public function write($data)
    {
        $this->writeBuffer .= $data;
    }

    public function close()
    {
        fclose($this->resource);
    }

}