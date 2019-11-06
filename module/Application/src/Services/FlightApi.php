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

    private $authClient;
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
    
    private function setAuthRestClient($value) {
        $this->authClient = $value;
    }

    private function getAuthRestClient() {
        return $this->authClient;
    }    

    /**
     * Get the Auth Rest Client necessary for the calls in the instance
     * Its generated if doesnot exist yet
     * @return RestClient
     */
    private function authRestClient(){
        $client = $this->getAuthRestClient();
        if(!empty($client)){
            return $client;
        }
        
        $username = self::USERNAME;
        $password = self::PASSWORD;
        $protocol = self::PROTOCOL;
        $domain = self::DOMAIN;

        $urlWithAuth = $protocol . '://' . $username . ':' . $password . '@' . $domain;
        $client = new RestClient($urlWithAuth);
        $client->setUri($urlWithAuth);        
        $this->setAuthRestClient($client);
        
        return $client;
    }
    
    /**
     * Call Get 
     * @param array $query - parameters
     * @return array
     */
    protected function restGet($query = []) {
        $path = $this->getPath();
        $client = $this->authRestClient();
        try {
            $response = $client->restGet($path, $query);
            $responseArray = json_decode($response->getBody(), true);            
        } catch (\Throwable $ex) {
            // log "no data for $path.$query"
            $responseArray = [];
        }
        return $responseArray;
    }

}
