<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_bo\Services\v2_0\UtilsCallbackRestLogicBo;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CallbackInvoicesRestLogicBo.
 */
class CallbackInvoicesRestLogicBo extends UtilsCallbackRestLogicBo {

  /**
   * {@inheritdoc}
   */
  public function __construct($transactions) {
    parent::__construct($transactions);
  }

  /**
   * {@inheritdoc}
   */
  public function apiPaymentProcessComplete($businessUnit, $type ,$purchaseOrderId, $typePage, $params, $headers) {

    parent::setProductType('invoices');
    parent::setBusinessUnit($businessUnit);
    parent::setTypePage($typePage);
    parent::loadTransactions($purchaseOrderId);
    parent::setParamas($params);
    parent::setHeaders($headers);

    // Validate url.
    $this->isValidUrl();

    // Validate transaction status.
    $this->isValidStateOrder();

    // Validate Duplicate Callback.
    $this->isDuplicateCallback();

    return $this->executeProcesses($type);
  }

  /**
   * Execute processes.
   *
   * @return array|void
   */
  public function executeProcesses($type) {

    if ($type == "qr") {
      $credit_card_type = 'QR Simple';
    }elseif ($type == "creditCard") {
      $credit_card_type = 'Tarjeta';
    }elseif ($type == "tigomoney") {
      $credit_card_type = 'Tigomoney';
    }

    if (is_object($this->dataTransaction) && property_exists($this->dataTransaction, 'cardBrand')) {
      if ($credit_card_type == 'QR Simple') {
        $credit_card_type = empty($this->dataTransaction->cardBrand) ? 'QR Simple' : $this->dataTransaction->cardBrand ;
      }elseif ($credit_card_type == 'Tarjeta') {
        $credit_card_type = empty($this->dataTransaction->cardBrand) ? 'Tarjeta' : $this->dataTransaction->cardBrand ;
      }elseif ($credit_card_type == 'Tigomoney') {
        $credit_card_type = empty($this->dataTransaction->cardBrand) ? 'Tigomoney' : $this->dataTransaction->cardBrand ;
      }
      $this->dataTransaction->cardBrand = $credit_card_type;
    }

    $fields = [
      "maskedAccountId" => $this->params['paymentInstrument']['maskedAccountId'] ?? $this->productType,
      "cardBrand" => $credit_card_type,
      'numberReference' => $this->params['paymentProcessorTransactionId'],
      'numberAccess' => $this->params['authorizationCode'],
      'errorCode' => $this->params['paymentResultCode'],
    ];
    if (is_object($this->dataTransaction) && property_exists($this->dataTransaction, 'additionalData')) {
      $this->params['accountId'] = $this->dataTransaction->accountId;
    }
    if ($this->typePage == "payment") {
      return $this->executePaymentProcessesPayment($fields);
    }
    if ($this->typePage == "fulfillment") {
      return $this->executeFulfillmentProcessesPayment($fields, $type);
    }
  }

  /**
   * Execute fulfillment processes .
   *
   * @param $fields
   *
   * @return array
   */
  public function executeFulfillmentProcessesPayment($fields, $type) {
    $this->getReversalFulfillment();
    $fulfillment_status = $this->params['fulfillmentSucceded'] ?? FALSE;
    $fields['fulfillmentSucceeded'] = $fulfillment_status ? 1 : 0;

    $complete = $fulfillment_status ? "COMPLETE" : "NON_COMPLETE";
    $this->changeStatusOrder($complete, $fields);

    if ($fulfillment_status) {
      \Drupal::service('module_handler')->invokeAll('succesfull_payment_' . $this->dataTransaction->productType, [$this->params]);
    }

    $aditional_data = [];
    if (!empty($this->getPurchaseOrderId())) {
      $aditional_data = $this->transactions->getTransactionById($this->getPurchaseOrderId());
    }

    if (!empty($this->params) && !empty($aditional_data) && !empty($this->businessUnit && !empty($this->productType))) {
      $this->pushNotifications($this->params, $aditional_data, $this->businessUnit, $this->productType);
    }

    //envio notificacion zendesks para qr y tarjeta de credrito
    if (!$this->params["fulfillmentSucceded"] && $type == "qr" ||
      !$this->params["fulfillmentSucceded"] && $type == "creditCard") {
      if (!empty($this->params["multipleAccountsDetail"])) {
        $this->evaluateMultiplePayment();
      }
      else {
        $this->evaluateOnePayment();
      }
    }

    $status_mail = $fulfillment_status ? 'success' : 'fail';
    $this->sendMail($status_mail);

    return [
      "code" => Response::HTTP_NO_CONTENT,
      "message" => "Fulfilment" . ($this->params['fulfillmentSucceded'] ? "" : " not") . " succeded.",
    ];
  }

  /**
   * @param $complete
   * @param $fields
   *
   * @return void
   */
  public function changeStatusOrder($complete, $fields = []) {
    $fields['stateOrder'] = strtoupper($this->typePage) . "_" . $complete;

    if (isset($this->params['authorizationCode'])) {
      $fields['numberAccess'] = $this->params['authorizationCode'];
    }

    $this->transactions->updateDataTransaction($this->getPurchaseOrderId(), $fields);

    $fields_log = [
      'purchaseOrderId' => $this->getPurchaseOrderId(),
      'message' => "{$this->typePage} " . str_replace('_', ' ',
          strtolower($complete)),
      'codeStatus' => Response::HTTP_NO_CONTENT,
      'operation' => $this->transactions::CALLBACK_TRANSACTION,
      'description' => "Message: {$this->getUrlService()} \nRequest Body: " . json_encode($this->removeParam($this->params),
          JSON_PRETTY_PRINT),
      'type' => $this->productType,
    ];

    $this->transactions->addLog($fields_log);
  }
}
