<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_tm_invoices_bo\Services\v2_0\PaymentGatewayTmInvoicesAsyncRestLogicBo;

/**
 * Constains the logic to connect to Payment Gateway TigoMoney for Express.
 */
class PaymentGatewayExpressTmInvoicesAsyncRestLogicBo extends PaymentGatewayTmInvoicesAsyncRestLogicBo {

  /**
   * Start (Initialize the payment process).
   */
  public function start($id, $id_type, $business_unit, $product_type, $params) {
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
      'uuid' => $this->tokenAuthorization->getMailUuid($id),
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
        $balance['additionalData']['period'] = $balance['period'];
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
    return $response;
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

  /**
   * Get config response for invoices.
   */
  public function getConfigInvoices($response_data, $business_unit, $product_type) {
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
    $utils_payment = \Drupal::service('oneapp_convergent_payment_gateway_tigomoney.v2_0.utils_service');
    $billing_data_form['billingDataForm'] = [];
    $tigo_money_form['tigoMoneyForm'] = [];
    $billing_form = $utils_payment->getBillingDataForm($product_type, $business_unit) ?? $billing_data_form;
    $others_config = $utils_payment->getPassNumberDataForm($product_type, $business_unit) ?? $tigo_money_form;
    $forms = [$billing_form, $others_config];

    $config = [
      'isValidTigoMoneyAccount' => $response_data['validTigomoneyAccount']['value'],
      'confirmation' => $purchase_summary,
      'forms' => $forms,
    ];
    if ($this->tokenAuthorization->isHe()) {
      $config['message'] = $this->config['he_otp']['flow']['message'];
      $config['actions']['messageButton'] = [
        'label' => $this->config['he_otp']['flow']['button'],
        'show' => TRUE,
        'type' => 'button',
      ];
    }
    return $config;
  }

}
