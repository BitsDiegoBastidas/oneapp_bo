<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

/**
 * Class EmailCallbackRestLogicBo.
 */

class EmailCallbackRestLogicBo {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
  * */
      
  protected $configFactory;
      
  /**
  * App config suffix.
  * @var string
  */

  private $configSuffix;

  /**
   * async suffix.
   *
   * @var string
   */
  private $asyncSuffix;

  /**
   * PaymentGatewayRestLogic constructor.
   */
  public function __construct() {
    $this->configSuffix = '';
    $this->asyncSuffix = '';
  }

  /**
   * Sets the field values based.
   */
  public function setSuffixConfigSuffix($config_suffix) {
    $this->configSuffix = $config_suffix;
  }

  /**
   * Gets the field values
   */
  public function getConfigSuffix() {
    return $this->configSuffix;
  }

  /**
   * Sets the field values based.
   */
  public function setAsyncSuffix($async_suffix) {
    $this->asyncSuffix = $async_suffix;
  }

  /**
   * Gets the field values
   */
  public function getAsyncSuffix() {
    return $this->asyncSuffix;
  }

  public function sendMailPayment($business_unit, $product_type, $params, $data_transaction, $status_mail = '') {
    $output_body = $this->getOutputMail($business_unit, $product_type, $status_mail);
    $tokens = $this->buildTokens($params, $data_transaction);
    $payment_gateway = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.error_service');
    $tokens['errorMessage'] = (isset($params['codeError'])) ? $payment_gateway->getMessageErrorGenerateOrderId($params['codeError']) : '';
    $tokens['username'] = $this->getUserName($params['customerName']);
    $tokens['cc_mail'] = $output_body['cc_mail'];
    $config_mail = [
      'subject' => $output_body['subject'],
      'body' => $output_body['body']['value'],
    ];
    if ($this->isActiveSendMail($business_unit, $product_type)) {
      \Drupal::logger('sendMailPayment')->warning('<pre><code>' . print_r('entra_if', TRUE) . '</code></pre>');
      $this->apiPaymentSendMail($tokens, $config_mail);
    }
  }

  public function buildTokens($params, $additional_data) {
    $masked_account_id = '';
    $utils = \Drupal::service('oneapp.utils');
    if (!empty($params['paymentInstrument']['maskedAccountId'])) {
      $payment_utils = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.utils_service');
      $masked_account_id = $payment_utils->formatMaskedAccountId($params['paymentInstrument']['maskedAccountId']);
    }
    $tokens = [
      'accountId' => $params['accountId'] ?? $params['Id'],
      'username' => $params['customerName'],
      'mail_to_send' => $params['email'],
      'date' => date('d-m-Y h:i:s a'),
      'accountNumber' => $params['Id'],
      'cardBrand' => $params['paymentInstrument']['cardBrand'] ?? "Tarjeta",
      'paymentTransactionId' => $params['paymentGatewayTransactionId'],
      'registrationDate' => date('d-m-Y h:i a', strtotime($params['registrationDate'])),
      'registrationDayMonthYear' => ($params['registrationDate']) ? date('d/m/Y', strtotime($params['registrationDate'])) : "",
      'registrationHour' => ($params['registrationDate']) ? date('h:i a', strtotime($params['registrationDate'])) : "",
      'period' =>'',
      'cardNumber' => $masked_account_id,
      'paymentOrderId' => $params['orderId'],
      'paymentProcessortransactionId' => $params['paymentProcessorTransactionId'] ?? "no existe número de referencia",
      'authorizationCode' => $params['authorizationCode'] ?? "no existe número de referencia",
      'paymentAmount' => $utils->formatCurrency($params['paymentAmount'], TRUE),
    ];
    if (is_object($additional_data)) {
      foreach ($additional_data as $key => $value) {
        $tokens[$key] = $value;
      }
      if (property_exists($additional_data, 'additionalData')) {
        if ($additional_data->additionalData) {
          $additional_data = unserialize($additional_data->additionalData);
          if (is_string($additional_data)) {
            $tokens['period'] = $additional_data;
          }
          else {
            foreach ($additional_data as $key => $value) {
              $tokens[$key] = $value;
            }
          }
        }
      }
    }
    $tokens = $this->addTokens($tokens, $params);
    return $tokens;
  }

