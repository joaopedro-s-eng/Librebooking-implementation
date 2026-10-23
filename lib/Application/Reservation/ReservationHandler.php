<?php

require_once(ROOT_DIR . 'lib/Config/namespace.php');
require_once(ROOT_DIR . 'lib/Server/namespace.php');
require_once(ROOT_DIR . 'lib/Common/namespace.php');
require_once(ROOT_DIR . 'Domain/namespace.php');
require_once(ROOT_DIR . 'Domain/Access/namespace.php');
require_once(ROOT_DIR . 'lib/Application/Reservation/namespace.php');
require_once(ROOT_DIR . 'lib/Application/Reservation/Persistence/namespace.php');
require_once(ROOT_DIR . 'lib/Application/Reservation/Validation/namespace.php');
require_once(ROOT_DIR . 'lib/Application/Reservation/Notification/namespace.php');

interface IReservationHandler
{
    /**
     * @param ReservationSeries|ExistingReservationSeries $reservationSeries
     * @param IReservationSaveResultsView $view
     * @return bool if the reservation was handled or not
     */
    public function Handle($reservationSeries, IReservationSaveResultsView $view);
}

class ReservationHandler implements IReservationHandler
{
    /**
     * @var IReservationPersistenceService
     */
    private $persistenceService;

    /**
     * @var IReservationValidationService
     */
    private $validationService;

    /**
     * @var IReservationNotificationService
     */
    private $notificationService;

    /**
     * @var IReservationRetryOptions
     */
    private $retryOptions;

    public function __construct(
        IReservationPersistenceService $persistenceService,
        IReservationValidationService $validationService,
        IReservationNotificationService $notificationService,
        IReservationRetryOptions $retryOptions
    ) {
        $this->persistenceService = $persistenceService;
        $this->validationService = $validationService;
        $this->notificationService = $notificationService;
        $this->retryOptions = $retryOptions;
    }

    /**
     * @static
     * @param $reservationAction string|ReservationAction
     * @param $persistenceService null|IReservationPersistenceService
     * @param UserSession $session
     * @return IReservationHandler
     */
    public static function Create($reservationAction, $persistenceService, UserSession $session)
    {
        if (!isset($persistenceService)) {
            $persistenceFactory = new ReservationPersistenceFactory();
            $persistenceService = $persistenceFactory->Create($reservationAction);
        }

        $validationFactory = new ReservationValidationFactory();
        $validationService = $validationFactory->Create($reservationAction, $session);

        $notificationFactory = new ReservationNotificationFactory();
        $notificationService = $notificationFactory->Create($reservationAction, $session);

        $scheduleRepository = new ScheduleRepository();
        $retryOptions = new ReservationRetryOptions(new ReservationConflictIdentifier(new ResourceAvailability(new ReservationViewRepository())), $scheduleRepository);

        return new ReservationHandler($persistenceService, $validationService, $notificationService, $retryOptions);
    }

    /**
     * @param ReservationSeries|ExistingReservationSeries $reservationSeries
     * @param IReservationSaveResultsView $view
     * @return bool if the reservation was handled or not
     * @throws Exception
     */

        public function Handle($reservationSeries, IReservationSaveResultsView $view)
        {

            $lockHandles = [];

            try {
                // 1. Coleta e ordena os IDs dos recursos para evitar deadlocks
                if (method_exists($reservationSeries, 'AllResources')) {
                    $resourceIds = [];
                    foreach ($reservationSeries->AllResources() as $resource)
  {
                        $resourceIds[] = $resource->GetId();
                    }
                    sort($resourceIds); // Garante que múltiplas requisições travem na mesma ordem

                    $lockDir = sys_get_temp_dir() . '/librebooking_locks';
                    if (!is_dir($lockDir)) {
                        @mkdir($lockDir, 0777, true);
                    }

                    // 2. Adquire trava exclusiva no nível do sistema operacional para cada recurso
                    foreach ($resourceIds as $resId) {
                        $fp = fopen($lockDir . '/resource_' . $resId . '.lock', 'c');
                        if ($fp) {
                            flock($fp, LOCK_EX); // Aguarda na fila se outro processo estiver reservando este recurso
                            $lockHandles[] = $fp;
                        }
                    }
                }

                // 3. Validação segura: ninguém consegue inserir enquanto este bloco executa
                $this->retryOptions->AdjustReservation($reservationSeries, $view->GetRetryParameters());
                $validationResult = $this->validationService->Validate($reservationSeries, $view->GetRetryParameters());
                $result = $validationResult->CanBeSaved();

                if ($validationResult->CanBeSaved()) {
                    try {
                        $this->persistenceService->Persist($reservationSeries);
                    } catch (Exception $ex) {
                        Log::Error('Error saving reservation: %s', $ex);
                        throw($ex);
                    }

                    $this->notificationService->Notify($reservationSeries);

                    $view->SetSaveSuccessfulMessage($result);
                } else {
                    $view->SetSaveSuccessfulMessage($result);
                    $view->SetErrors($validationResult->GetErrors());

                    $view->SetCanBeRetried($validationResult->CanBeRetried());
                    $view->SetRetryParameters($validationResult->GetRetryParameters());
                    $view->SetRetryMessages($validationResult->GetRetryMessages());
                    $view->SetCanJoinWaitList($validationResult->CanJoinWaitList() && Configuration::Instance()->GetKey(
                        ConfigKeys::RESERVATION_ALLOW_WAITLIST,
                        new BooleanConverter()
                    ));
                }

                $view->SetWarnings($validationResult->GetWarnings());

                return $result;

            } finally {
                // 4. Libera todas as travas com segurança (mesmo se ocorrer erro ou exceção)
                while (!empty($lockHandles)) {
                    $fp = array_pop($lockHandles);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                }
            }
        }


//     public function Handle($reservationSeries, IReservationSaveResultsView $view)
//     {
//         $this->retryOptions->AdjustReservation($reservationSeries, $view->GetRetryParameters());
//         $validationResult = $this->validationService->Validate($reservationSeries, $view->GetRetryParameters());
//         $result = $validationResult->CanBeSaved();

//         if ($validationResult->CanBeSaved()) {
//             try {
//                 $this->persistenceService->Persist($reservationSeries);
//             } catch (Exception $ex) {
//                 Log::Error('Error saving reservation: %s', $ex);
//                 throw($ex);
//             }

//             $this->notificationService->Notify($reservationSeries);

//             $view->SetSaveSuccessfulMessage($result);
//         } else {
//             $view->SetSaveSuccessfulMessage($result);
//             $view->SetErrors($validationResult->GetErrors());

//             $view->SetCanBeRetried($validationResult->CanBeRetried());
//             $view->SetRetryParameters($validationResult->GetRetryParameters());
//             $view->SetRetryMessages($validationResult->GetRetryMessages());
//             $view->SetCanJoinWaitList($validationResult->CanJoinWaitList() && Configuration::Instance()->GetKey(
//                 ConfigKeys::RESERVATION_ALLOW_WAITLIST,
//                 new BooleanConverter()
//             ));
//         }

//         $view->SetWarnings($validationResult->GetWarnings());

//         return $result;
//     }













}
