<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_invoices\Services\v2_0\PaymentGatewayRestLogic;

/**
 * Class that have the logic to connect to Payment Gateway from Express Payment.
 */
class PaymentGatewayExpressInvoicesRestLogic extends PaymentGatewayRestLogic {

  /**
   * {@inheritDoc}
   */
  public function start($id, $id_type, $business_unit, $product_type, $params) {
    $this->isB2b($id, $id_type);

    /* If the line is convergent, the business unit must be changed
    from mobile to home, since the APIs only bring information for home
    accounts and their debt information is the same in home and mobile.*/
    $billing_account_id = $id;
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    $is_convergent = $this->isConvergent($billing_account_id);
    if($is_convergent) {
      $id_type = "subscribers";
      $business_unit = "home";
    }
    $balance = $this->utilsPayment->getBalance($billing_account_id, $id_type, $business_unit, $params);
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->tokenAuthorization->setId($id);
    }

    if (isset($params["isPartialPayment"]) && !$params["isPartialPayment"]) {
      $params['amount'] = $balance["dueAmount"];
    }
    if ($balance['dueAmount'] != $params['amount'] && !$params['isPartialPayment']) {
      throw new \Exception("El monto es incorrecto");
    }
    if ($balance["dueAmount"] <= 0 && !$params['isPartialPayment']) {
      throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
    }
    if (!$params["isMultipay"]) {
      if ($params["period"] != $balance["pendingInvoices"][0]->period) {
        throw new \Exception("el periodo a pagar no es valido");
      }else{
        $params['amount'] = $balance["pendingInvoices"][0]->dueAmount;
      }
    }

    if ($params["isMultipay"] && is_array($params["period"])) {
      $totalAmount = [];
      foreach ($balance["pendingInvoices"] as $key => $invoices) {
        if (in_array($balance["pendingInvoices"][$key]->period, $params["period"])) {
          $totalAmount[] = $invoices->dueAmount;
        }
      }

      $params['amount'] = array_sum($totalAmount);
    }

    $account_number_invoice_payment = $balance["billingAcountId"];
    $fields = [
      'uuid' => $this->tokenAuthorization->getMailUuid($id),
      'accountId' => $id,
      'accountNumber' => !empty($balance["accountNumber"]) ? $balance["accountNumber"] : $id,
      'accountType' => $business_unit,
      'productType' => $product_type,
      'amount' => $params["amount"],
      'isPartialPayment' => $params['isPartialPayment'] ? 1 : 0,
      'numberReference' => 0,
      'accessType' => $this->tokenAuthorization->getAccessType(),
    ];

    $utils_express_bo = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.util_service');
    if (isset($balance['additionalData']) && (!empty($balance['additionalData']))) {
      if (isset($balance['period'])) {
        if ($params["isMultipay"]) {
          $result = [];
          foreach ($balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"] as $key => $invoices) {
            if ($params["period"][$key]) {
              $result[] = $invoices["productReference"];
            }
          }
          $balance['additionalData']['period'] = implode(",", $result);
          if($business_unit == "mobile") {
            $balance["additionalData"]["accountNumber"] = $id;
          }
        } elseif ($business_unit == "home") {
          $balance['additionalData']['period'] = $params["period"];
        } elseif ($business_unit == "mobile" && !$params["isMultipay"]) {
          $balance["additionalData"]["accountNumber"] = $id;
          $balance['additionalData']['period'] = (isset($balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"])
            ? $balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"][0]["productReference"]
            : $balance["additionalData"]["fieldsForPaymentBody"]["productReference"]) ;
        }
      }
      $balance['additionalData']['period'] = $utils_express_bo->getFormatPeriods($balance['additionalData']['period']);
      $balance['additionalData']['accountNumberInvoicePayments'] = $account_number_invoice_payment;
      $fields['additionalData'] = serialize($balance['additionalData']);
    }
    else {
      $additional_data = new \Stdclass();
      $additional_data->period = $balance['period'];
      $additional_data->paymentMethod = $params['paymentMethodName'] ?? 'creditCard';
      $additional_data->accountNumberInvoicePayments = $account_number_invoice_payment;
      $fields['additionalData'] = serialize($additional_data);
    }
    $transaction_id = $this->transactions->initTransaction($fields, $product_type);
    $finger_print = $this->utilsPayment->getAttachments($id, $business_unit, $product_type, $transaction_id);
    $purchaseorder_id = $this->transactions->encryptId($transaction_id);
    if($params["isMultipay"]) {
      $results = [];
      $periods = "";
      foreach ($balance["pendingInvoices"] as $key => $invoices) {
        if (in_array($balance["pendingInvoices"][$key]->period, $params["period"])) {
          $results[] = $invoices->invoiceId;
          $balance["invoiceId"] = implode(", ", $results);
          $periods .= explode(",", $balance["period"])[$key];
        }
      }
    }else{
      if($balance["pendingInvoices"][0]->period == $params["period"]){
        $balance["invoiceId"] = $balance["pendingInvoices"][0]->invoiceId;
        $periods = explode(",", $balance["period"])[0];
      }
    }
    $response = [
      'fingerPrint' => $finger_print,
      'purchaseorderId' => $purchaseorder_id,
      'dueAmount' => $params["amount"],
      'invoiceId' => $balance["invoiceId"],
      'accountNumber' => !empty($balance["accountNumber"]) ? $balance["accountNumber"] : $id,
      'accountId' => $id,
      'isMultipay' => $params["isMultipay"],
      'productType' => 'Pago de factura',
    ];
    if (isset($balance['period'])) {
      $response['period'] = ($periods ?? $balance['period']);
    }
    if ($params["isMultipay"]) {
      $config_app = (object) $this->tokenAuthorization->getApplicationSettings('configuration_app');
      $response['applicationName'] = $config_app->express_payment['applicationNameMultipay'];
    }
    return $response;

  }

