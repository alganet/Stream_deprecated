<?php

namespace Respect\Stream;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{

    public function testSocketConnection()
    {
        $connection = new Connection('72.14.204.99:80');
        $this->assertTrue(is_resource($connection->getResource()));
    }

}