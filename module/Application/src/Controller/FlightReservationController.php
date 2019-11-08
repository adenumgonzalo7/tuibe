<?php

/**
 * @todo: Adapters for all WS calls
 */

namespace Application\Controller;

use Application\Form\FlightReservationForm;
use Application\Services\FlightSchedules;
use Application\Services\FlightAvailability;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

/**
 * This is the controller class displaying a page with the User Flight Reservation form.
 * User registration has several steps, so we display different form elements on
 * each step. We use session container to remember user's choices on the previous
 * steps.
 */
class FlightReservationController extends AbstractActionController {
    
    const INDEX_STEP_1 = FlightReservationForm::STEP.FlightReservationForm::STEP_1;
    const INDEX_STEP_2 = FlightReservationForm::STEP.FlightReservationForm::STEP_2;
    
    /**
     * Session container.
     * @var Zend\Session\Container
     */
    private $sessionContainer;

    /**
     * Constructor. Its goal is to inject dependencies into controller.
     */
    public function __construct($sessionContainer) {
        $this->sessionContainer = $sessionContainer;
    }

    /**
     * This is the default "index" action of the controller. It displays the 
     * User Registration page.
     */
    public function indexAction() {
        // $extraData is used to pass response data WS to form
        $flighAvailability = [];
     
        // Determine the current step.
        $step = $this->getActualStep();

        if ($step == FlightReservationForm::STEP_1) {
            // Init user choices.
            $this->sessionContainer->userChoices = [];            
        } else if($step == FlightReservationForm::STEP_2){   
            $formDataStep1 =  $this->sessionContainer->userChoices[self::INDEX_STEP_1];          
            $flighAvailability = $this->getAvailableFlightOnDatesByFlightFormData($formDataStep1);
        }
        
        $userFormChoices = $this->sessionContainer->userChoices;
        $form = new FlightReservationForm($step, $userFormChoices, $flighAvailability);

        $route = $this->isFormPost($step, $form);
        if(!empty($route)){
            return $route;
        }

        $viewModel = new ViewModel(['form' => $form]);
        $viewModel->setTemplate("application/flightreservation/".FlightReservationForm::STEP.$step);

        return $viewModel;
    }
    
    /**
     * The "review" action shows a page allowing to review data entered on previous options
     * @return ViewModel
     * @throws \Exception
     */
    public function reviewAction() {
        // Validate session data.
        if (!isset($this->sessionContainer->step) ||
                $this->sessionContainer->step <= FlightReservationForm::STEP_2 ||
                !isset($this->sessionContainer->userChoices)) {
            throw new \Exception('Sorry, the data is not available for review yet');
        }

        $userChoices = $this->getReviewActionUserChoices();
        
        return new ViewModel(['userChoices' => $userChoices]);
    }
        
    /**
     * Process the AJAX calls of Form
     */
    public function ajaxAction(){
        $result = [];
        $post = $this->params()->fromPost();        
        // if more than one ajax call, refactor with abstract factory
        if(isset($post['action']) && $post['action']=='getFlightSchedules'){               
            $fromCode = $post['fromCode'] ?? '';
            $toCode = $post['toCode'] ?? '';
            $return = $post['hasReturn'] ?? 0;
            $result = $this->getFlightSchedulesForDatepickers($fromCode, $toCode, $return);
        }
        return new JsonModel($result); 
    }
        
    /**
     * Retrieve user choices from session for step Review, updated with the
     * actual info from WS
     * @return array
     * @throws \Exception
     */
    private function getReviewActionUserChoices(){
        // Retrieve user choices from session.
        $userChoices = $this->sessionContainer->userChoices;
        $step2Index = FlightReservationForm::STEP.FlightReservationForm::STEP_2;        
        $priceReturn = 0;
        
        // get the available flight date in realtime, if it is available yet.         
        $formDataStep1 =  $this->sessionContainer->userChoices[self::INDEX_STEP_1];  
        $formDataStep2 =  $this->sessionContainer->userChoices[self::INDEX_STEP_2];  
        $departureFlightId = $formDataStep2['available_departure_time'];
        $availableDepartureFlight = $this->getAvailableDepartureFlightOnDatesById($formDataStep1, $departureFlightId);   
        $availableDepartureFlight = $this->getFormattedAvailableFlight($availableDepartureFlight);
        $userChoices[$step2Index]['departure'] = $availableDepartureFlight;
        if(empty($availableDepartureFlight)){
            throw new \Exception('Sorry, the flight is not available');
        }
        
        // if it has return was selected, get the available flight date in realtime, if it is available yet.      
        if(FlightReservationForm::returnDateWasChosenInStep1($userChoices)){
            // get the available flight date in realtime, if it is available yet.         
            $returnFlightId = $formDataStep2['available_return_time'];
            $availableReturnFlight = $this->getAvailableReturnFlightOnDatesById($formDataStep1, $returnFlightId);   
            $availableReturnFlight = $this->getFormattedAvailableFlight($availableReturnFlight);
            $userChoices[$step2Index]['return'] = $availableReturnFlight;            
            if(empty($availableReturnFlight)){
                throw new \Exception('Sorry, the flight is not available');
            } 
        }
        
        $totalPrice = $this->getPriceFlight($userChoices);
        $userChoices[$step2Index]['total_price'] = $totalPrice;        
        return $userChoices;
    }
    
