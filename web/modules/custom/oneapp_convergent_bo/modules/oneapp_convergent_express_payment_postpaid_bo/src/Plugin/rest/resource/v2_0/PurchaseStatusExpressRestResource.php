<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\oneapp_convergent_payment_gateway\Services\v2_0\TransactionsPaymentRestLogic;
use Drupal\oneapp_rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\oneapp\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   api_response_version = "v2_0",
 *   block_id = "oneapp_convergent_express_payment_postpaid_qr_v2_0_purchase_status_block",
 *   id = "oneapp_convergent_payment_express_v2_0_generate_purchase_status_rest_resource",
 *   label = @Translation("ONEAPP Convergent Payment express postpaid BO - Invoices Purchase Status Rest Resource v2_0"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/express/postpaid/{businessUnit}/invoices/{idType}/{id}/purchaseorders/{purchaseorderId}/status",
 *   },
 *  anonymous_token = TRUE
 * )
 */
class PurchaseStatusExpressRestResource extends ResourceBase {

  /**
   * @var \Drupal\oneapp_convergent_payment_gateway\Services\v2_0\UtilsService
   */
  protected $paymentUtils;

  /**
   * @var \Drupal\oneapp_convergent_payment_gateway_invoices\Services\v2_0\PaymentGatewayRestLogic
   */
  protected $paymentGateway;

  /**
   * @var \Drupal\oneapp_convergent_payment_gateway\Services\v2_0\TransactionsPaymentRestLogic
   */
  public $transactionService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->paymentUtils = $container->get('oneapp_convergent_payment_gateway.v2_0.utils_service');
    $instance->paymentGateway = $container->get('oneapp_convergent_payment_gateway.v2_0.payment_gateway_rest_logic');
    $instance->transactionService = $container->get('oneapp_convergent_payment_gateway.v2_0.transactions_payment_rest_logic');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function get($businessUnit, $idType, $id, $purchaseorderId, Request $request) {
    $this->init();
    $productType = 'invoices';
    try {
      $transactionId = $this->transactionService->decryptId($purchaseorderId);
      $transaction = $this->transactionService->getTransactionById($transactionId);
      if (!empty($transaction)) {
        $statusOrder['stateOrder'] = $transaction->stateOrder;
        if ($this->request->query->get('paymentMethod') == 'qr') {
          $this->configBlock = $this->blockConfigService->getDefaultConfigBlock('oneapp_convergent_express_payment_postpaid_qr_v2_0_purchase_status_block');
          $this->paymentGateway->setPaymentMethod('qr');
        }elseif ($this->request->query->get('paymentMethod') == 'creditCard'){
          $transaction->uuid = md5($this->request->query->get('email'));
          $this->configBlock = $this->blockConfigService->getDefaultConfigBlock('oneapp_convergent_express_payment_postpaid_credit_card_v2_0_purchase_status_block');
        }elseif ($this->request->query->get('paymentMethod') == 'tigoMoney') {
        $ax = $this->blockConfigService->getDefaultConfigBlock('oneapp_convergent_express_payment_postpaid_tm_v2_0_purchase_status_block');
        $this->configBlock = $ax;
        $this->paymentGateway->setPaymentMethod('tigoMoney');
        $transaction->uuid = md5('');
          
        }
        $this->paymentGateway->getVariablesIfConvergent($id, $businessUnit, $idType);
        $this->paymentGateway->setBusinessUnit($businessUnit);
        $this->paymentGateway->setConfig($this->configBlock);
        $this->transactionService->setConfig($this->configBlock);
        if ($this->request->query->get('force') == 'true') {
          $statusOrder = $this->updateTransaction($transaction, $businessUnit);
          $transaction = $this->transactionService->getTransactionById($transactionId);
        }
      }else {
        throw new \Exception(str_replace('@field', 'url', $this->configBlock["errors"]["empty"]["value"]));
      }
      if ($transaction->accountId != $id) {
        throw new \Exception(str_replace('@field', 'purchaseorderId', $this->configBlock["errors"]["valid"]["value"]));
      }
    }catch (\Exception $e) {
      $this->apiErrorResponse->getError()->set('message', $e->getMessage());
      throw new NotFoundHttpException($this->apiErrorResponse, $e);
    }
    
    //Seteamos los datos.
    $response_data = $this->paymentGateway->formatStatus($transaction, $businessUnit);
    $data = (object) unserialize($transaction->additionalData);
    $response_data["periods"]["value"] = $data->period;
    $response_data["periods"]["formattedValue"] = $data->period;
    $this->apiResponse->getData()->setAll($response_data);
    $additonalDataObject = \json_decode($transaction->additionalData);
    if (!isset($additonalDataObject)) {
      $additonalDataObject = (object) unserialize($transaction->additionalData);
    }
    if (isset($additonalDataObject->didEnrollment)) {
      $statusOrder['enrollmentMessage'] = TRUE;
    }
    if ($transaction->errorCode == null) {
      $transaction->errorCode = "";
    }
    $this->responseConfig($transaction->errorCode, $statusOrder);

    $response = new ModifiedResourceResponse($this->apiResponse);
    $cookie = new Cookie("SESSION", time());
    $response->headers->setCookie($cookie);

    return $response;
  }

