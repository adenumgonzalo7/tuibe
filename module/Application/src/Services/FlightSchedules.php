<?php

namespace Application\Services;

class FlightSchedules extends FlightApi{
    
    const RESPONSE_INDEX = 'flightschedules';
    
    private $fromCode; 
    private $toCode;
    private $hasReturn;
    
    
    public function __construct(){
        parent::__construct(parent::PATH_FLIGHT_SCHEDULES);
    }

    public function setFromCode(string $value) {
        $this->fromCode = $value;
    }

    public function getFromCode() {
        return $this->fromCode;
    }
    
    public function setToCode(string $value) {
        $this->toCode = $value;
    }

    public function getToCode() {
        return $this->toCode;
    }    
    
    public function setHasReturn(bool $value) {
        $this->hasReturn = $value;
    }

    public function getHasReturn() {
        return $this->hasReturn;
    }      
    
    /**
     * @todo Validations
     * @return array
     */
    public function get() {
    
        $fromCode = $this->getFromCode();
        $toCode = $this->getToCode();
        $hasReturn = $this->getHasReturn();
        
        if($fromCode){
            $queryParams['departureairport'] = $fromCode;
        }
        if($toCode){
            $queryParams['destinationairport'] = $toCode;
        }
     
        if($hasReturn){            
            $queryParams['returndepartureairport'] = $toCode;
            $queryParams['returndestinationairport'] = $fromCode;
        }        
        
        $response = parent::restGet($queryParams);        
        return $response[self::RESPONSE_INDEX];
    }
    
    /**
     * Reset the instance params and get all Flight Schedules 
     * @return array
     */
    public function getAll() {
        $this->setFromCode('');
        $this->setToCode('');
        $this->setToCode(false);           
        $result = $this->get();        
        return $result;
    }
    
}
