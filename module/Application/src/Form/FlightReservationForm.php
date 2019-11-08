<?php

namespace Application\Form;

use Application\Services\FlightRoutes;
use Zend\Form\Form;
use Zend\Form\Element;
use Zend\InputFilter\InputFilter;
use Zend\Form\Element\Radio as ZendFormElementRadio;

/**
 * This form is used to collect user registration data. This form is multi-step.
 * It determines which fields to create based on the $step argument you pass to
 * its constructor.
 */
class FlightReservationForm extends Form {
    
    const STEP = 'step';
    const STEP_1 = 1;
    const STEP_2 = 2;
    
    const MAX_SEATS_TO_SHOW = 10;

    private $flightRoutes;
    private $flightAvailability;
    private $userChoices;

    /**
     * Constructor.     
     */
    public function __construct($step, $userFormChoices, $flightAvailability = []) {
        // Check input.
        if (!is_int($step) || $step < self::STEP_1 || $step > self::STEP_2){
            throw new \Exception('Step is invalid');
        }
            
        // Define form name
        parent::__construct('flightreservation-form');

        // Set POST method for this form
        $this->setAttribute('method', 'post');      
        $this->setFlightAvailability($flightAvailability);
        $this->setUserChoices($userFormChoices);
        
        $this->addElements($step);
        $this->addInputFilter($step);
        
    }
    
    private function setFlightAvailability(array $value) {       
        $this->flightAvailability = $value;        
    }

    private function getFlightAvailability() {    
        return $this->flightAvailability;
    }
    
    private function setUserChoices(array $value) {       
        $this->userChoices = $value;        
    }

    private function getUserChoices() {    
        return $this->userChoices;
    }    

