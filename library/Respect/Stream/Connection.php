<?php

namespace Respect\Stream;

class Connection
{

    protected $readSize = 8192;
    protected $resource;
    protected $address;
    protected $readBuffer = '';
    protected $lineBuffer = '';
    protected $writeBuffer = '';
    protected $eventListeners = array();

    public function setReadSize($readSize)
    {
        $this->readSize = $readSize;
    }

    public function isConnected()
    {
        return (!empty($this->resource));
    }

    public function connect($address)
    {
        $this->address = $address;
        $this->resource = stream_socket_client(
            $address, $errno, $errstr, 15,
            STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT
        );
        stream_set_blocking($this->resource, false);
    }

    public function __call($methodName, $arguments)
    {
        if ('on' === substr($methodName, 0, 2)) {
            $params = $arguments;
            array_unshift($params, lcfirst(substr($methodName, 2)));
            call_user_func_array(
                array($this, 'registerEvent'), $params
            );
            return $this;
        }
    }

    public function registerEvent($eventName, $callback=null)
    {
        if (!isset($this->eventListeners[$eventName])) {
            $this->eventListeners[$eventName] = array();
        }
        return $this->eventListeners[$eventName][] = $callback;
    }

    public function unregisterEvent($eventName, $callback)
    {
        if (isset($this->eventListeners[$eventName])) {
            $eventKey = array_search(
                $callback, $this->eventListeners[$eventName]
            );
            unset($this->eventListeners[$eventName][$eventKey]);
        }
    }

    public function hasEvent($eventName)
    {
        return isset($this->eventListeners[$eventName])
        && !empty($this->eventListeners[$eventName]);
    }

    public function callEvent($eventName, array $params=array())
    {
        if ($this->hasEvent($eventName))
            foreach ($this->eventListeners[$eventName] as $ev)
                if (is_callable($ev))
                    call_user_func_array($ev, $params);
    }

    public function bind($eventsArray)
    {
        foreach ($eventsArray as $name => $callback) {
            $this->registerEvent($name, $callback);
        }
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
        if (!$this->hasEvent('data')
            && !$this->hasEvent('line')
            && !$this->hasEvent('complete'))
            return;

        $read = '';
        if (is_resource($this->resource))
            $read = fread($this->resource, $this->readSize);


        if ($this->hasEvent('complete'))
            $this->readBuffer .= $read;

        if ($this->hasEvent('line'))
            $this->readLinesFromBuffer($read);
        elseif ($this->hasEvent('data'))
            $this->readDataFromBuffer($read);


        if (0 === strlen($read))
            $this->tearDown();

        if (is_resource($this->resource) && feof($this->resource))
            $this->tearDown();
    }

    protected function tearDown()
    {
        if ($this->hasEvent('line') && '' !== $this->lineBuffer)
            $this->callEvent('line', array($this->lineBuffer));
        $this->callEvent('complete', array($this->readBuffer));
        $this->close();
    }

    protected function readDataFromBuffer($read)
    {
        $this->callEvent('data', array($read));
    }

    protected function readLinesFromBuffer($read)
    {
        $this->callEvent('data', array($read));
        $this->lineBuffer .= $read;
        if (false === stripos($this->lineBuffer, chr(10)))
            return false;
        $lines = explode(chr(10), $this->lineBuffer);
        $buffer = array_pop($lines);
        foreach ($lines as $k => $l) {
            $this->callEvent('line', array($l));
        }
        $this->lineBuffer = $buffer;
    }

    public function performWrite()
    {
        if ('' === $this->writeBuffer)
            return;
        $wrote = '';
        if (is_resource($this->resource))
            $wrote = fwrite($this->resource, $this->writeBuffer);
        $this->writeBuffer = substr($this->writeBuffer, $wrote);
    }

    public function write($data)
    {
        $this->writeBuffer .= $data;
    }

    public function close()
    {
        $this->callEvent('end');
        if (is_resource($this->resource))
            fclose($this->resource);
    }

}