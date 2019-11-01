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
        $extraData = [];
        
        // Determine the current step.
        $step = FlightReservationForm::STEP_1;
        if (isset($this->sessionContainer->step)) {
            $step = $this->sessionContainer->step;
        }
        // Ensure the step is correct (between 1 and 2).
        if ($step < FlightReservationForm::STEP_1 || $step > FlightReservationForm::STEP_2){
            $step = FlightReservationForm::STEP_1;   
        }
        
        if ($step == FlightReservationForm::STEP_1) {
            // Init user choices.
            $this->sessionContainer->userChoices = [];
            
        } else if($step == FlightReservationForm::STEP_2){
            $formDataStep1 =  $this->sessionContainer->userChoices["step".FlightReservationForm::STEP_1];        
            $departureDate = $formDataStep1['departure-date'] ?? ''; 
            $returnDate = $formDataStep1['return-date'] ?? '';   // if !='' is round-trip    
            $departureCode = $formDataStep1['from'] ?? '';       
            $destinationCode = $formDataStep1['to'] ?? '';      
            $extraData = $this->getAvailableFlightOnDates($departureCode, $destinationCode, $departureDate, $returnDate);
//print_r($extraData);
//die("asd1");
        }
        
        $form = new FlightReservationForm($step, $extraData);

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
                $this->sessionContainer->userChoices["step$step"] = $data;
                // Increase step
                $step ++;
                $this->sessionContainer->step = $step;
                // If we completed all 3 steps, redirect to Review page.
                if ($step > FlightReservationForm::STEP_2) {
                    return $this->redirect()->toRoute('flightreservation', ['action' => 'review']);
                }
                // Go to the next step.
                return $this->redirect()->toRoute('flightreservation');
            }
        }

        $viewModel = new ViewModel(['form' => $form]);
        $viewModel->setTemplate("application/flightreservation/step$step");

        return $viewModel;
    }

    /**
     * The "review" action shows a page allowing to review data entered on previous
     * three steps.
     */
    public function reviewAction() {
        // Validate session data.
        if (!isset($this->sessionContainer->step) ||
                $this->sessionContainer->step <= FlightReservationForm::STEP_2 ||
                !isset($this->sessionContainer->userChoices)) {
            throw new \Exception('Sorry, the data is not available for review yet');
        }

        // Retrieve user choices from session.
        $userChoices = $this->sessionContainer->userChoices;

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
    
    private function getFlightSchedulesForDatepickers($fromCode, $toCode, $return)
    {
        $flightSchedules = $this->getFlightSchedules($fromCode, $toCode, $return);
        $result = $this->formatFlightSchedulesForDatepickers($flightSchedules);        
        return $result;
    }
    
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
  
    
}
