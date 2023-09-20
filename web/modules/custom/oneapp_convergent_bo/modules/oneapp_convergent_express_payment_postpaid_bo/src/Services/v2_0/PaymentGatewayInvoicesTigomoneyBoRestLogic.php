<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\BadRequestHttpException;
use Drupal\oneapp_convergent_payment_gateway_invoices\Services\v2_0\PaymentGatewayRestLogic;

/**
 * Class PaymentGatewayTmInvoicesRestLogic.
 */
class PaymentGatewayInvoicesTigomoneyBoRestLogic extends PaymentGatewayRestLogic {

  /**
   * Default configuration.
   *
   * @var token
   */
  private $token;

  /**
   * Default configuration.
   *
   * @var initialData
   */
  protected $initialData;

  /**
   * Default configuration.
   *
   * @var businessUnit
   */
  protected $businessUnit;

  /**
   * Default configuration.
   *
   * @var $mobileUtils
   */
  protected $mobileUtils;

  /**
   * Set config.
   */
  public function setBusinessUnit($business_unit) {
    $this->businessUnit = $business_unit;
  }

  /**
   * Set config.
   */
  public function initMobileUtils() {
    $this->mobileUtils = \Drupal::service('oneapp.mobile.utils');
  }

  /**
   * Get payer account.
   */
  public function getPayerAccount($params, $id) {
    if (isset($params["payerAccount"])) {
      $params["payerAccount"] = $this->modifyMsisdnForPayment($params["payerAccount"]);
      return $params["payerAccount"];
    }
    else {
      return $this->modifyMsisdnForPayment($id);
    }
  }

  /**
   * Modifica Msisdn.
   */
  public function modifyMsisdnForPayment($msisdn) {
    $module_config = \Drupal::config("oneapp.payment_gateway_tigomoney." . $this->businessUnit .
      "_invoices.config")->get("configuration_app_pagos_express");
    $transaction_with_country_code = (bool) $module_config['setting_app_payment']['enableTransactionWithPrefix'] ?? FALSE;
    $new_msisdn = $this->mobileUtils->modifyMsisdnCountryCode($msisdn, $transaction_with_country_code);
    return $new_msisdn;
  }

  /**
   * Start (Initialize the payment process).
   */
  public function start($id, $id_type, $business_unit, $product_type, $params) {
    $payment_utils = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.utils_service_tm_invoice');
    $this->mobileUtils = \Drupal::service('oneapp.mobile.utils');
    $this->saveInitialData($id, $id_type, $business_unit);
    /*
    If the line is convergent, the business unit must be changed from mobile to
    home, since the APIs only bring information for home accounts and their debt
    information is the same in home and mobile.
     */
    $billing_account_id = $id;
    $this->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    $balance = $this->utilsPayment->getBalance($billing_account_id, $id_type, $business_unit, $params);
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->tokenAuthorization->setId($id);
    }
    $balance['additionalData']['payerAccount'] = $this->getPayerAccount($params, $id);
    $balance['additionalData']['invoiceId'] = $balance["invoiceId"] ?? '';
    if (isset($params["isPartialPayment"]) && !$params["isPartialPayment"]) {
      $params['amount'] = $balance["dueAmount"];
    }
    if ($balance['dueAmount'] != $params['amount'] && !$params['isPartialPayment']) {
      throw new \Exception("El monto es incorrecto");
    }
    if ($balance["dueAmount"] <= 0 && !$params['isPartialPayment']) {
      throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
    }
    if (isset($balance["noData"]["value"]) && $balance["noData"]["value"] == "empty") {
      throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
    }
    $amount = ($params["isPartialPayment"] && !isset($balance['amountForPartialPayment'])) ? $params["amount"] : $balance["dueAmount"];
    $id = $this->modifyMsisdnForPayment($id);
    $fields = [
      'uuid' => md5("null@cybersource.com"),
      'accountId' => $id,
      'accountNumber' => !empty($balance["accountNumber"]) ? $balance["accountNumber"] : $id,
      'accountType' => $business_unit,
      'productType' => $product_type,
      'amount' => $amount,
      'isPartialPayment' => $params['isPartialPayment'] ? 1 : 0,
      'numberReference' => 0,
      'accessType' => $this->tokenAuthorization->getAccessType(),
    ];
    if (isset($balance['additionalData']) && (!empty($balance['additionalData']))) {
      if (isset($balance['period'])) {
        $period = $balance['period'];
        $period_data = explode(",", $period);
        $periods = "";
        for ($i=0;$i<count($period_data);$i++) {
          if ($i==0) {
            $periods = $payment_utils->getFormattedPeriodNumber(trim($period_data[$i]));
          }
          else {
            $periods .= ",".$payment_utils->getFormattedPeriodNumber(trim($period_data[$i]));
          }
        }
        $balance['period'] = $period;
        $balance['additionalData']['period'] = $period;
      }
      elseif (isset($balance['endPeriod'])) {
        $balance['additionalData']['period'] = $balance['endPeriod'];
      }
      elseif (isset($balance['dueDate'])) {
        $balance['additionalData']['period'] = $balance['dueDate'];
      }

      $balance['additionalData'] += $this->initialData;
      $fields['additionalData'] = serialize($balance['additionalData']);
    }
      
