<?php

namespace Application\Services;

use ZendRest\Client\RestClient;

class FlightApi {

    const PROTOCOL = 'http';
    const DOMAIN = 'tstapi.duckdns.org';
    const USERNAME = "php-applicant";
    const PASSWORD = "Z7VpVEQMsXk2LCBc";
    
    const PATH_FLIGHT_ROUTES = 'api/json/1F/flightroutes/';
    const PATH_FLIGHT_SCHEDULES = 'api/json/1F/flightschedules/';
    const PATH_FLIGHT_AVAILABILITY = 'api/json/1F/flightavailability/';

    private $path;

    public function __construct(string $path) {
        $this->setPath($path);
    }

    private function setPath(string $value) {
        $this->path = $value;
    }

    private function getPath() {
        return $this->path;
    }

    public function get() {
        $username = self::USERNAME;
        $password = self::PASSWORD;
        $protocol = self::PROTOCOL;
        $domain = self::DOMAIN;
        $path = $this->getPath();

        $urlWithAuth = $protocol . '://' . $username . ':' . $password . '@' . $domain;
        $client = new RestClient($urlWithAuth);
        $client->setUri($urlWithAuth);
        $response = $client->restGet($path);
        $responseArray = json_decode($response->getBody(), true);

        return $responseArray;
    }

}
