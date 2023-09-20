<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway_tigomoney\Services\v2_0\UtilsServiceTigomoney;

/**
 * Class UtilsServiceTmInvoices for paymentGateway.
 */
class UtilsServiceTmInvoicesBo extends UtilsServiceTigomoney {

  /**
   * Get the body of the payment.
   *
   * @params $business_unit, $product_type, $id_type, $id, $purchaseorder_id, $params, $add_details[]
   */
  public function getBodyPayment($business_unit, $product_type, $id_type, $id, $purchaseorder_id, $params, $add_details = []) {
    $additional_data = [];
    $update_required_add_data = FALSE;
    $purchaseorder_id = $this->transactions->decryptId($purchaseorder_id);
    $config_value_default = (object) $this->tokenAuthorization->getConvergentPaymentGatewaySettings('fields_default_values');
    $name_default = $config_value_default->name["send_default_value_name"] ? $config_value_default->name["name_default_value"] . ' ' . $config_value_default->name["last_name_default_value"] : '';
    $email_default = $config_value_default->email["send_default_value_email"] ? $config_value_default->email["email_default_value"] : '';
    $user_name_payment = $this->tokenAuthorization->getGivenNameUser() . " " . $this->tokenAuthorization->getFirstNameUser();
    $user_name_payment = ($user_name_payment != ' ') ? $user_name_payment : $name_default;
    $user_name_payment = $this->clearString($user_name_payment);
    $user_name = $this->getNameAndLastname($user_name_payment);
    $params = array_map('trim', $params);
    $condition[] = ['id', $purchaseorder_id, '='];
    $condition[] = ['uuid', md5("null@cybersource.com"), '='];
    $condition[] = ['accountId', $id, '='];
    $condition[] = ['accountType', $business_unit, '='];
    $data_transaction = $this->transactions->getTransactionById(NULL, $condition);
    $email = $this->getEmail($params);
    if (empty($email)) {
      $email = $email_default;
    }
    if (!isset($data_transaction->accountNumber)) {
      $this->sendException(t('La transacción no es válida'), 400, NULL);
    }
    $config_app = $this->getConfigPayment($product_type, 'configuration_app', $business_unit);
    if ((strlen($id) < 8)) {
      $params['phoneNumber'] = $this->cutOraddPhone($id);
    }
    $device_id = (isset($params['deviceId'])) ? $params['deviceId'] : $this->getDeviceId($params['uuid'], $params['userAgent']);
    $body = [
      'accountNumber' => $data_transaction->accountNumber,
      'accountType' => $config_app->setting_app_payment['typePay'],
      'deviceId' => $device_id,
      'applicationName' => $config_app->setting_app_payment['applicationName'],
      'purchaseOrderId' => $purchaseorder_id,
      'paymentChannel' => $config_app->setting_app_payment['paymentChannel'],
      'phoneNumber' => isset($params['phoneNumber']) ? $params['phoneNumber'] : $data_transaction->accountId,
      'customerIpAddress' => $params['customerIpAddress'],
      'customerName' => $user_name_payment,
      'updatePaymentSeparately' => FALSE,
      'email' => $email,
      'createPaymentToken' => FALSE,
      'paymentCurrencyCode' => $config_app->setting_app_payment['currency'],
      'paymentAmount' => $data_transaction->amount,
      'productReference' => $data_transaction->numberReference,
    ];
    $config_value_default = (object) $this->tokenAuthorization->getConvergentPaymentGatewaySettings('fields_default_values');
    // Add values to pay with  new card.
    $body['billToAddress'] = [
      'firstName' => $user_name['firstName'],
      'lastName' => $user_name['lastName'],
      'country' => $config_value_default->address['payment_country'],
      'city' => $config_value_default->address['payment_city'],
      'street' => $config_value_default->address['address_default_value'],
      'postalCode' => $config_value_default->address['payment_postal_code'],
      'state' => $config_value_default->address['payment_state'],
      'email' => $email,
    ];
    if (isset($add_details['billToAddress']['street'])) {
      $body['billToAddress']['street'] = $add_details['billToAddress']['street'];
      unset($add_details['billToAddress']);
    }
    elseif (isset($params['street']) && $params['street'] != '') {
      $body['billToAddress']['street'] = $params['street'];
    }
    foreach ($add_details as $key => $value) {
      $body[$key] = $value;
    }
    if (isset($data_transaction->additionalData)) {
      $additional_data = unserialize($data_transaction->additionalData);
    }
    if (isset($params['paymentMethodName']) && ($params['paymentMethodName'] === 'tigoMoney' ||
        $params['paymentMethodName'] === 'tigoMoney_Inv')) {
      $cvv = $this->getPinTigomoney($params);
      if ($cvv === FALSE) {
        $this->sendException(t('El PIN de la línea es requerido.'), 400, NULL);
      }
    }
    else {
      $cvv = $this->getTokenTigomoney($params, []);
      if ($cvv === FALSE) {
        $this->sendException(t('El token de Tigomoney es requerido.'), 400, NULL);
      }
    }
    if (isset($params['paymentMethodName'])) {
      unset($params['paymentMethodName']);
    }
    if ((strlen($additional_data['payerAccount']) < 8)) {
      $additional_data['payerAccount'] = $this->cutOraddPhone($additional_data['payerAccount']);
    }
    $body['creditCardDetails'] = [
      'cvv' => $cvv,
      'accountNumber' => isset($additional_data['payerAccount']) ? $additional_data['payerAccount'] : $params['phoneNumber'],
    ];
    if ($body['productReference'] != 0) {
      $update_required_add_data = TRUE;
      if (!isset($additional_data)) {
        $additional_data = new \stdClass();
      }
      if (is_object($additional_data)) {
        $additional_data->productReference = $body['productReference'];
      }
      else {
        $additional_data['productReference'] = $body['productReference'];
      }
    }
    if ($update_required_add_data) {
      $this->transactions->updateDataTransaction($purchaseorder_id, ['additionalData' => serialize($additional_data)]);
    }
    if (!isset($body['purchaseDetails'])) {
      if (!isset($add_details['multipleAccountsDetail'])) {
        $body['purchaseDetails'] = [];
      }
    }
    // Add params nameValuePairList.
    if ($config_app->addData['value']) {
      $body['nameValuePairList'][] = [
        'name' => 'PARAM1',
        'value' => $config_app->addData['paymentCollectorId'] ?? 292,
      ];
      $body['nameValuePairList'][] = [
        'name' => 'PARAM2',
        'value' => $config_app->addData['paymentTransaction'] ?? 695,
      ];
    }
    return $body;
  }

  /**
   * Return the formatted period for show text to number
   */
  public function getFormattedPeriodNumber($period) {
    $period_data = explode(" ", $period);
    $period_month = $period_data[0];
    $period_month = str_replace([
      'Enero',
      'Febrero',
      'Marzo',
      'Abril',
      'Mayo',
      'Junio',
      'Julio',
      'Agosto',
      'Septiembre',
      'Octubre',
      'Noviembre',
      'Diciembre',
    ], [
      '01',
      '02',
      '03',
      '04',
      '05',
      '06',
      '07',
      '08',
      '09',
      '10',
      '11',
      '12',
    ], $period_month
    );
    $period = $period_data[2].$period_month;
    return $period;
  }

}