  /**
   * Returns config data. (Optional)
   *
   * @param string $errorCode
   *   Transaction info.
   * @param array $additional_data
   *   Additional data.
   */
  public function responseConfig(string $errorCode, array $additional_data = []) {
    $message = [];
    $additional_data["stateOrder"] = ($additional_data["stateOrder"] == "INITIALIZED") ? 'payment_non_complete' : $additional_data["stateOrder"];
    $messageKey = isset($additional_data["stateOrder"]) ? strtolower($additional_data["stateOrder"]) : 'payment_non_complete';
    $messageKey = isset($additional_data["enrollmentMessage"]) ? "enrollments_complete" : $messageKey;
    if (strtoupper($messageKey) == "PAYMENT_COMPLETE") {
      $message['title'] = '';
      $message['body'] = '';
    }
    else {
      $message['title'] = isset($this->configBlock["messages"][$messageKey]["title"]) ? $this->configBlock["messages"][$messageKey]["title"] : '';
      $message['body'] = isset($this->configBlock["messages"][$messageKey]["body"]) ? $this->configBlock["messages"][$messageKey]["body"] : '';
      if (isset($this->configBlock["messages"][$messageKey]["codeMapping"]) && strlen($this->configBlock["messages"][$messageKey]["codeMapping"]) != 0) {
        $this->paymentGateway = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.utils_service');
        $mappedMessage = $this->paymentGateway->changeAllowedValuesToArray($this->configBlock["messages"][$messageKey]["codeMapping"], $errorCode);
        if ($mappedMessage) {
          $message['body'] = $mappedMessage;
        }
      }
    }

    $this->configBlock["actions"]["home"]["show"] = $this->configBlock["actions"]["home"]["show"] ? TRUE : FALSE;

    if(( $additional_data['stateOrder'] == 'ORDER_IN_PROGRESS' || $additional_data['stateOrder'] == 'PAYMENT_NON_COMPLETE' )
      && isset($this->configBlock['errors']['errorMapped']['value']) && !empty($this->configBlock['errors']['errorMapped']['value'])) {
      $errors = explode("\r\n", $this->configBlock['errors']['errorMapped']['value']);
      foreach($errors as $key => $row) {
        $errorArray = explode('|', $row);
        if($errorCode == $errorArray[0]) {
          $message['body'] = $errorArray[1];
        }
      }
    }

    $this->configBlock["actions"]["details"]["show"] = (bool) $this->configBlock["actions"]["details"]["show"];
    $this->apiResponse
      ->getConfig()
      ->set('status', $additional_data)
      ->set('actions', $this->configBlock["actions"])
      ->set('message', $message)
      ->set('errorCode', $errorCode);
  }

