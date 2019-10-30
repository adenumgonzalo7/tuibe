<?php

namespace Application\Services;

class FlightRoutes extends FlightApi{
    
    public function __construct(){
        parent::__construct(parent::PATH_FLIGHT_ROUTES);
    }

    public function getByDepartureAirportCode($departureAirportCode){
        
    }
    
    public function getByDestinationAirportCode($destinationAirportCode){
        
    }    
    
    public function getAll() {
        $response = $this->get();        
        return $response['flightroutes'];
    }

}
