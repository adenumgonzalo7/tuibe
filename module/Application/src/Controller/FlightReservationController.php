<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Form\FlightReservationForm;
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
        }

        $form = new FlightReservationForm($step);

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

        $viewModel = new ViewModel([
            'form' => $form
        ]);
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

        return new ViewModel([
            'userChoices' => $userChoices
        ]);
    }

    public function ajaxAction(){
        $tmp = $this->params()->fromPost();
       
        //$view = new ViewModel([
        //    'menuId' => $tmp,
        //]);
        //print_r($tmp);
        //die($tmp);
        //$view->setTemplate('layout/layout');
        //$view->setTerminal(true);
        //return $view;
        $a = [$tmp];
        return new JsonModel($a); 
    }
    
}
