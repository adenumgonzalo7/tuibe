<?php

namespace Application\Form;

use Application\Services\FlightRoutes;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

/**
 * This form is used to collect user registration data. This form is multi-step.
 * It determines which fields to create based on the $step argument you pass to
 * its constructor.
 */
class FlightReservationForm extends Form {
    
    const STEP_1 = 1;
    const STEP_2 = 2;
    
    private $flightRoutes;

    /**
     * Constructor.     
     */
    public function __construct($step) {
        // Check input.
        if (!is_int($step) || $step < self::STEP_1 || $step > self::STEP_2)
            throw new \Exception('Step is invalid');

        // Define form name
        parent::__construct('flightreservation-form');

        // Set POST method for this form
        $this->setAttribute('method', 'post');

        $this->addElements($step);
        $this->addInputFilter($step);
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
                'value' => '2' // This set the opt 2 as selected when form is rendered
            ]
         ]);     
        
        // Add "from" field
        $allDepartureAirports = $this->getAllUniqueDepartureAirportsValueOptionsWithExtraInfo();        
        $this->add([
            'type' => 'select',
            'name' => 'from',
            'attributes' => [
                'id' => 'from',
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
                'disabled' => 'disabled' // This set the opt 2 as selected when form is rendered
            ],
            'options' => [
                'empty_option' => 'To',
                'value_options' => $allDestinationAirports
            ],         
        ]);
    }
    
    private function addElementsStep2(){
        // Add "phone" field
        $this->add([
            'type' => 'text',
            'name' => 'phone',
            'attributes' => [
                'id' => 'phone'
            ],
            'options' => [
                'label' => 'Mobile Phone',
            ],
        ]);

        // Add "street_address" field
        $this->add([
            'type' => 'text',
            'name' => 'street_address',
            'attributes' => [
                'id' => 'street_address'
            ],
            'options' => [
                'label' => 'Street address',
            ],
        ]);

        // Add "city" field
        $this->add([
            'type' => 'text',
            'name' => 'city',
            'attributes' => [
                'id' => 'city'
            ],
            'options' => [
                'label' => 'City',
            ],
        ]);

        // Add "state" field
        $this->add([
            'type' => 'text',
            'name' => 'state',
            'attributes' => [
                'id' => 'state'
            ],
            'options' => [
                'label' => 'State',
            ],
        ]);

        // Add "post_code" field
        $this->add([
            'type' => 'text',
            'name' => 'post_code',
            'attributes' => [
                'id' => 'post_code'
            ],
            'options' => [
                'label' => 'Post Code',
            ],
        ]);

        // Add "country" field
        $this->add([
            'type' => 'select',
            'name' => 'country',
            'attributes' => [
                'id' => 'country',
            ],
            'options' => [
                'label' => 'Country',
                'empty_option' => '-- Please select --',
                'value_options' => [
                    'US' => 'United States',
                    'CA' => 'Canada',
                    'BR' => 'Brazil',
                    'GB' => 'Great Britain',
                    'FR' => 'France',
                    'IT' => 'Italy',
                    'DE' => 'Germany',
                    'RU' => 'Russia',
                    'IN' => 'India',
                    'CN' => 'China',
                    'AU' => 'Australia',
                    'JP' => 'Japan'
                ],
            ],
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
            'validators' => [
                [
                    'name' => 'InArray',
                    'options' => [
                        'haystack' => [
                            'Free',
                            'Bronze',
                            'Silver',
                            'Gold',
                            'Platinum'
                        ]
                    ]
                ]
            ],
        ]);

        // Add input for "To" field
        $inputFilter->add([
            'name' => 'to',
            'required' => true,
            'filters' => [
            ],
            'validators' => [
                [
                    'name' => 'InArray',
                    'options' => [
                        'haystack' => [
                            'PayPal',
                            'Visa',
                            'MasterCard',
                        ]
                    ]
                ]
            ],
        ]);
    }
    
    private function addInputFilterStep2($inputFilter){

            $inputFilter->add([
                'name' => 'phone',
                'required' => true,
                'filters' => [
                ],
                'validators' => [
                    [
                        'name' => 'StringLength',
                        'options' => [
                            'min' => 3,
                            'max' => 32
                        ],
                    ],
                ],
            ]);

            // Add input for "street_address" field
            $inputFilter->add([
                'name' => 'street_address',
                'required' => true,
                'filters' => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    ['name' => 'StringLength', 'options' => ['min' => 1, 'max' => 255]]
                ],
            ]);

            // Add input for "city" field
            $inputFilter->add([
                'name' => 'city',
                'required' => true,
                'filters' => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    ['name' => 'StringLength', 'options' => ['min' => 1, 'max' => 255]]
                ],
            ]);

            // Add input for "state" field
            $inputFilter->add([
                'name' => 'state',
                'required' => true,
                'filters' => [
                    ['name' => 'StringTrim'],
                ],
                'validators' => [
                    ['name' => 'StringLength', 'options' => ['min' => 1, 'max' => 32]]
                ],
            ]);

            // Add input for "post_code" field
            $inputFilter->add([
                'name' => 'post_code',
                'required' => true,
                'filters' => [
                ],
                'validators' => [
                    ['name' => 'IsInt'],
                    ['name' => 'Between', 'options' => ['min' => 0, 'max' => 999999]]
                ],
            ]);

            // Add input for "country" field
            $inputFilter->add([
                'name' => 'country',
                'required' => false,
                'filters' => [
                    ['name' => 'Alpha'],
                    ['name' => 'StringTrim'],
                    ['name' => 'StringToUpper'],
                ],
                'validators' => [
                    ['name' => 'StringLength', 'options' => ['min' => 2, 'max' => 2]]
                ],
            ]);
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
            $option['attributes'] = ['data-hasatleastoneroundtrip'=>$hasAtLeastOneRoundTrip];
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
                "AKK" => "AKK - Bla (Bla)" // aki no poner OST pq solo son salidas
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
           
        /*
        vamos a recorrer salidas todos y los devolvemos como:

                
        Las llegadas las ponemos todas tb en otro array aparte, pero por JS habra que controlarlo creo yo :P  o por AJAX, no se...      
         * 
         */
        /*
           {
         "DepCode":"AGP",
         "RetCode":"OST",
         "DepName":"M\u00e1laga Airport",
         "RetName":"Ostend\u2013Bruges International Airport",
         "DepCountry":"Spain",
         "RetCountry":"Belgium"
      },
         */
        
        
        
            

}
