<?php

namespace Respect\Stream;

use SplObjectStorage;

class Client
{

    protected $sockets = array();
    protected $connections = array();

    public function addConnection(Streamable $connection)
    {
        $this->connections[] = $connection;
        $this->sockets[] = $connection->getResource();
        return array_search($connection, $this->connections);
    }

    public function removeConnectionById($id)
    {
        unset($this->connections[$id], $this->sockets[$id]);
    }

    public function getConnectionById($id)
    {
        return $this->connections[$id];
    }

    public function work()
    {
        while (count($this->sockets)) {
            $read = $write = $this->sockets;
            $changed_streams = stream_select($read, $write, $except = null, 15);
            if (false === $changed_streams)
                return false;
            if ($changed_streams > 0) {
                foreach ($read as $r) {
                    $this->connections[array_search($r, $this->sockets)]->performRead();
                }
                foreach ($write as $w) {
                    $this->connections[array_search($w, $this->sockets)]->performWrite();
                }
            }
        }
    }

    public function __destruct()
    {
        foreach ($this->connections as $c) {
            $c->close();
        }
    }

}