  /**
   * Overwrites ONEAPP function for express.
   *
   * @param string $id
   *   The id of the account, phone number for mobile, etc.
   * @param string $id_type
   *   The kind of the id, suscribers or contract.
   *
   * @return bool
   *   If the account is b2b or not.
   */
  public function isB2b($id, $id_type = 'subscribers') {
    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('express_auth')) {
      return NULL;
    }
    else {
      return $this->accountsService->isB2B($id, $id_type);
    }
  }

  /**
   * Get config response for invoices.
   *
   * @param mixed $params
   *   Params of the request.
   * @param mixed $response_data
   *   The data of the response.
   * @param string $business_unit
   *   The category of the phone line, e.g home or mobile.
   * @param string $product_type
   *   The type of product to pay: invoices, packets, etc.
   * @param mixed $configBlock
   * @return array
   *   Data to show in the config section.
   */


  public function getConfig($params, $response_data, $business_unit, $product_type,$configBlock) {
    $labelsNewForm= $this->buildGetInputsLabelsForm($configBlock['labelBillingForm']);
    $newForm= $this->buildGetInputsForm($configBlock['billingForm']);

   $newForm=[
      'billingForm'=>[
        'labels'=>$labelsNewForm,
        'fullName'=>$newForm['billingForm']['fullName'],
        'nit'=>$newForm['billingForm']['nit'],
        'mail'=>$newForm['billingForm']['mail'],
      ]
    ];

    array_merge($labelsNewForm,$newForm);
    $billing_form = $this->utilsPayment->getBillingDataForm($product_type, $business_unit,
    $this->tokenAuthorization->getMailUuid($params['msisdn']));
    $new_card_form = $this->utilsPayment->getFormPayment($product_type, $response_data);
    $this->setCustomConfigErrorMesages($new_card_form);
    if ($billing_form) {
      $forms = [$new_card_form, $billing_form];
    }
    else {
      $forms = $new_card_form;
    }

    $this->config['termsAndConditions'][$business_unit]['show'] =
    (bool) $this->config['termsAndConditions'][$business_unit]['show'];
    $cards = $this->utilsPayment->getCards($business_unit, $params);
    unset($response_data["invoiceId"]);
    unset($response_data["accountNumber"]);
    unset($response_data["accountId"]);
    unset($response_data["isMultipay"]);
    unset($response_data["product_type"]);
    unset($response_data["period"]);
    unset($response_data["applicationName"]);
    unset($response_data["dueAmount"]);

    $config = [
      'forms' => array_merge($new_card_form,$newForm),
      'params' => $response_data,
      'cards' => $cards,
      'actions' => $this->config['actions'],
      'errors' => $this->config['errors'],
      'views_messages' => $this->config['views_messages'],
      'pop_up' => $this->config['pop_up_card_payment'],
      'termsAndConditions' => $this->config['termsAndConditions'][$business_unit],
      'notificationAttempts' => $this->config['notificationAttempts'],
    ];

    if ($this->tokenAuthorization->isHe()) {
      $config['message'] = $this->config['he_otp']['flow']['message'];
    }

    foreach ($config['actions'] as $key => $action) {
      $config['actions'][$key]['show'] = (bool) $action['show'];
    }
    foreach ($config['errors'] as $key => $error) {
      $config['errors'][$key]['show'] = (bool) $error['show'];
    }
    foreach ($config['views_messages'] as $key => $error) {
      $config['views_messages'][$key]['show'] = (bool) $error['show'];
    }

    return $config;
  }
 /**
   * Build and get format for the new form (billingForm).
   *
   * @param array $config
   *   Array where configuration structure is stored.
   */
  protected function buildGetInputsForm($configBlock) {

    $size_error_validation = [
      '#size' => 40,
    ];
    foreach ($configBlock['billingForm'] as $id => $entity) {
      $item = [];

      $item['label'] = $entity['label'];
      $item['value'] = $entity['value'];
      $item['placeholder'] = $entity['placeholder'];
      $item['validations'] = ['required' => (bool) $entity['required'],'type_error_alert_validation' => $entity['error_alert_validation'],'type_error_alert_required' => $entity['error_alert_required'],'minLength' => $entity['minLength'], 'maxLength' => $entity['maxLength'], 'pattern' => $entity['pattern']];
      $item['description'] = $entity['description'];
      $item['error_message'] = ['error_message_required' => $entity['error_message_required'], 'error_message_validation' => $entity['error_message_validation']];
      $item['type'] = $entity['type'];
      $item['show'] = (bool) $entity['show'];

      $config['billingForm'][$id] = $item;
    }

    return $config;
  }