    private function getPriceFlight($userChoices){
        $numMustPayPerson = $userChoices[self::INDEX_STEP_1]['num_adults'] + $userChoices[self::INDEX_STEP_1]['num_children'];
        $outPrice = $userChoices[self::INDEX_STEP_2]['departure']['price'];
        $retPrice = $userChoices[self::INDEX_STEP_2]['return']['price'] ?? 0;
        
        $priceByPerson = $outPrice + $retPrice;        
        $result = $priceByPerson * $numMustPayPerson;
                
        return $result;
    }
    
    /**
     * 
     * @return string
     */
    public function getActualStep(){
        // Determine the current step.
        $step = FlightReservationForm::STEP_1;
        if (isset($this->sessionContainer->step)) {
            $step = $this->sessionContainer->step;
        }
        // Ensure the step is correct (between 1 and 2).
        if ($step < FlightReservationForm::STEP_1 || $step > FlightReservationForm::STEP_2){
            $step = FlightReservationForm::STEP_1;   
        }
        return $step;
    }
    
    /**
     * 
     * @param string $step
     * @param type $form
     * @return string - route. empty if not 
     */
    public function isFormPost($step, $form){
        $result = '';
        // Check if user has submitted the form
        if ($this->getRequest()->isPost()) {
            // Fill in the form with POST data
            $data = $this->params()->fromPost();
            $form->setData($data);
            // Validate form
            if ($form->isValid()) {       
                // Get filtered and validated data
                $data = $form->getData();
                // Save user choices in session.
                $this->sessionContainer->userChoices[FlightReservationForm::STEP.$step] = $data;
                // Increase step
                $step ++;
                $this->sessionContainer->step = $step;
                // If we completed all 3 steps, redirect to Review page.
                if ($step > FlightReservationForm::STEP_2) {
                    $result = $this->redirect()->toRoute('flightreservation', ['action' => 'review']);
                } else {
                    // Go to the next step.
                    $result = $this->redirect()->toRoute('flightreservation');
                }
            }
        }        
        return $result;
    }
        
    
    
    /**
     * 
     * @param string $fromCode
     * @param string $toCode
     * @param string $return
     * @return type
     */
    private function getFlightSchedulesForDatepickers($fromCode, $toCode, $return)
    {
        $flightSchedules = $this->getFlightSchedules($fromCode, $toCode, $return);
        $result = $this->formatFlightSchedulesForDatepickers($flightSchedules);        
        return $result;
    }
    
    /**
     * 
     * @param string $fromCode
     * @param string $toCode
     * @param int $return
     * @return type
     */
    private function getFlightSchedules(string $fromCode, string $toCode, int $return){
        $flightSchedules = new FlightSchedules();
        $flightSchedules->setFromCode($fromCode);
        $flightSchedules->setToCode($toCode);
        $flightSchedules->setHasReturn($return);                    
        $result = $flightSchedules->get();           
        return $result;
    }
    
    /**
     * Format the result for Datepickers, removing old dates
     * @param array $flightSchedules
     */
    private function formatFlightSchedulesForDatepickers(array $flightSchedules){
        $departureDates = [];
        $returnDates = [];        
        
        if(isset($flightSchedules['OUT']) && is_array($flightSchedules['OUT'])){
            $departureDates = $this->getRealDatesFlightSchedules($flightSchedules['OUT']);              
        }
        if(isset($flightSchedules['RET']) && is_array($flightSchedules['RET'])){
            $returnDates = $this->getRealDatesFlightSchedules($flightSchedules['RET']);            
        }          
        
        $result = [
            'departureDates' => $departureDates,
            'returnDates' => $returnDates,
        ];        
        return $result;     
    }
    