    $transaction_id = $this->transactions->initTransaction($fields, $product_type);
    $purchaseorder_id = $this->transactions->encryptId($transaction_id);

    $response = [
      'purchaseorderId' => $purchaseorder_id,
      'amount' => $amount,
      'invoiceId' => $balance["invoiceId"],
      'accountNumber' => !empty($balance["accountNumber"]) ? $balance["accountNumber"] : $id,
      'accountId' => $id,
      'payerAccount' => $balance['additionalData']['payerAccount'],
      'isMultipay' => (isset($balance["multipay"]) && $balance["multipay"]) ? TRUE : FALSE,
      'productType' => $this->config['fields']['productType']['value'],
      'additionalData' => $this->initialData,
    ];
    if (isset($balance['period'])) {
      $response['period'] = $balance['period'];
    }
    elseif (isset($balance['endPeriod'])) {
      $response['period'] = $balance['endPeriod'];
    }
    elseif (isset($balance['dueDate'])) {
      $response['period'] = $balance['dueDate'];
    }

    if ($response['period']) {
      $period = $response['period'];
      $period_data = explode(",", $period);
      $periods = "";
      for ($i=0;$i<count($period_data);$i++) {
        if ($i==0) {
          $periods = $payment_utils->getFormattedPeriodNumber(trim($period_data[$i]));
        }
        else {
          $periods .= ",".$payment_utils->getFormattedPeriodNumber(trim($period_data[$i]));
        }
      }
      $response['period'] = $periods;
    }