  public function getOutputMail($business_unit, $product_type, $status_mail) {
    $config_mail = empty($this->asyncSuffix) ? 'configuration_mail' : $this->asyncSuffix . '_configuration_mail';
    if ($config_mail == "tigomoney_configuration_mail") {
      $config_app_mail = \Drupal::config("oneapp.payment_gateway_tigomoney.{$business_unit}_{$product_type}.config")
          ->get('configuration_mail_pagos_express');
    }
    else {
      $config_app_mail = \Drupal::config("oneapp.payment_gateway{$this->configSuffix}.{$business_unit}_{$product_type}.config")
          ->get($config_mail);
    }
    $output = $config_app_mail[$status_mail];
    $output['cc_mail'] = (
      isset($config_app_mail['cc_mail']) && strlen($config_app_mail['cc_mail']) > 1
      ) ? $config_app_mail['cc_mail'] : NUll;
    return $output;
  }

  public function addTokens($tokens, $params) {
    if (isset($params['paymentInstrument'])) {
      foreach ($params['paymentInstrument'] as $key => $value) {
        $tokens["paymentInstrument{$key}"] = $value;
      }
    }

    if (isset($params['nameValuePairList'])) {
      foreach ($params['nameValuePairList'] as $key => $value) {
        $tokens["nameValuePairList{$key}"] = $value['value'];
      }
    }
    return $tokens;
  }

  /**
   * Delete defaults name and lastname.
   */
  public function getUserName($customer_name) {
    $customer_name_to_compare = strtolower($customer_name);
    $config_value_default = (object) \Drupal::config("oneapp_convergent_payment_gateway.config")->get('fields_default_values');
    $name = isset($config_value_default->name['name_default_value']) ? strtolower($config_value_default->name['name_default_value']) : '';
    $last_name = isset($config_value_default->name['last_name_default_value']) ?
      strtolower($config_value_default->name['last_name_default_value']) : '';
    if (strpos($customer_name_to_compare, $name) === FALSE &&
      strpos($customer_name_to_compare, $last_name) === FALSE &&
      strpos($customer_name_to_compare, 'none') === FALSE) {
      return $customer_name;
    }
    else {
      return '';
    }
  }

  protected function isActiveSendMail($business_unit, $product_type) {
    $config_mail =
      \Drupal::config("oneapp.payment_gateway{$this->configSuffix}.{$business_unit}_{$product_type}.config")->get('configuration_mail');
    if (isset($config_mail['active_send_mail'])) {
      return (bool) $config_mail['active_send_mail'];
    }
    return TRUE;
  }

  public function apiPaymentSendMail($tokens, $config_mail) {
    foreach ($tokens as $index => $token) {
      $params['tokens'][$index] = isset($token) ? $token : NULL;
    }
    $settings_from_data =  \Drupal::config('oneapp_mailer.settings')->getRawData();
    $from_name = $settings_from_data["oneapp_mailer_fromname"];
    $from_email = $settings_from_data["oneapp_mailer_from"];
    $subject = $config_mail['subject'];
    $token_service = \Drupal::token();
    $html_body = $token_service->replace($config_mail['body'], $params['tokens']);
    try {
      $mailer_service = \Drupal::service('oneapp.mailer.send');
      $mailer_service->sendMail($from_name, $from_email, $tokens["mail_to_send"], $subject, $html_body, 'email');
    }
    catch (\Exception $e) {
      \Drupal::logger('payment-email')->error(
        "Entro a EmailCallbackRestLogic.php y fallo email con error= @error",
        ['@error' => $e->getMessage()]
      );
      return $e;
    }
    if (isset($tokens['cc_mail']) && strlen(trim($tokens['cc_mail'])) > 10) {
      $cc_mail = explode(",", $tokens['cc_mail']);
      foreach ($cc_mail as $key => $value) {
        if (strlen(trim($value)) > 10) {
          try {
            $mailer_service->sendMail($from_name, $from_email, $value, $subject, $html_body, 'email');
          }
          catch (\Exception $e) {
            \Drupal::logger('payment-email')->error("Entro a EmailCallbackRestLogic.php y fallo cc email con error= @error",
              ['@error' => $e->getMessage()]
            );
          }
        }
      }
    }
  }

}