    /**
     * Return the dates (removing older than today)
     * @param array
     * @return array
     */
    private function getRealDatesFlightSchedules(array $flightSchedules)
    {
        $result = [];
        $dateNow = date('Y-m-d');
        foreach($flightSchedules as $flightSchedule){
            $dateFlight = $flightSchedule['date'];
            if($dateFlight >= $dateNow){ //@todo better throught Datetime...
                $result[] = $dateFlight;
            }            
        } 
        return $result;
    }
    
    /**
     * @todo comment
     * @param array $flightData
     * @param string $id
     * @return array
     */
    private function getAvailableDepartureFlightOnDatesById($flightData, $id){
        return $this->getAvailableFlightOnDatesById('OUT', $flightData, $id);
    }
    
    /**
     * @todo comment
     * @param array $flightData
     * @param string $id
     * @return array
     */
    private function getAvailableReturnFlightOnDatesById($flightData, $id){
        return $this->getAvailableFlightOnDatesById('RET', $flightData, $id);
    }    
    
    /**
     * @todo comment
     * @param string $indexFlightType
     * @param array $flightData
     * @param string $id
     * @return array
     */
    private function getAvailableFlightOnDatesById($indexFlightType, $flightData, $id){
        $result = [];
        $allFlights = $this->getAvailableFlightOnDatesByFlightFormData($flightData);
        
        $flights = $allFlights[$indexFlightType];
        foreach($flights as $flight){
            $flightId = FlightReservationForm::getUniqueIdentifierAvailableFlight($flight);          
            if($flightId==$id){
                return $flight;
            }            
        }
        return $result;
    }    
    
    /**
     * 
     * @param array $flightData
     *  departure-date: string Date
     *  return-date: string Date
     *  from: string
     *  to: string
     * @return array
     */
    private function getAvailableFlightOnDatesByFlightFormData($flightData){   
        $departureDate = $flightData['departure-date'] ?? ''; 
        $returnDate = $flightData['return-date'] ?? '';   // if !='' is round-trip    
        $departureCode = $flightData['from'] ?? '';       
        $destinationCode = $flightData['to'] ?? '';      
        return $this->getAvailableFlightOnDates($departureCode, $destinationCode, $departureDate, $returnDate);        
    }
    
    /**
     * 
     * @param string $departureCode
     * @param string $destinationCode
     * @param string $departureDate
     * @param string $returnDate
     * @return array
     */
    private function getAvailableFlightOnDates(
            string $departureCode, 
            string $destinationCode, 
            string $departureDate, 
            string $returnDate){
                
        $flights = new FlightAvailability();
        $flights->setDepartureAirport($departureCode);
        $flights->setDestinationAirport($destinationCode);
        $flights->setDepartureDate($departureDate);          
        
        if($returnDate){
            $flights->setReturnDepartureAirport($destinationCode);
            $flights->setReturnDestinationAirport($departureCode);
            $flights->setReturnDate($returnDate);  
        }

        $result = $flights->get();           
        return $result;
    }
  
    /**
     * Decorator for available Flight
     * @param array $availableFlight
     *   Array (
            [date] => 2019-11-28
            [aircrafttype] => Boeing
            [datetime] => 2019-11-28T06:24:00
            [price] => 28
            [duration] => 02:25:00
            [seatsAvailable] => 8
            [depart] => Array
            (
                [airport] => Array
                (
                    [code] => BRU
                    [name] => Brussels Airport
                )
            )
            [arrival] => Array
            (
                [airport] => Array
               (
                    [code] => OST
                    [name] => Ostend–Bruges International Airport
                )
            )
        )
     */
    public function getFormattedAvailableFlight(array $availableFlight){
        $result = $availableFlight;
        if(isset($availableFlight['date'])){
            $result['dateFormatted'] = date("D. j M Y", strtotime($result['date']));
        }
        if(isset($availableFlight['datetime']) && isset($availableFlight['duration'])){
            
            $outTime = new \DateTime($availableFlight['datetime']);
            $outTimeFormatted = $outTime->format('H:i');
            
            $durationDatetime = new \DateTime($availableFlight['duration']);
            $retTime = $this->getSumDates($outTime, $durationDatetime);
            $retTimeFormatted = $retTime->format('H:i');
            
            $result['outTime'] = $outTimeFormatted;
            $result['retTime'] = $retTimeFormatted;
        }        
        return $result;
    }
    
    /**
     * 
     * @param \Datetime $date
     * @param \Datetime $datetoAdd
     * @return type
     */
    private function getSumDates(\Datetime $date, \Datetime $datetoAdd) {
        $secs = $datetoAdd->format('U'); 
        $intervalFormat = 'PT'.$secs.'S';
        $result = $date->add(new \DateInterval($intervalFormat));
        return $result;
    }

}