    return $response;
  }

  /**
   * Encrypts data.
   *
   * @param mixed $data
   *   Data.
   *
   * @return string
   *   Encripta URL redirect.
   */
  public function encryptUrl($data) {
    $key = \Drupal::config('oneapp_convergent_payment_gateway.config')->get('tigoMoney')['rsa']['public_key'];
    $key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($key, 64, "\n", TRUE) . "\n-----END PUBLIC KEY-----";
    openssl_public_encrypt($data, $encrypt, $key);
    return base64_encode($encrypt);
  }

  /**
   * Look for the selected template id in the config block.
   */
  protected function getTemplateOtp() {
    $payment_method_name = \Drupal::request()->query->get('paymentMethodName');
    if ($payment_method_name === 'tigoMoney' || $payment_method_name === 'tigoMoney_Inv') {
      return '';
    }
    else {
      $config_templates_otp = \Drupal::config('oneapp_mobile.otp.config')->get('templates');
      $template_list = [];
      foreach ($config_templates_otp as $template) {
        $template_list[] = $template['templateId'];
      }
      $template_id = $this->config['config']['templateId']['type'];
      return $template_list[$template_id];
    }
  }

  /**
   * Formatting the transaction status data.
   */
  public function formatStatus($transaction, $business_unit) {
    $format_data = [];
    $utils = \Drupal::service('oneapp.utils');
    $add_data = isset($transaction->additionalData) ? unserialize($transaction->additionalData) : '';
    foreach ($this->config["fields"] as $field => $value) {
      // Formato a labels dependiendo de businessUnit.
      $labels = explode('|', $value['label']);
      if (count($labels) > 1) {
        if ($business_unit == "home") {
          $format_data[$field]['label'] = $labels[1];
        }
        else {
          $format_data[$field]['label'] = $labels[0];
        }
      }
      else {
        $format_data[$field]['label'] = $value['label'];
      }
      // Formato a values dependiendo de businessUnit.
      if (isset($value['value']) && $value['value'] != '') {
        $values_content = explode('|', $value['value']);
        if (count($values_content) > 1) {
          if ($business_unit == "home") {
            $value['value'] = $values_content[1];
          }
          else {
            $value['value'] = $values_content[0];
          }
        }
      }
      $format_data[$field]['show'] = $this->utils->formatBoolean($value['show']);
      $format_data[$field]['value'] = $transaction->$field ?? '';

      switch ($field) {
        case 'payerAccount':
          $format_data[$field]['value'] = $add_data['payerAccount'] ?? '';
          $format_data[$field]['formattedValue'] = isset($add_data['payerAccount']) ?
            $this->mobileUtils->modifyMsisdnCountryCode($add_data['payerAccount'], FALSE) : '';
          break;

        case 'accountId':
          $format_data[$field]['formattedValue'] = isset($transaction->$field) ?
            $this->mobileUtils->modifyMsisdnCountryCode($transaction->$field, FALSE) : '';
          break;

        case 'productType':
          if ($transaction->$field == 'tm_invoices') {
            $format_data[$field]['value'] = 'invoices';
            $format_data[$field]['formattedValue'] = $value['value'];
          }
          else {
            $format_data[$field]['value'] = $transaction->$field;
          }
          break;

        case 'period':
          if (isset($add_data['period']) && !preg_match("^[a-zA-S,U-Z]^", $add_data['period'])) {
            $dates = explode('-', $add_data['period']);
            try {
              $is_valid = checkdate(intval($dates[2]), intval($dates[1]), intval($dates[0]));
              if ($is_valid) {
                $format_data[$field]['value'] = $add_data['period'] ?? '';
                $format_data[$field]['formattedValue'] = $utils->formatDate(strtotime($add_data['period']),
                  $this->config["dateFormat"]["dateFormat"]);
              }
              else {
                $format_data[$field]['value'] = $add_data['period'] ?? '';
                $format_data[$field]['formattedValue'] = $add_data['period'] ?? '';
              }
            }
            catch (\Exception $e) {
              $format_data[$field]['value'] = $add_data['period'] ?? '';
              $format_data[$field]['formattedValue'] = $add_data['period'] ?? '';
            }
          }
          else {
            $format_data[$field]['value'] = $add_data['period'] ?? '';
            $format_data[$field]['formattedValue'] = $add_data['period'] ?? '';
          }
          if (empty($format_data[$field]['formattedValue'])) {
            $format_data[$field]['show'] = FALSE;
          }
          break;

        case 'changed':
          $format_data[$field]['formattedValue'] = (isset($transaction->$field) && $transaction->$field > 0) ?
            $this->utils->formatDate($transaction->$field, $this->config["dateFormat"]["dateFormat"]) : '';
          break;

        case 'amount':
          $format_data[$field]['formattedValue'] = isset($transaction->$field) ?
            $this->utils->formatCurrency($transaction->$field, TRUE) : '';
          break;

        case 'cardBrand':
          $format_data[$field]['value'] = $value['value'];
          $format_data[$field]['formattedValue'] = $value['value'];
          break;

        case 'wallet':
          $format_data[$field]['value'] = $value['value'] ?? '';
          $format_data[$field]['formattedValue'] = $value['value'] ?? '';
          break;

        default:
          $format_data[$field]['formattedValue'] = $transaction->$field;
      }
    }
    $aditional = $this->getConfigAditionalData($transaction->additionalData);
    return array_merge($format_data, $aditional);
  }

  /**
   * Generate order in payment gateway.
   */
  public function generateOrderId($business_unit, $product_type, $id_type, $id, $purchaseorder_id) {
    $this->mobileUtils = \Drupal::service('oneapp.mobile.utils');
    // If is a convergent line, the variables could change.
    $billing_account_id = $id;
    $this->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $this->tokenAuthorization->setBusinessUnit($business_unit);
    $this->tokenAuthorization->setIdType($id_type);
    if ($business_unit == 'home') {
      $id = $billing_account_id;
      $this->tokenAuthorization->setId($id);
    }
    $decrypt_purchaseorder_id = $this->transactions->decryptId($purchaseorder_id);
    $data_transaction = $this->transactions->getTransactionById($decrypt_purchaseorder_id);
    $additional_data = $this->setAdditionalData($data_transaction);
    $payer_account = $this->mobileUtils->modifyMsisdnCountryCode($additional_data['payerAccount'], TRUE);
    /*$validate_otp = \Drupal::config('oneapp_convergent_payment_gateway.config')->get('tigoMoney')['validateAccountTigoMoney']['validate'];
    if ($validate_otp) {
      if (isset($this->params['verificationCode'])) {
        $is_valid_otp = $this->validityCodeOtp($payer_account, $this->params['verificationCode']);
        if ($is_valid_otp === FALSE) {
          $this->sendException($this->config['errors']['invalidOtp']['value'], 400, NULL);
        }
      }
      else {
        $this->sendException($this->config['errors']['verifyOtp']['value'], 400, NULL);
      }
    }*/
    $this->params['uuid'] = md5($params['email']);
    $this->params['tokenUuId'] = $this->tokenAuthorization->getTokenUuid();
    // Orden de prioridad para correo a utilizar.
    $this->params['email'] = (empty($this->tokenAuthorization->getEmail()) && isset($this->params['email'])) ? $this->params['email'] : $this->tokenAuthorization->getEmail();
    if (!$this->tokenAuthorization->isHe()) {
      $this->params['customerNameToken'] = $this->tokenAuthorization->getGivenNameUser() . " " .
        $this->tokenAuthorization->getFirstNameUser();
    }
    $config_app = $this->tokenAuthorization->getApplicationSettings('configuration_app_pagos_express', '', '_tigomoney');
    $this->params['apiHost'] = $config_app["setting_app_payment"]["api_path"];
    if (isset($config_app["setting_app_payment"]["aws_service"])) {
      $this->params['aws_service'] = $config_app["setting_app_payment"]["aws_service"];
    }
    $is_multipay = (isset($additional_data['isMultipay']) && $additional_data['isMultipay']) ? TRUE : FALSE;
    $additional_data_for_payment_body = (isset($additional_data['fieldsForPaymentBody'])) ? $additional_data['fieldsForPaymentBody'] : [];
    if ($is_multipay) {
      $additional_data_for_payment_body['applicationName'] = $config_app["setting_app_payment"]['applicationNameMultipay'];
    }
    //$id = $this->modifyMsisdnForPayment($id);
    $body = $this->utilsPayment
      ->getBodyPayment($business_unit, $product_type, $id_type, $id, $purchaseorder_id, $this->params, $additional_data_for_payment_body);
    if (isset($this->params['paymentMethodName'])) {
      unset($this->params['code']);
      unset($this->params['verificationCode']);
      unset($this->params['paymentMethodName']);
    }
    $order_id = $this->utilsPayment
      ->generateOrderId($body, $business_unit, $product_type, $this->params, $is_multipay);
    $fields = [
      'stateOrder' => "ORDER_IN_PROGRESS",
      'changed' => time(),
      'orderId' => $order_id->body->orderId,
      'transactionId' => $order_id->body->transactionId,
    ];
    $this->transactions->updateDataTransaction($decrypt_purchaseorder_id, $fields);
    $body_logs = $body;
    $body_logs['creditCardDetails'] = [];
    $fields_log = [
      'purchaseOrderId' => $decrypt_purchaseorder_id,
      'message' => "Order in progress",
      'codeStatus' => 200,
      'operation' => $this->transactions::CREATED_ORDER,
      'description' => "Back office response: \n" . json_encode($order_id->body, JSON_PRETTY_PRINT) . "\nBody: \n" .
        json_encode($body_logs, JSON_PRETTY_PRINT),
      'type' => $product_type,
    ];
    $this->transactions->addLog($fields_log);

    return $order_id->body;
  }

  /**
   * Data format for summary.
   */
  public function summaryTransaction($id, $balance, $purchaseorder_id) {
    if (isset($balance->additionalData) && (!empty($balance->additionalData))) {
      $fields['additionalData'] = unserialize($balance->additionalData);
    }
    $response = [
      'purchaseorderId' => $purchaseorder_id,
      'dueAmount' => $balance->amount,
      'invoiceId' => isset($fields['additionalData']["invoiceId"]) ? $fields['additionalData']["invoiceId"] : '',
      'accountNumber' => isset($balance->accountNumber) ? $balance->accountNumber : $id,
      'accountId' => $id,
      'payerAccount' => $fields['additionalData']["payerAccount"],
      'isMultipay' => (isset($fields['additionalData']["isMultipay"]) && $fields['additionalData']["isMultipay"]) ? TRUE : FALSE,
      'productType' => t('Pago de factura'),
    ];
    if (isset($fields['additionalData']["period"])) {
      $response['period'] = $fields['additionalData']["period"];
    }
    if ($response['isMultipay']) {
      $config_app = (object) $this->tokenAuthorization->getApplicationSettings('configuration_app_pagos_express', '', '_tigomoney');
      $response['applicationName'] = $config_app->setting_app_payment['applicationNameMultipay'];
    }
    return $response;
  }

  /**
   * Returns payment summary.
   */
  public function responseSummary($data, $business_unit) {
    $this->mobileUtils = \Drupal::service('oneapp.mobile.utils');
    $fields = [];
    $formatted_value = "";
    $utils_payment = \Drupal::service('oneapp_convergent_payment_gateway_tigomoney.v2_0.utils_service');
    $utils = \Drupal::service('oneapp.utils');
    foreach ($this->config["fields"] as $key => $field) {
      switch ($key) {
        case 'dueAmount':
          $formatted_value = isset($data[$key]) ? $this->utils->formatCurrency($data[$key], TRUE) : '';
          break;

        case 'dueDate':
          $date_formatter = \Drupal::service('date.formatter');
          $formatted_value = isset($data[$key]) ? $date_formatter->format(strtotime($data[$key]),
            $this->config["dateFormat"]["dateFormat"]) : '';
          break;

        case 'period':
          if (isset($data[$key]) && !preg_match("^[a-zA-Z]^", $data[$key])) {
            $fecha_array = explode('-', $data[$key]);
            $is_valid = checkdate($fecha_array[2], $fecha_array[1], $fecha_array[0]);
            if ($is_valid) {
              $formatted_value = $utils->formatDate(strtotime($data[$key]), $this->config["dateFormat"]["dateFormat"]);
            }
            else {
              $formatted_value = isset($data[$key]) ? $data[$key] : '';
            }
          }
          else {
            $formatted_value = isset($data[$key]) ? $data[$key] : '';
          }
          break;

        default:
          $formatted_value = isset($data[$key]) ? $data[$key] : '';
          break;
      }
      $fields[$key] = [
        'show' => $field["show"] ? TRUE : FALSE,
        'label' => $field["label"],
        'value' => isset($data[$key]) ? $data[$key] : '',
        'formattedValue' => isset($formatted_value) ? $formatted_value : '',
      ];
      if ($key == 'payerAccount') {
        $fields['payerAccount']['value'] = $this->mobileUtils->modifyMsisdnCountryCode($fields['payerAccount']['value'], TRUE);
      }
      elseif ($key == 'paymentMethod') {
        $fields['paymentMethod']['value'] = $this->config["fields"]["paymentMethod"]['value'];
        $fields['paymentMethod']['formattedValue'] = $this->config["fields"]["paymentMethod"]['value'];
      }
    }
    if ($data['purchaseorderId'] != FALSE) {
      $fields['purchaseOrderId'] = [
        'label' => $this->config["fields"]["purchaseOrderId"]["label"],
        'show' => $this->config["fields"]["purchaseOrderId"]["show"] ? TRUE : FALSE,
        'value' => $data['purchaseorderId'],
        'formattedValue' => $data['purchaseorderId'],
      ];
      $fields['status'] = TRUE;
    }
    $purchase_summary['otpTemplateId'] = $this->getTemplateOtp();
    $subtitles = $this->config['config'];
    foreach ($subtitles as $key => $value) {
      $purchase_summary[$key]['label'] = $value['label'];
      $purchase_summary[$key]['show'] = (bool) $value['show'];
    }
    unset($purchase_summary['templateId']);
    foreach ($this->config['actions'] as $key => $value) {
      $purchase_summary['actions'][$key] = $value;
      $purchase_summary['actions'][$key]['show'] = (bool) $value['show'];
    }
    $purchase_summary['actions']['termsOfServices'] = $this->config['termsAndConditions'][$business_unit];
    $purchase_summary['actions']['termsOfServices']['show'] = ($this->config['termsAndConditions'][$business_unit]['show'] == 1) ?
      TRUE : FALSE;
    $config = [
      'confirmation' => $purchase_summary,
    ];
    $billing_data_form['billingDataForm'] = [];
    $tigo_money_form['tigoMoneyForm'] = [];
    $others_config = $utils_payment->getPassNumberDataForm('invoices', $business_unit);
    if ($others_config) {
      foreach ($this->config['tigoMoneyFormActions'] as $key => $value) {
        $tigo_money_form_actions[$key] = $value;
        $tigo_money_form_actions[$key]['show'] = (bool) $value['show'];
      }
      $config['forms'] = [$billing_data_form, $others_config];
      $config['actions'] = $tigo_money_form_actions;
    }
    else {
      $config['forms'] = [$billing_data_form, $tigo_money_form];
    }
    return [
      'data' => $fields,
      'config' => $config,
    ];
  }

  /**
   * Validate otp.
   */
  public function validityCodeOtp($id, $verification_code) {
    try {
      $api_key = \Drupal::config('oneapp_mobile.otp.config')->get('headers')['apikey'];
      $header = [
        'apikey' => $api_key,
      ];
      $params_endpoint = [
        'id' => $id,
        'code' => $verification_code,
      ];
      $response_otp = $this->manager
        ->load('validate_otp_endpoint')
        ->setParams($params_endpoint)
        ->setHeaders($header)
        ->setQuery([])
        ->setBody([])
        ->sendRequest();
    }
    catch (\Exception $exception) {
      return FALSE;
    }
    if (isset($response_otp->status) && $response_otp->status === 1 && isset($response_otp->action) &&
      $response_otp->action === "OTP Correcto") {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Send exception.
   */
  public function sendException($msg, $code, $exception = NULL) {
    if (is_null($exception)) {
      $exception = new \Exception($msg, $code);
    }
    $error = new ErrorBase();
    $error->getError()->set('message', $msg);
    throw new BadRequestHttpException($error, $exception, $exception->getCode());
  }

  /**
   * Values depending on the businessUnit are left in the configuration.
   */
  public function formatInitConfigs($config, $business_unit) {

    foreach ($config as $key => $item) {
      if (is_array($item)) {
        foreach ($item as $index => $field) {
          if (is_array($field)) {
            $labels = explode('|', $field['label']);
            if (count($labels) > 1) {
              if ($business_unit == "home") {
                $config[$key][$index]['label'] = $labels[1];
              }
              else {
                $config[$key][$index]['label'] = $labels[0];
              }
            }
            if (isset($field['value']) && $field['value'] != '') {
              $values = explode('|', $field['value']);
              if (count($values) > 1) {
                if ($business_unit == "home") {
                  $config[$key][$index]['value'] = $values[1];
                }
                else {
                  $config[$key][$index]["value"] = $values[0];
                }
              }
            }
          }
          else {
            if (is_array($item)) {
              if (isset($item['label']) && $item['label'] != '') {
                $labels = explode('|', $item['label']);
                if (count($labels) > 1) {
                  if ($business_unit == "home") {
                    $config[$key]['label'] = $labels[1];
                  }
                  else {
                    $config[$key]['label'] = $labels[0];
                  }
                }
              }
              if (isset($item['value']) && $item['value'] != '') {
                $values = explode('|', $item['value']);
                if (count($values) > 1) {
                  if ($business_unit == "home") {
                    $config[$key]['value'] = $values[1];
                  }
                  else {
                    $config[$key]["value"] = $values[0];
                  }
                }
              }
              if (isset($item['title']) && $item['title'] != '') {
                $values = explode('|', $item['title']);
                if (count($values) > 1) {
                  if ($business_unit == "home") {
                    $config[$key]['title'] = $values[1];
                  }
                  else {
                    $config[$key]["title"] = $values[0];
                  }
                }
              }
              if (isset($item['body']) && $item['body'] != '') {
                $values = explode('|', $item['body']);
                if (count($values) > 1) {
                  if ($business_unit == "home") {
                    $config[$key]['body'] = $values[1];
                  }
                  else {
                    $config[$key]["body"] = $values[0];
                  }
                }
              }
              break;
            }
          }
        }
      }

    }
    return $config;
  }

  /**
   * Get aditional data.
   */
  public function getConfigAditionalData($aditional_data) {

    $data_format = [];
    if (!empty($aditional_data) && $this->config["aditional"] != '') {
      $data = unserialize($aditional_data);
      if (isset($data) && count($data) > 0) {
        $data = (array) $data;
        foreach ($this->config["aditional"] as $index => $configData) {
          if (isset($configData["variable"]) && strlen($configData["variable"]) > 2) {
            $data_format[$configData["variable"]] = [
              'label' => $configData["label"],
              'show' => $configData["show"] ? TRUE : FALSE,
              'value' => isset($data[$configData["variable"]]) ? $data[$configData["variable"]] : '',
              'formattedValue' => isset($data[$configData["variable"]]) ? $data[$configData["variable"]] : '',
            ];
          }
        }
      }
    }
    return $data_format;
  }

  /**
   * Save initial data.
   */
  public function saveInitialData($id, $id_type, $business_unit) {
    $this->initialData['id'] = $id;
    $this->initialData['businessUnit'] = $business_unit;
    $this->initialData['idType'] = $id_type;
  }

  public function getConfig($params, $response_data) {
    $purchase_summary = [];
    $purchase_summary['otpTemplateId'] = $this->getTemplateOtp();
    $subtitles = $this->config['config'];
    foreach ($subtitles as $key => $value) {
      $purchase_summary[$key]['label'] = $value['label'];
      $purchase_summary[$key]['show'] = (bool) $value['show'];
    }
    unset($purchase_summary['templateId']);
    foreach ($this->config['actions'] as $key => $value) {
      $purchase_summary['actions'][$key] = $value;
      $purchase_summary['actions'][$key]['show'] = (bool) $value['show'];
    }
    $purchase_summary['actions']['termsOfServices'] = $this->config['termsAndConditions'][$response_data['businessUnit']];
    $purchase_summary['actions']['termsOfServices']['show'] =
      ($this->config['termsAndConditions'][$response_data['businessUnit']]['show'] == 1)
      ? TRUE : FALSE;
    $config = [
      'isValidTigoMoneyAccount' => $response_data['validTigomoneyAccount']['value'],
      'confirmation' => $purchase_summary,
    ];
    return $config;
  }

  /**
   * The data is formatted.
   */
  public function formatData($data) {
    $fields = [];
    foreach ($this->config["fields"] as $key => $field) {
      switch ($key) {
        case 'amount':
          $formatted_value = isset($data[$key]) ? $this->utils->formatCurrency($data[$key], TRUE) : '';
          break;

        case 'accountId':
        case 'payerAccount':
          $formatted_value = $this->mobileUtils->modifyMsisdnCountryCode($data[$key], FALSE);
          break;

        case 'dueDate':
          $utils = \Drupal::service('oneapp.utils');
          $formatted_value = isset($data[$key]) ? $utils->formatDate(strtotime($data[$key]), $this->config["config"]["format"]) : '';
          break;

        default:
          $formatted_value = isset($data[$key]) ? $data[$key] : $field['value'];
          break;
      }
      $fields[$key] = [
        'show' => $field["show"] ? TRUE : FALSE,
        'label' => $field["label"],
        'value' => isset($data[$key]) ? $data[$key] : $field['value'],
        'formattedValue' => isset($formatted_value) ? $formatted_value : '',
      ];
      if ($key == 'productType') {
        $fields['productType']['value'] = 'invoices';
      }
      elseif ($key == 'payerAccount') {
        $fields['payerAccount']['value'] = $this->mobileUtils->modifyMsisdnCountryCode($fields['payerAccount']['value'], TRUE);
      }
      elseif ($key == 'paymentMethod') {
        $fields['paymentMethod']['value'] = $this->config["fields"]["paymentMethod"]['value'];
        $fields['paymentMethod']['formattedValue'] = $this->config["fields"]["paymentMethod"]['value'];
      }

    }
    if ($data['purchaseorderId'] != FALSE) {
      $fields['purchaseOrderId'] = [
        'label' => $this->config["fields"]["purchaseOrderId"]["label"],
        'show' => $this->config["fields"]["purchaseOrderId"]["show"] ? TRUE : FALSE,
        'value' => $data['purchaseorderId'],
        'formattedValue' => $data['purchaseorderId'],
      ];
      $request = \Drupal::request();
      $origin = $request->headers->get('origin');
      $origin = (strpos($origin, 'localhost') !== FALSE) ? 'app' : 'web';
      $payer_account_url = $this->mobileUtils->modifyMsisdnCountryCode($data['payerAccount'], FALSE);
      $account_id_url = $this->mobileUtils->modifyMsisdnCountryCode($data['additionalData']['id'], FALSE);
      $general_config_tigomoney = \Drupal::config('oneapp_convergent_payment_gateway.config')->get('tigoMoney');
      $form_invoice_config = \Drupal::config('oneapp.payment_gateway_tigomoney.' .
      $data['additionalData']['businessUnit'] . '_invoices.config')->get('redirectUrl');
      $url_redirect_oneapp = $form_invoice_config['redirectParameterUrl']['value'];
      $url_redirect_oneapp = str_replace('{MSISDN}', $account_id_url, $url_redirect_oneapp);
      $url_redirect_oneapp = str_replace('{PURCHASE_ORDER}', $data['purchaseorderId'], $url_redirect_oneapp);
      $url_redirect_oneapp = str_replace('{ORIGIN}', $origin, $url_redirect_oneapp);
      $url_redirect_oneapp = str_replace('po', $form_invoice_config['po']['value'], $url_redirect_oneapp);
      $encrypt_params = 'device_type=MOBILE&client_id=client_id&response_type=code&' .
        $general_config_tigomoney['queryParams']['X-Username'] .
        '=' . $payer_account_url . '&' . $general_config_tigomoney['queryParams']['redirect_uri'] . '=' .
        $form_invoice_config['redirectUrl']['value'] . '?' . $form_invoice_config['redirectParameter']['value'] .
        '=' . $url_redirect_oneapp;
      $encrypt_url = $this->utilsPayment->encryptUrl($encrypt_params);
      $encrypt_url = $data['validTigomoneyAccount']['url'] . '?' . $general_config_tigomoney['queryParams']['params'] . '=' . $encrypt_url;

      if ($data['validTigomoneyAccount']['value'] === TRUE) {
        $url = $encrypt_url;
      }
      else {
        $url = $data['validTigomoneyAccount']['url'];
      }
      $fields['redirectUrl'] = [
        'label' => $form_invoice_config['redirectUrl']['label'],
        'value' => $url,
        'formattedValue' => $url,
        'show' => (bool) $form_invoice_config['show'],
      ];
    }
    return $fields;
  }

}
