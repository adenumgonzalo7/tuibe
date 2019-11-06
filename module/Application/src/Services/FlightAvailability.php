<?php

namespace Application\Services;

class FlightAvailability extends FlightApi{
    
    const RESPONSE_INDEX = 'flights';
    
    private $departureAirport; 
    private $destinationAirport;
    private $departureDate;
    
    private $returnDepartureAirport; 
    private $returnDestinationAirport;
    private $returnDate;
    
   
    public function __construct(){
        parent::__construct(parent::PATH_FLIGHT_AVAILABILITY);
    }

    public function setDepartureAirport(string $value) {
        $this->departureAirport = $value;
    }

    public function getDepartureAirport() {
        return $this->departureAirport;
    }
    
    public function setDestinationAirport(string $value) {
        $this->destinationAirport = $value;
    }

    public function getDestinationAirport() {
        return $this->destinationAirport;
    }   
    
    public function setDepartureDate(string $value) { //@todo hand with Datetime better...
        $result = $value ? $this->formatDateForParam($value) : '';
        $this->departureDate = $result;
    }

    public function getDepartureDate() {
        return $this->departureDate;
    }
    
    public function setReturnDepartureAirport(string $value) {
        $this->returnDepartureAirport = $value;
    }

    public function getReturnDepartureAirport() {
        return $this->returnDepartureAirport;
    }
    
    public function setReturnDestinationAirport(string $value) {
        $this->returnDestinationAirport = $value;
    }

    public function getReturnDestinationAirport() {
        return $this->returnDestinationAirport;
    }   
    
    public function setReturnDate(string $value) { //@todo hand with Datetime better...
        $result = $value ? $this->formatDateForParam($value) : '';
        $this->returnDate = $result;
    }

    public function getReturnDate() {
        return $this->returnDate;
    }
    
    private function formatDateForParam($date){        
        return date("Ymd", strtotime($date));        
    }
    
    /**
     * @todo Validations
     * @return array
     * 
     * Example:
            Array
            (
                [flights] => Array
                    (
                        [OUT] => Array
                            (
                                [0] => Array
                                    (
                                        [date] => 2019-11-11
                                        [aircrafttype] => Boeing
                                        [datetime] => 2019-11-11T11:18:00
                                        [price] => 68
                                        [seatsAvailable] => 8
                                        [depart] => Array
                                            (
                                                [airport] => Array
                                                    (
                                                        [code] => AGP
                                                        [name] => Málaga Airport
                                                    )

                                            )
                                        [arrival] => Array
                                            (
                                                [airport] => Array
                                                    (
                                                        [code] => FRA
                                                        [name] => Frankfurt Airport
                                                    )
                                            )
                                    )
                                [1] => Array
                                    (
                                        [date] => 2019-11-11
                                        [aircrafttype] => Boeing
                                        [datetime] => 2019-11-11T13:52:00
                                        [price] => 74
                                        [seatsAvailable] => 15
                                        [depart] => Array
                                            (
                                                [airport] => Array
                                                    (
                                                        [code] => AGP
                                                        [name] => Málaga Airport
                                                    )

                                            )
                                        [arrival] => Array
                                            (
                                                [airport] => Array
                                                    (
                                                        [code] => FRA
                                                        [name] => Frankfurt Airport
                                                    )
                                            )
                                    )
                            )
                    )

            )
     * 
     */
    public function get() {
    
        $fromCode = $this->getDepartureAirport();
        $toCode = $this->getDestinationAirport();
        $date = $this->getDepartureDate();
        
        $retFromCode = $this->getReturnDepartureAirport();
        $retToCode = $this->getReturnDestinationAirport();
        $retDate = $this->getReturnDate();        
        
        
        if($fromCode){
            $queryParams['departureairport'] = $fromCode;
        }
        if($toCode){
            $queryParams['destinationairport'] = $toCode;
        }     
        if($date){            
            $queryParams['departuredate'] = $date;        
        }        
        if($retFromCode){
            $queryParams['returndepartureairport'] = $retFromCode;
        }
        if($retToCode){
            $queryParams['returndestinationairport'] = $retToCode;
        }     
        if($retDate){            
            $queryParams['returndate'] = $retDate;        
        }        
        $response = parent::restGet($queryParams);        
        return $response[self::RESPONSE_INDEX];
    }
        
}