    /**
     * This method adds elements to form (input fields and submit button).
     */
    protected function addElements($step) {

        if ($step == self::STEP_1) {
            $this->addElementsStep1();
        } else if ($step == self::STEP_2) {
            $this->addElementsStep2();
        }

        // Add the CSRF field
        $this->add([
            'type' => 'csrf',
            'name' => 'csrf',
            'attributes' => [],
            'options' => [
                'csrf_options' => [
                    'timeout' => 600
                ]
            ],
        ]);

        // Add the submit button
        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'value' => 'Next Step',
                'id' => 'submitbutton',
            ],
        ]);
    }

    /**
     * 
     */
    private function addElementsStep1(){
        
        $this->add([
            'type' => 'radio',
            'name' => 'oneway_or_roundtrip',
            'options' => [
                'label' => '',
                'value_options' => [
                    '1' => ' Round-trip flight ',
                    '2' => ' One way ',
                ],
            ],
            'attributes' => [
                'value' => '2', // This set the opt 2 as selected when form is rendered                
                'id' => 'oneway_or_roundtrip',
            ]
         ]);     
        
        // Add "from" field
        $allDepartureAirports = $this->getAllUniqueDepartureAirportsValueOptionsWithExtraInfo();        
        $this->add([
            'type' => 'select',
            'name' => 'from',
            'attributes' => [
                'id' => 'from',
                'class' => 'form-control',
            ],
            'options' => [
                'empty_option' => 'From',
                'value_options' => $allDepartureAirports
            ],
        ]);

        // Add "to" field
        $allDestinationAirports = $this->getAllDestinationAirportsValueOptions();  
        $this->add([
            'type' => 'select',
            'name' => 'to',
            'attributes' => [
                'id' => 'to',
                'class' => 'form-control',
                'disabled' => 'disabled' // This set the opt 2 as selected when form is rendered
            ],
            'options' => [
                'empty_option' => 'To',
                'value_options' => $allDestinationAirports
            ],         
        ]);
        
        // Add "fromdate" field
        $this->add([
            'type' => 'text',
            'name' => 'departure-date',
            'attributes' => [
                'id' => 'departure-date',
                'class' => 'form-control',
                'placeholder' => 'Departure',
                'disabled' => 'disabled' // This set the opt 2 as selected when form is rendered
            ],
        ]);   
        
        // Add "fromdate" field
        $this->add([
            'type' => 'text',
            'name' => 'return-date',
            'attributes' => [
                'id' => 'return-date',
                'class' => 'form-control',
                'placeholder' => 'Return',
                'disabled' => 'disabled' // This set the opt 2 as selected when form is rendered
            ],
        ]);         

        // Add "num_adults" field
        $this->add([
            'type' => 'select',
            'name' => 'num_adults',
            'attributes' => [
                'id' => 'num_adults',
                'class' => 'form-control',
            ],
            'options' => [                               
                'value_options' => [
                    '1' => '1 Adult',
                    '2' => '2 Adults',
                    '3' => '3 Adults',
                    '4' => '4 Adults',                 
                ],
            ],
        ]);
            
        // Add "num_children" field
        $this->add([
            'type' => 'select',
            'name' => 'num_children',
            'attributes' => [
                'id' => 'num_children',
                'class' => 'form-control',
            ],
            'options' => [                       
                'value_options' => [
                    '0' => '0 Children',
                    '1' => '1 Children',
                    '2' => '2 Children',
                    '3' => '3 Children',
                    '4' => '4 Children',              
                ],
            ],
        ]);
        
            
        // Add "num_babies" field
        $this->add([
            'type' => 'select',
            'name' => 'num_babies',
            'attributes' => [
                'id' => 'num_babies',
                'class' => 'form-control',
            ],
            'options' => [                       
                'value_options' => [
                    '0' => '0 Babies',
                    '1' => '1 Baby',
                    '2' => '2 Babies',
                    '3' => '3 Babies',
                    '4' => '4 Babies',              
                ],
            ],
        ]);          

    }
    
    private function addElementsStep2(){
        
        $extraData = $this->getFlightAvailability();
        //@todo adapter is need. This data income from WS Flight Availability directly        
        $availableDeparturesValueOptions = $this->getFlightsValueOptionsWithExtraInfo($extraData['OUT'], 'departure'); 
        

        $this->add([
            'type' => 'radio',
            'name' => 'available_departure_time',
            'options' => [
                'value_options' => $availableDeparturesValueOptions,              
            ],
            'attributes' => [
                'value' => '', // This set the opt 2 as selected when form is rendered                                
                'class'=> "js-radiobutton_departure"
            ]
         ]);     
        
        $availableReturnValueOptions = [];
        if(!empty($extraData['RET'])){
            $availableReturnValueOptions = $this->getFlightsValueOptionsWithExtraInfo($extraData['RET'], 'return'); 
        }
        
        $this->add([
            'type' => 'radio',
            'name' => 'available_return_time',
            'options' => [
                'value_options' => $availableReturnValueOptions,              
            ],
            'attributes' => [
                'value' => '', // This set the opt 2 as selected when form is rendered                                
                'class'=> "js-radiobutton_return"
            ]
         ]);         
    }
    
    
    /**
     * This method creates input filter (used for form filtering/validation).
     */
    private function addInputFilter($step) {
        $inputFilter = $this->getInputFilter();
        if ($step == self::STEP_1) {
            $this->addInputFilterStep1($inputFilter);
        } else if ($step == self::STEP_2) {
            $this->addInputFilterStep2($inputFilter);
        }        
    }
    
    /**
     * 
     * @param InputFilter $inputFilter
     */
    private function addInputFilterStep1($inputFilter){
        // Add input for "From" field
        $inputFilter->add([
            'name' => 'from',
            'required' => true,
            'filters' => [
            ],
            'validators' => [ //@todo change for "InArray" with possible values
                ['name' => 'StringLength', 'options' => ['min' => 1, 'max' => 10]]
            ],  
        ]);

        // Add input for "To" field
        $inputFilter->add([
            'name' => 'to',
            'required' => true,
            'filters' => [
            ],
            'validators' => [ //@todo change for "InArray" with possible values
                ['name' => 'StringLength', 'options' => ['min' => 1, 'max' => 10]]
            ],            
        ]);
        
        $inputFilter->add([
            'name' => 'num_adults',
            'required' => true,
            'filters' => [],
            'validators' => [
                ['name' => 'IsInt'],
                ['name' => 'Between', 'options' => ['min' => 1, 'max' => 4]]
            ],
        ]);
        
        $inputFilter->add([
            'name' => 'num_children',
            'required' => true,
            'filters' => [],
            'validators' => [
                ['name' => 'IsInt'],
                ['name' => 'Between', 'options' => ['min' => 0, 'max' => 4]]
            ],
        ]); 
        
        $inputFilter->add([
            'name' => 'num_babies',
            'required' => true,
            'filters' => [],
            'validators' => [
                ['name' => 'IsInt'],
                ['name' => 'Between', 'options' => ['min' => 0, 'max' => 4]]
            ],
        ]);         
        
    }
    
    /**
     * 
     * @param InputFilter $inputFilter
     */
    private function addInputFilterStep2($inputFilter){       
        $inputFilter->add([
            'name' => 'available_departure_time',
            'required' => true,
        ]);    
                
        $userChoices = $this->getUserChoices();
        $availableReturnTimeRequired = self::returnDateWasChosenInStep1($userChoices); 
        $inputFilter->add([
            'name' => 'available_return_time',
            'required' => $availableReturnTimeRequired,
        ]);                     
    }
    
    /**
     * If return-date was chosen in step1      
     * @return bool
     */
    public static function returnDateWasChosenInStep1($userChoices){
        return $userChoices[self::STEP.self::STEP_1]['return-date']!='';
    }
    
    
    /**
     * Get all unique departura airports value options, and add extra info
     * @return array
     */
    private function getAllUniqueDepartureAirportsValueOptionsWithExtraInfo(){
        $result = [];
        $uniqueDepartureAirports = $this->getAllUniqueDepartureAirportsValueOptions();
        foreach($uniqueDepartureAirports as $code => $label){
            $hasAtLeastOneRoundTrip = $this->hasAtLeastOneRoundTrip($code) ? 1 : 0;
            $option['label'] = $label;
            $option['value'] = $code;
            $option['attributes'] = ['data-hassomereturn'=>$hasAtLeastOneRoundTrip];
            $result[] = $option;
        }
        return $result;
    }
    
    /**
     * One airport X has at leat one Round-trip if it has at least one destination
     * than has a flight with destination this airport X
     * @param string $code
     */
    private function hasAtLeastOneRoundTrip($code){  
        $flightRoutes = $this->getFlightRoutes();
        $retCodesOfCode = []; // todos sus destinos
        $allFlights = []; // todas las conexiones
         
        foreach($flightRoutes as $flightRoute){          
            $depCode = $flightRoute['DepCode'];
            $retCode = $flightRoute['RetCode'];
 
            $allFlights[$depCode][$retCode] = 1;
            if($depCode==$code){
                $retCodesOfCode[] = $retCode;
            }
        }

        // para cada destino, busco si hay un origen igual al code original
        foreach($retCodesOfCode as $retCodeOfCode){
            if(isset($allFlights[$retCodeOfCode][$code])){
                return true;
            }
        }
        return false;
    }
    
    /**
     * 
     * @return array
     */
    private function getAllUniqueDepartureAirportsValueOptions(){
        $result = []; 
        $flightRoutes = $this->getFlightRoutes();
        
        foreach($flightRoutes as $flightRoute){
            $depCode = $flightRoute['DepCode'];
            $depName = $flightRoute['DepName'];
            $depCountry = $flightRoute['DepCountry'];            
            $result[$depCode] = $depCode . ' - ' . $depName . ' ('.$depCountry.')';
        }
        return $result;
    }
    

    private function getAllDestinationAirportsValueOptions(){
        $result = [];     
        $flightRoutes = $this->getFlightRoutes();
        
        foreach($flightRoutes as $flightRoute){
            $depCode = $flightRoute['DepCode'];
            $code = $flightRoute['RetCode'];
            $name = $flightRoute['RetName'];
            $country = $flightRoute['RetCountry'];            
            
            $label = $code . ' - ' . $name . ' ('.$country.')';
            $option['label'] = $label;
            $option['value'] = $code;
            $option['attributes'] = ['data-depcode'=>$depCode];
            $result[] = $option;
        }
        
        return $result;
    }
    
    /**
     * 
     * @return array
     * Exmple:
     *      [
                "AGP" => "AGP - M\u00e1laga Airport (Spain)",
                "AKK" => "AKK - Bla (Bla)" 
            ]
     */
    private function getFlightRoutes(){
        $result = $this->flightRoutes;
        if(empty($result)){                  
            $flightRoutesApi = new FlightRoutes();            
            $flightRoutes = $flightRoutesApi->getAll();            
            $this->flightRoutes = $flightRoutes;
            $result = $flightRoutes;
        }
        return $result;
    }
    
    
    /**
     * 
     * @param array $flights - The OUT flights availability data
     * Sample:

        Array
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

     * 
     * 
       @return array
        Sample:  [
        [
            'value' => '0',
            'label' => 'Apple',
            'selected' => false,
            'disabled' => false,
            'attributes' => [
                'id' => 'apple_option',                            
            ],
            'label_attributes' => [
                'class' => 'list-group-item js-radiocomponent_departure',
                'data-for_id' => 'apple_option',
            ]
       ],
       [               
            'value' => '1',
            'label' => 'Orange',
            'selected' => false,
            'disabled' => false,
            'attributes' => [
                'id' => 'orange_option',
            ],
            'label_attributes' => [
                'class' => 'list-group-item  js-radiocomponent_departure',
                'data-for_id' => 'orange_option',
            ]
       ], 
     */
    private function getFlightsValueOptionsWithExtraInfo($flights, $type){
        $result = [];
        
        $cont = 0;
        foreach($flights as $flight){
            $idFlight = $type.$cont;
            
            $label = $this->getRadiobuttonLabel($flight);
            $element['value'] = self::getUniqueIdentifierAvailableFlight($flight);
            $element['label'] = $label;
            $element['selected'] = false;
            $element['disabled'] = false;
            $element['attributes']['id'] = $idFlight;
            $element['label_attributes']['class'] = 'list-group-item  js-radiocomponent_'.$type;
            $element['label_attributes']['id'] = 'js-radiolabel_'.$type.'_'.$cont;
            $element['label_attributes']['data-for_id'] = $idFlight;                        
            $cont++;
            $result[] = $element;
        }        
        return $result;
    }
          
    
    /**
     * Generate a unique identifier of flight 
     * @param array $flight
        Example:
            Array
            (
                [date] => 2019-11-21
                [aircrafttype] => Boeing
                [datetime] => 2019-11-21T08:09:00
                [price] => 329
                [seatsAvailable] => 27
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
                                [code] => BRU
                                [name] => Brussels Airport
                            )

                    )

            )
     */
    public static function getUniqueIdentifierAvailableFlight(array $flight){
        return $flight['depart']['airport']['code'].'-'.$flight['arrival']['airport']['code'].'|'.
                $flight['aircrafttype'].'|'.
                $flight['datetime'];
    }
    
    /**
     * @todo make decorator for radiobuttom group, and i could put html elements
     * 
     * in text
     * @param array $flight
     * @return array
     */
    private function getRadiobuttonLabel(array $flight){
        $datetimeDeparture = $flight['datetime'];                        
        $timeDeparture = date("H:i", strtotime($datetimeDeparture));
        // $datetimeArrival = $flight['datetime']; //@todo Where is arrival datetime???      
        $price = $flight['price'];
        $priceFormatted = '€'.number_format($price, 2, ',', '.');
        $seats = $flight['seatsAvailable'];
        
        $result = $flight['depart']['airport']['name']. ' '.$timeDeparture.' -> ' . $flight['arrival']['airport']['name'];
        $result .= ' ('.$priceFormatted.')';
        
        if($seats < self::MAX_SEATS_TO_SHOW){
            $result .= ' [Only '.$seats.' tickets at '.$priceFormatted.']';
        }        
  
        return $result;
    }

}