/**
   * Build and get format for the new form (buildGetInputsLabelsForm).
   *
   * @param array $config
   *   Array where configuration structure is stored.
   */
  protected function buildGetInputsLabelsForm($configBlock) {
    $size_error_validation = [
      '#size' => 40,
    ];
    foreach ($configBlock as $id => $entity) {
      $item = [];
      $item['label'] = $entity['label'];
      $item['value'] = $entity['value'];
      $item['show'] = (bool) $entity['show'];
      $config[$id] = $item;
    }
    return $config;
  }

  /**
   * Set error message from the config intro the form.
   *
   * @param array $card_form
   *   Form with the fields to show.
   */
  private function setCustomConfigErrorMesages(array &$card_form) {
    $fields_errors = [
      'numberCard' => [
        'error_number_card_required',
        'error_number_card_validation',
      ],
      'expirationDate' => [
        'error_expiration_date_required',
        'error_expiration_date_validation',
      ],
      'cvv' => [
        'error_cvv_required',
        'error_cvv_validation',
      ],
      'nameCard' => [
        'error_cardholder_required',
        'error_cardholder_validation',
      ],
    ];
    foreach ($card_form["newCardForm"] as $title => &$field) {
      if (array_key_exists($title, $fields_errors)) {
        foreach ($fields_errors[$title] as $value) {
          $message = $this->setErrorType($value);
          if ($message != NULL) {
            $field['validations']['errors'][$message] = $this->config['errors'][$value];
          }
        }
      }
    }
  }

  /**
   * Find the type of error (required or validation) using the error name.
   *
   * @param string $error_name
   *   The error name.
   *
   * @return mixed
   *   The type of the error or NULL if there is no error type.
   */
  private function setErrorType($error_name) {
    $type_errors = [
      'error_require' => [
        'error_number_card_required',
        'error_expiration_date_required',
        'error_cvv_required',
        'error_cardholder_required',
      ],
      'error_validation' => [
        'error_number_card_validation',
        'error_expiration_date_validation',
        'error_cvv_validation',
        'error_cardholder_validation',
      ],
    ];
    foreach ($type_errors as $type => $errors) {
      $result = array_search($error_name, $errors);
      if (is_bool($result) && !$result) {
        continue;
      }
      return $type;
    }
    return NULL;
  }

  public function generateOrderId($business_unit, $product_type, $id_type, $id, $purchase_order_id) {
    $encryption = \Drupal::service('oneapp_convergent_express_payment.v2_0.encryption_rest_logic');
    foreach ($this->params as &$param) {
      if (!is_array($param) && is_string($param)) {
        $param = $encryption->decryptValue($param);
      }
    }
    $this->params['email'] = strtolower($this->params["billingData"]["email"]);
    $this->params['uuid'] = $this->tokenAuthorization->getMailUuid($this->params['email']);

    if (!$this->tokenAuthorization->isHe()) {
      if ($this->tokenAuthorization->getGivenNameUser() != NULL ||
      $this->tokenAuthorization->getFirstNameUser() != NULL) {
        $this->params['customerNameToken'] = $this->tokenAuthorization->getGivenNameUser() . " " . $this->tokenAuthorization->getFirstNameUser();
      }
    }

    $configApp = $this->tokenAuthorization->getApplicationSettings('configuration_app');
    $this->params['apiHost'] = $configApp["express_payment"]["api_path"];
    if (isset($configApp["express_payment"]["aws_service"])) {
      $this->params['aws_service'] = $configApp["express_payment"]["aws_service"];
    }
    $decrypt_purchase_order_id = $this->transactions->decryptId($purchase_order_id);
    $data_transaction = $this->transactions->getTransactionById($decrypt_purchase_order_id);
    $additionalData = $this->setAdditionalData($data_transaction);
    $isMultipay = $this->params['isMultipay'];
    $additional_data_for_payment_body = (isset($additionalData['fieldsForPaymentBody'])) ?
    $additionalData['fieldsForPaymentBody'] : [];
    if ($isMultipay) {
      $additional_data_for_payment_body['applicationName'] = $configApp["express_payment"]['applicationNameMultipay'];
    }
    $config_fac = \Drupal::config("oneapp.payment_gateway." . $business_unit . "_" . $product_type . ".config")->getRawData();
    if ($config_fac['billing_form']['overwrite_data']) {
      $this->validateDataOverWrite($addData, $config_fac);
    }
    $body = $this->utilsPayment
      ->getBodyPayment($business_unit, $product_type, $id_type, $id, $purchase_order_id, $this->params, $additional_data_for_payment_body);
    if($business_unit != "mobile") {
      if(!$this->isConvergent($id)) {
        $body['accountNumber'] = $data_transaction->accountId;
      }
    }

    $orderId = $this->utilsPayment
      ->generateOrderId($body, $business_unit, $product_type, $this->params, $isMultipay);
    $fields = [
      'stateOrder' => "ORDER_IN_PROGRESS",
      'changed' => time(),
      'orderId' => $orderId->body->orderId,
      'transactionId' => $orderId->body->transactionId,
    ];
    $this->transactions->updateDataTransaction($decrypt_purchase_order_id, $fields);
    $bodyLogs = $body;
    $bodyLogs['creditCardDetails'] = [];
    $fieldsLog = [
      'purchaseOrderId' => $decrypt_purchase_order_id,
      'message' => "Order in progress",
      'codeStatus' => 200,
      'operation' => $this->transactions::CREATED_ORDER,
      'description' => "Back office response: \n" . json_encode($orderId->body, JSON_PRETTY_PRINT) . "\nBody: \n" . json_encode($bodyLogs, JSON_PRETTY_PRINT),
      'type' => $product_type,
    ];
    $this->transactions->addLog($fieldsLog);

    return $orderId->body;
  }

  /**
   * Validar los datos de factura.
   */
  public function validateDataOverWrite(&$addData, $config_fac) {
    if (isset($this->params['billingData'])) {
      if (isset($this->params['billingData']['nit']) && (strlen($this->params['billingData']['nit']) > 0)) {
        $nit = trim(strtoupper($this->params['billingData']['nit']));
        $addData['documentNumber'] = $nit;
      }
      if ((!isset($this->params['billingData']['nit']) || empty($this->params['billingData']['nit'])) &&
        $this->token->isHe() && !$config_fac['billing_form']['nit']['required'] && $config_fac['billing_form']['nit']['show']) {
        $nit = trim($config_fac['billing_form']['nit']['default']);
        if (strlen($nit) == 0) {
          $nit = "0";
        }
        else {
          $nit = strtoupper($nit);
        }
        $addData['documentNumber'] = $nit;
      }
      if ((isset($this->params['billingData']['address']) && !empty($this->params['billingData']['address'])) &&
        $config_fac['billing_form']['address']['show']) {
        $address = trim($this->params['billingData']['address']);
        $addData['billToAddress']['street'] = $address;
      }
      if ((!isset($this->params['billingData']['address']) || empty($this->params['billingData']['address'])) &&
        !$config_fac['billing_form']['address']['required'] && $config_fac['billing_form']['address']['show']) {
        $address = trim($config_fac['billing_form']['address']['default']);
        if (strlen($address) >= 4) {
          $addData['billToAddress']['street'] = $address;
        }
      }
      if (isset($this->params['billingData']['email']) && $this->token->isHe()) {
        $email = trim($this->params['billingData']['email']);
        $this->params['email'] = $email;
      }
      if ((!isset($this->params['billingData']['email']) || empty($this->params['billingData']['email'])) &&
        !$config_fac['billing_form']['email']['required'] && $this->token->isHe() && $config_fac['billing_form']['email']['show']) {
        $email = trim($config_fac['billing_form']['email']['default']);
        $this->params['email'] = $email;
      }
      if (isset($this->params['billingData']['fullname']) && $this->token->isHe() && $config_fac['billing_form']['fullname']['show']) {

        if (isset($this->params['customerNameToken']) && isset($this->params['tokenizedCardId'])) {
          $this->params['customerNameToken'] = trim($this->params['billingData']['fullname']);
        }
        else {
          $this->params['customerName'] = trim($this->params['billingData']['fullname']);
        }
      }
      if ((!isset($this->params['billingData']['fullname']) || empty($this->params['billingData']['fullname'])) &&
        !$config_fac['billing_form']['fullname']['required'] && $this->token->isHe() && $config_fac['billing_form']['fullname']['show']) {
        $name = trim($config_fac['billing_form']['fullname']['default']);
        if (isset($this->params['customerNameToken']) && isset($this->params['tokenizedCardId'])) {
          $this->params['customerNameToken'] = $name;
        }
        else {
          $this->params['customerName'] = $name;
        }
      }
    }
    else {
      if (!$config_fac['billing_form']['nit']['required'] && $this->token->isHe() && $config_fac['billing_form']['nit']['show']) {
        $nit = trim($config_fac['billing_form']['nit']['default']);
        if (strlen($nit) == 0) {
          $nit = "0";
        }
        else {
          $nit = strtoupper($nit);
        }
        $addData['documentNumber'] = $nit;
      }
      if (!$config_fac['billing_form']['address']['required'] && $config_fac['billing_form']['address']['show']) {
        $address = trim($config_fac['billing_form']['address']['default']);
        if (strlen($address) >= 4) {
          $addData['billToAddress']['street'] = $address;
        }
      }
      if (!$config_fac['billing_form']['email']['required'] && $this->token->isHe()) {
        $email = trim($config_fac['billing_form']['email']['default']);
        $this->params['email'] = $email;
      }
      if (!$config_fac['billing_form']['fullname']['required'] && $this->token->isHe() &&
        $config_fac['billing_form']['fullname']['show']) {
        $name = trim($config_fac['billing_form']['fullname']['default']);
        if (isset($this->params['customerNameToken']) && isset($this->params['tokenizedCardId'])) {
          $this->params['customerNameToken'] = $name;
        }
        else {
          $this->params['customerName'] = $name;
        }
      }
    }
    if (!isset($addData['documentNumber'])) {
      $addData['documentNumber'] = "0";
    }
  }

  /**
   * @param $dataTransaction
   * @return array
   */
  public function formatPaymentInformation($dataTransaction){
    $row = [];
    if (isset($dataTransaction->orderId)) {
      $row['orderId'] = $dataTransaction->orderId;
      $row['transactionId'] = $dataTransaction->transactionId;
    }

    return $row;
  }

  public function isConvergent($id) {
    $billing = \Drupal::service('oneapp_mobile_billing.billing_service');
    return $billing->isConvergent($id);
  }

}
