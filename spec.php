<?php

/**
 * http://github.com/Respect
 * 
 * Respect\Stream será um componente de alta performance para conexões com 
 * streams tais como requisições HTTP.
 * 
 * Esse é um exemplo de como a interface do componente deverá se comportar.
 * 
 */
use Respect\Stream;

/**
 * Eventos e funções disponíveis para quaisquer streams
 */ {

    $s = Stream::open('http://twitter.com'); //Abre a conexão


    $s->onData(8192, //Lê os dados 8192 bytes por vez. (fread)
        function($data) {
            
        }
    );


    $s->onLine(function($line, $line_number) { //Lê uma linha por vez (fgets)
        }
    );


    $s->onComplete(function($response) { //Chamado quando EOF é identificado e todo buffer é lido (feof, fclose)
        }
    );

    $s->onEnd(function() { //Chamado quando EOF é identificado (feof, fclose)
        }
    );

    $s->write("something", true); //fwrite
}
/**
 * Eventos e funções de arquivos CVS.
 */ {
    $s->onCSV(function($fields, $line_number) { //fgetscvs
        }
    );
    $s->writeCSV($fields);
}

/**
 * Eventos e funções para streams HTTP
 * 
 * Se houver possibilidade de trabalhar com Keep-Alive, o sistema reutilizará
 * as conexões previamente estabelecidas
 * 
 */ {

    $s = Stream::init($numberOfWorkers);


    $s->get($url, $headers = array()); //http GET
    $s->head($url, $headers = array()); //http HEAD
    $s->delete($url, $headers = array()); //http DELETE
    $s->post($data, $url, $headers = array()); //http POST
    $s->put($data, $url, $headers = array()); //http PUT

    $s->onSuccess(function($code, $url, $data) { //quando Status 2xx
        }
    );
    $s->onRedirect(function($code, $url, $data) {//quando Status 3xx
        }
    );
    $s->onServerError(function($code, $url, $data) { //quando Status 5xx
        }
    );

    $s->onHeader(function($name, $value, $code) { //a cada header HTTP
        }
    );
    $s->onBody(function($body, $headers, $code) { //quando o body termina de carregar
        }
    );

    $s->onSuccess(function() { //exemplo completo
            $s->onHeader(function($name, $value) {
                    if ($name == "Accept") {
                        $s->onBody(function($body) {
                                //faz algo com o body
                            }
                        );
                    } else {
                        //Respect\Stream nem carrega o body se não houver evento
                    }
                }
            );
        }
    );
    $s->onRedirect(301, //callback pra status 301
        function($code, $url, $data) {
            $s->onHeader('Location',
                function($value) {
                    //callback específico pra um determinado header
                }
            );
        }
    );
}

