<?php

namespace Application\Services;

class FlightRoutes extends FlightApi{
    
    const RESPONSE_INDEX = 'flightroutes';
    
    public function __construct(){
        parent::__construct(parent::PATH_FLIGHT_ROUTES);
    }
    
    public function getAll() {
        $response = parent::restGet();        
        return $response[self::RESPONSE_INDEX];
    }

}
