<?php

date_default_timezone_set('America/Sao_Paulo');
set_include_path('./library/' . PATH_SEPARATOR . get_include_path());
require_once 'SplClassLoader.php';
$respectLoader = new \SplClassLoader();
$respectLoader->register();

$client = new Respect\Stream\Client;

$connection = new Respect\Stream\Wrappers\Http('2.kingolabs.com:80');
$connection->onData(function($data) {
        //echo $data;
    }
);
$connection->onRedirect(function($code, $url, $data) {
        echo "$code, $url\n";
    }
);
$connection->onHeader(function($name, $value) {
        echo "$name: $value\n";
    }
);
$connection->onBody(function($content) {
        //echo $content;
        echo 'body';
    }
);
$connection->get('/');
$connection->get('/');
$connection->get('/');
$connection->get('/');
$connection->get('/');
$client->addConnection($connection);



$client->work();