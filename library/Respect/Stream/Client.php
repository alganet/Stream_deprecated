<?php

namespace Respect\Stream;

use SplObjectStorage;

class Client
{

    protected $sockets = array();
    protected $connections = array();

    public function addConnection(Connection $connection)
    {
        if (!$connection->isConnected())
            $connection->connect();
        $this->connections[] = $connection;
        $this->sockets[] = $connection->getResource();
        $id = array_search($connection, $this->connections);
        $client = $this;
        $connection->onEnd(function() use ($id, &$client) {
                $client->removeConnectionById($id);
            }
        );
        return $id;
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
                    $id = array_search($r, $this->sockets);
                    if (isset($this->connections[$id]))
                        $this->connections[$id]->performRead();
                }
                foreach ($write as $w) {
                    $id = array_search($w, $this->sockets);
                    if (isset($this->connections[$id]))
                        $this->connections[$id]->performWrite();
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