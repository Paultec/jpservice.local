<?php

namespace Application\Service;

use Zend\Http\Client as HttpClient;
use Zend\Http\PhpEnvironment\Request as PhpEnvironmentRequest;

class PreParser
{
    public function getStructure($uri)
    {
        $client = new HttpClient();
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');

        $client->setOptions([
            CURLOPT_CONNECTTIMEOUT  => 30,
            CURLOPT_USERAGENT       => (new PhpEnvironmentRequest())->getServer('HTTP_USER_AGENT'),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => 1,
            CURLOPT_HEADER          => 0,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_CONNECTTIMEOUT  => 120,
            CURLOPT_TIMEOUT         => 120,
            CURLOPT_REFERER         => $uri,
        ]);

        $client->setUri($uri);
        $data = $client->send();

        return $data->getBody();
    }
}