  /**
   * Actualizando los datos de la base de datos
   */
  public function updateTransaction($transaction, $businessUnit) {
    $orderData = new \stdClass();
    $configApp = (object) \Drupal::config("oneapp.payment_gateway.{$businessUnit}_{$transaction->productType}.config")->get('configuration_app');
    $query['query'] = [];
    if (isset($this->config["configs"]["sendPayment"]["value"]) && $this->config["configs"]["sendPayment"]["value"]) {
      $query['query'] = [
        'forceUpdate' => 'true',
      ];
    }
    $params['orderId'] = $transaction->orderId;
    $params['uuid'] = str_replace("-", "", $transaction->uuid);
    $params['apiHost'] = $configApp->setting_app_payment['api_path'];
    $resporse['stateOrder'] = $transaction->stateOrder;
    if (!empty($params['orderId'])) {
      $aws_service = $configApp->setting_app_payment["aws_service"] ?? 'payment';
      $updateTransaction = TRUE;
      try {
        /** @var \Drupal\aws_service\Services\v2_0\AwsApiManager */
        $aws_manager = \Drupal::service('aws.manager');
        $orderData = $aws_manager->callAwsEndpoint('oneapp_convergent_payment_gateway_v2_0_orders_status_endpoint', $aws_service, [], $params, $query['query'], []);
      }
      catch (\Exception $e) {
        if ($e->getCode() == 500) {
          // TODO: Code is only while PG fix the problem 500 on multipayments.
          $orderData->body->paymentApproved = TRUE;
          $transaction->stateOrder = 'PAYMENT_COMPLETE';
          $updateTransaction = FALSE;
        }
        else {
          throw $e;
        }
      }

      if ($orderData->body && property_exists($orderData->body, 'paymentApproved')) {
        if ($transaction->stateOrder == 'ORDER_IN_PROGRESS' || $transaction->stateOrder == 'PAYMENT_COMPLETE') {
          $resporse['paymentApproved'] = $orderData->body->paymentApproved ?? '';
          if (!$orderData->body->paymentApproved) {
            $resporse['stateOrder'] = 'PAYMENT_NON_COMPLETE';
          }else if (property_exists($orderData->body, 'fulfillmentSucceeded')) {
            $resporse['fulfillmentSucceeded'] = $orderData->body->fulfillmentSucceeded;
            if ($orderData->body->fulfillmentSucceeded) {
              $resporse['stateOrder'] = 'FULFILLMENT_COMPLETE';
            }else {
              $resporse['stateOrder'] = 'FULFILLMENT_NON_COMPLETE';
            }
          }else {
            $resporse['stateOrder'] = 'ORDER_IN_PROGRESS';
          }

          $fieldsUpdate = [];
          if ($updateTransaction) {
            $fieldsUpdate['stateOrder'] = $resporse['stateOrder'];
            $fieldsUpdate['accountNumber'] = !empty($transaction->accountNumber) ? $transaction->accountNumber : $orderData->body->accountNumber;
            $fieldsUpdate['accountType'] = !empty($transaction->accountType) ? $transaction->accountType : $orderData->body->accountType;
            $fieldsUpdate['accountId'] = !empty($transaction->accountId) ? $transaction->accountId : $orderData->body->phoneNumber;
            $fieldsUpdate['purchaseOrderId'] = !empty($transaction->purchaseOrderId) ? $transaction->purchaseOrderId : $orderData->body->purchaseOrderId;
            $fieldsUpdate['orderId'] = !empty($transaction->orderId) ? $transaction->orderId : $orderData->body->orderId;
            $fieldsUpdate['numberReference'] = $orderData->body->paymentProcessorTransactionId ?? $transaction->numberReference;
            $fieldsUpdate['errorCode'] = $orderData->body->paymentRejectReason ?? '';
            $fieldsUpdate['numberAccess'] = $orderData->body->paymentAuthorizationCode ?? $transaction->numberAccess;
            if (isset($orderData->body->paymentApproved)) {
              $fieldsUpdate['paymentApproved'] = $orderData->body->paymentApproved ? 1 : 0;
            }
            if (isset($orderData->body->fulfillmentSucceeded)) $fieldsUpdate['fulfillmentSucceeded'] = $orderData->body->fulfillmentSucceeded ? 1 : 0;
          }
          else {
            $fieldsUpdate['stateOrder'] = $resporse['stateOrder'];
          }
          $this->updateDataTransaction($transaction->id, $fieldsUpdate);
        }
      }
    }

    return $resporse;
  }

  /**
   * Obtiene los datos necesarios para realizar el pago desde una db table
   * @param string $idTransaction
   * @param $fields
   */
  public function updateDataTransaction($idTransaction, $fields = []) {
    try {
      $connection = \Drupal::database();
      $query = $connection
        ->update('oneapp_payment_gateway' . $this->suffix)
        ->fields($fields);
      $query->condition('id', $idTransaction, '=');

      $query->execute();
    }
    catch (\Exception $e) {
      $fieldsLog = [
        'operation' => TransactionsPaymentRestLogic::ERROR_UPDATED_ORDER,
        'codeStatus' => 400,
        'message' => "Transaction not Updated",
        'description' => "Message: " . $e->getMessage() . "\nFields: " . json_encode($fields, JSON_PRETTY_PRINT),
        'type' => '',
      ];
      $this->addLog($fieldsLog);
      throw new \Exception(t("Los datos de la orden son incorrectos"));
    }

  }


}
