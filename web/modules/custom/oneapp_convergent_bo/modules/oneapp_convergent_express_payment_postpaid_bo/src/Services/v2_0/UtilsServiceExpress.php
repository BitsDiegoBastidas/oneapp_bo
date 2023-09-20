<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp_convergent_payment_gateway\Services\v2_0\UtilsService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Express Utils.
 */
class UtilsServiceExpress extends UtilsService {

  /**
   * Devuelve formulario de facturación.
   */
  public function getBillingDataForm($productype, $business_unit, $uuid = NULL) {
    $config = $this->getConfigPayment($productype, 'billing_form', $business_unit);
    if (isset($config->show) && $config->show) {
      $result = $this->getBillingData($uuid);
      foreach ($config as $field => $value) {
        if (is_array($value)) {
          $form[$field] = [
            'show' => (bool) $value['show'],
            'label' => $value['label'],
            'placeholder' => $value['placeholder'],
            'type' => "text",
            'format' => "",
            'value' => ($result !== FALSE) ? $result[$field] : $value['default'],
            'validations' => [
              'required' => (bool) $value['required'],
              'minLength' => $value['minlength'],
              'maxLength' => $value['maxlength'],
            ],
          ];
        }
      }
      return ['billingDataForm' => $form];
    }
    return NULL;
  }

  /**
   * Generando el formulario.
   */
  public function getFormPayment($type_form, $get_default_values = FALSE, $payment_method = 'creditCard') {
    $config_forms = $this->tokenAuthorization->getConvergentPaymentGatewaySettings('payment_forms');
    $config_allowed_credit_cards = $this->tokenAuthorization->getConvergentPaymentGatewaySettings('credit_card_types');
    $form = [];

    foreach ($config_forms["newCardForm"] as $field => $values) {
      $show_field = $this->showFields($values, $type_form);
      $show_field_email_required = $this->showFieldsEmailRequired($values, $type_form);
      unset($values["disableOnForm"]);
      unset($values["showPrepaid"]);
      unset($values["showHybrid"]);
      unset($values["showPostpaid"]);
      if ($get_default_values == FALSE) {
        unset($values["defaultValue"]);
        $values["value"] = "";
      }
      if ((isset($values["type"])) && ($values["type"] != "select")) {
        unset($values["options"]);
        unset($config_forms["newCardForm"][$field]["options"]);
      }
      foreach ($values as $index => $item) {
        switch ($index) {
          case 'show':
            $form['newCardForm'][$field][$index] = $show_field ? TRUE : FALSE;
            break;

          case 'options':
            if ($values["type"] == "select") {
              $options = [];
              $lines = explode(PHP_EOL, $item);
              foreach ($lines as $key => $value) {
                $form['newCardForm'][$field][$index] = $item;
                $options[$key]['value'] = substr($value, 0, strpos($value, '|'));
                $options[$key]['label'] = substr(trim($value), strpos($value, '|') + 1);
              }
              $form['newCardForm'][$field][$index] = $options;
            }
            break;

          case 'required':
            $form['newCardForm'][$field]['validations'][$index] = $show_field_email_required ? TRUE : FALSE;
            break;

          case 'minLength':
            $form['newCardForm'][$field]['validations'][$index] = $item;
            break;

          case 'maxLength':
            $form['newCardForm'][$field]['validations'][$index] = $item;
            break;

          case 'details':
            if (!empty($item)) {
              $form['newCardForm'][$field]['details'] = $item;
            }
            break;

          case 'pattern':
            $form['newCardForm'][$field]['validations'][$index] = $item;
            break;

          case 'error_message_required':
            $form['newCardForm'][$field]['error_message'][$index] = $item;
            break;

          case 'error_message_validation':
            $form['newCardForm'][$field]['error_message'][$index] = $item;
            break;

          default:
            $form['newCardForm'][$field][$index] = $item;
            break;
        }
        if ($field == 'numberCard') {
          $allowed_credit_cards = [];
          foreach ($config_allowed_credit_cards['allowed_credit_cards'] as $value) {
            if ($value != '0') {
              $allowed_credit_cards[] = $value;
            }
          }
          $form['newCardForm'][$field]['validations']['allowedCreditCards'] = $allowed_credit_cards;
        }
      }
    }
    if ($this->tokenAuthorization->isHe()) {
      $form["newCardForm"]["rememberCard"]["show"] = FALSE;
      $types_transactions = ['packets', 'topups', 'autopackets'];
      if (isset($form["newCardForm"]["email"]["hideHe"]) && $form["newCardForm"]["email"]["hideHe"] &&
        in_array($type_form, $types_transactions)) {
        $form["newCardForm"]["email"]["show"] = FALSE;
        $form["newCardForm"]["email"]["validations"]["required"] = FALSE;
      }
      else {
        $form["newCardForm"]["email"]["show"] = TRUE;
      }
      if (isset($form["newCardForm"]["enrollMe"]["hideHe"]) && $form["newCardForm"]["enrollMe"]["hideHe"] &&
        $type_form == 'invoices') {
        $form["newCardForm"]["enrollMe"]["show"] = FALSE;
        $form["newCardForm"]["enrollMe"]["validations"]["required"] = FALSE;
      }
      else {
        $form["newCardForm"]["enrollMe"]["show"] = TRUE;
      }
    }
    if (!$this->tokenAuthorization->isHe() && empty($this->tokenAuthorization->getEmail())) {
      $types_transactions = ['packets', 'topups', 'autopackets'];
      if (isset($form["newCardForm"]["email"]["hidePP"]) && $form["newCardForm"]["email"]["hidePP"] &&
        in_array($type_form, $types_transactions)) {
        $form["newCardForm"]["email"]["show"] = FALSE;
        $form["newCardForm"]["email"]["validations"]["required"] = FALSE;
      }
      else {
        $form["newCardForm"]["email"]["show"] = TRUE;
      }
      if (isset($form["newCardForm"]["enrollMe"]["hideHe"]) && $form["newCardForm"]["enrollMe"]["hideHe"] &&
        $type_form == 'invoices') {
        $form["newCardForm"]["enrollMe"]["show"] = FALSE;
        $form["newCardForm"]["enrollMe"]["validations"]["required"] = FALSE;
      }
      else {
        $form["newCardForm"]["enrollMe"]["show"] = TRUE;
      }
    }
    if (isset($form["newCardForm"]["enrollMe"]["show"]) && $form["newCardForm"]["enrollMe"]["show"] &&
      $type_form == 'invoices') {
      $form["newCardForm"]["enrollMe"]["show"] = \Drupal::requestStack()->getCurrentRequest()->get('isB2b') ? FALSE : TRUE;
    }
    return $form;
  }

  /**
   * Obtiene el body del pago.
   */
  public function getBodyPayment($business_unit, $product_type, $id_type, $id, $purchase_order_id, $params, $add_details = []) {
    $purchase_order_id = $this->transactions->decryptId($purchase_order_id);
    $config_fac = \Drupal::config("oneapp.payment_gateway.mobile_topups.config")->getRawData();
    $config_value_default = (object) $this->tokenAuthorization->getConvergentPaymentGatewaySettings('fields_default_values');
    $name_default = $config_value_default->name["send_default_value_name"] ? $config_value_default->name["name_default_value"] . ' ' . $config_value_default->name["last_name_default_value"] : '';
    $email_default = $config_value_default->email["send_default_value_email"] ? $config_value_default->email["email_default_value"] : '';
    $params['email'] = !empty($params['email']) ? $params['email'] : $email_default;
    if (isset($config_fac['billing_form']['fullname']['overwriteName']) && $config_fac['billing_form']['fullname']['overwriteName'] && !empty($params["billingData"]["fullname"])) {
      $user_name_payment = $params["billingData"]["fullname"];
    }
    else {
      $user_name_payment = isset($params['tokenizedCardId']) ? $params['customerNameToken'] : ($params['customerName'] != '' ? (string) $params['customerName'] : $config_fac["billing_form"]["fullname"]["default"]);
      $user_name_payment = $this->clearString($user_name_payment);
    }
    $user_name_payment = empty($user_name_payment) ? $name_default : $user_name_payment;
    $user_name_billing = !empty($params["billingData"]["fullname"]) ? $params["billingData"]["fullname"] : $user_name_payment;
    $user_name = $this->getNameAndLastname($user_name_billing);
    $number_card = $this->getNumberCard($params['numberCard'] ?? '');
    foreach ($params as &$param) {
      if (!is_array($param)) {
        $param = trim($param);
      }
    }
    //$params = array_map('trim', $params);
    if (isset($params['tokenizedCardId']) && $this->tokenAuthorization->isHe()) {
      $this->sendException(t('Si desea realizar el pagos con una tarjeta almacenada debe iniciar sesión'), Response::HTTP_BAD_REQUEST, NULL);
    }
    $config_fields = $this->validateConfigPaymentForms($params, $product_type);
    $condition[] = ['id', $purchase_order_id, '='];
    $condition[] = ['uuid', $this->tokenAuthorization->getMailUuid($id), '='];
    $condition[] = ['accountId', $id, '='];
    $condition[] = ['accountType', $business_unit, '='];
    $data_transaction = $this->transactions->getTransactionById(NULL, $condition);

    if (!$data_transaction->accountNumber) {
      $this->sendException(t('La transacción no esta válida'), Response::HTTP_BAD_REQUEST, NULL);
    }

    $config_app = $this->getConfigPayment($product_type, 'configuration_app', $business_unit);
    if (isset($params['tokenizedCardId']) || !$config_fields->newCardForm['phone']['show']) {
      $params['phoneNumber'] = $this->cutOraddPhone($id);
    }
    $device_id = (isset($params['deviceId'])) ? $params['deviceId'] : $this->getDeviceId($params['uuid'], $params['userAgent']);
    if (!empty($device_id)) {
      $device_id = str_replace('_', '-', $device_id);
    }

    $add_country_code = (!empty($config_app->express_payment['add_country_code'])) ? $config_app->express_payment['add_country_code'] : FALSE;
    $account_number = $data_transaction->accountNumber;

    if ($add_country_code) {
      $mobile_utils_service = \Drupal::service('oneapp.mobile.utils');
      $account_number = $mobile_utils_service->modifyMsisdnCountryCode($account_number, TRUE);
    }

    $body = [
      'accountNumber' => $account_number,
      'accountType' => $config_app->express_payment['typePay'],
      'deviceId' => $device_id,
      'deviceFingerprintId' => $this->getDeviceFingerprintforPaymentGateway($purchase_order_id),
      'applicationName' => $config_app->express_payment["applicationName"],
      'purchaseOrderId' => $purchase_order_id,
      'paymentChannel' => $config_app->express_payment['paymentChannel'],
      'phoneNumber' => $params['phoneNumber'],
      'customerIpAddress' => $params['customerIpAddress'],
      'customerName' => $user_name_payment,
      'updatePaymentSeparately' => FALSE,
      'email' => !empty($params['email']) ? $params['email'] : $email_default,
    ];
    $form_key = 'newCardForm';
    if (array_key_exists('paymentMethod', $params)) {
      $form_key = 'asyncForm';
    }
    if (!isset($params["tokenizedCardId"]) && filter_var($config_fields->$form_key["identificationType"]["show"], FILTER_VALIDATE_BOOLEAN)) {
      if ($this->showFields($config_fields->$form_key["identificationType"], $product_type)) {
        if ($form_key == 'asyncForm') {
          $document_type = $this->changeAllowedValuesToArray($config_fields->$form_key["identificationType"]["options"]);
        }
        else {
          foreach ($config_fields->$form_key["identificationType"]["options"] as $key => $field) {
            $document_type[$field['value']] = $field['label'];
          }
        }
        if (isset($params['documentType']) && array_key_exists($params['documentType'], $document_type)) {
          $body['documentType'] = $params['documentType'];
        }
        else {
          $this->sendException(t('El tipo de documento no es válido'), Response::HTTP_BAD_REQUEST, NULL);
        }
      }
    }
    if (!isset($params["tokenizedCardId"]) && filter_var($config_fields->$form_key["identificationNumber"]["show"], FILTER_VALIDATE_BOOLEAN)) {
      if ($this->showFields($config_fields->$form_key["identificationNumber"], $product_type)) {
        if (isset($params['documentNumber']) && !empty($params['documentNumber'])) {
          $body["documentNumber"] = $params['documentNumber'];
        }
        elseif (isset($params['documentId']) && !empty($params['documentId'])) {
          $body["documentNumber"] = $params['documentId'];
        }
        else {
          $this->sendException(t('El número del documento no es válido'), Response::HTTP_BAD_REQUEST, NULL);
        }
      }
    }

    $config_value_default = (object) $this->tokenAuthorization->getConvergentPaymentGatewaySettings('fields_default_values');
    $cvv = isset($config_value_default->card["send_default_value_card"]) && $config_value_default->card["send_default_value_card"] ? $config_value_default->card["cvv"] : '';
    // Add values to pay with  new card.
    if (isset($params['numberCard'])) {
      $body['creditCardDetails'] = [
        'expirationYear' => (string) substr($params['expirationYear'], -4),
        'cvv' => !empty($params['cvv']) ? (string) $params['cvv'] : $cvv,
        'cardType' => (string) $params['cardType'],
        'expirationMonth' => (string) $params['expirationMonth'],
        'accountNumber' => strval($number_card),
      ];
    }
    if (filter_var($config_value_default->address["send_default_value_address"], FILTER_VALIDATE_BOOLEAN)) {
      $params['street'] = $config_value_default->address['address_default_value'];
    }
    else {
      if (isset($params['street'])) {
        $params['street'] = $params['street'];
      }
      elseif (filter_var($config_fields->$form_key["address"]["show"], FILTER_VALIDATE_BOOLEAN) && !isset($params['tokenizedCardId'])) {
        if ($this->showFields($config_fields->$form_key["address"], $product_type)) {
          if (isset($params['street'])) {
            $params['street'] = $params['street'];
          }
          else {
            $this->sendException(t('El campo dirección no es válido'), Response::HTTP_BAD_REQUEST, NULL);
          }
        }
      }
      else {
        $params['street'] = $config_value_default->address['address_default_value'];
      }
    }

    $body['billToAddress'] = [
      'firstName' => $user_name['firstName'],
      'lastName' => $user_name['lastName'],
      'country' => $config_value_default->address['payment_country'],
      'city' => $config_value_default->address['payment_city'],
      'postalCode' => $config_value_default->address['payment_postal_code'],
      'state' => $config_value_default->address['payment_state'],
      'email' => !empty($params['email']) ? $params['email'] : $email_default,
    ];
    if (isset($add_details['billToAddress']['street'])) {
      $body['billToAddress']['street'] = $add_details['billToAddress']['street'];
      unset($add_details['billToAddress']);
    }
    else {
      $body['billToAddress']['street'] = $params['street'];
    }
    $body['paymentCurrencyCode'] = $config_app->setting_app_payment['currency'];
    if (isset($params['numberCard']) && isset($params['createPaymentToken'])) {
      $body['createPaymentToken'] = (!$this->tokenAuthorization->isHe() && $params['createPaymentToken']) ? TRUE : FALSE;
      if (isset($params['enrollMe']) && $params['enrollMe'] == 'true') {
        $body['createPaymentToken'] = TRUE;
      }
    }
    $body['paymentAmount'] = $data_transaction->amount;

    if (isset($params['tokenizedCardId'])) {
      $body['tokenizedCardId'] = (string) $params['tokenizedCardId'];
      $body['cvv'] = (isset($params['cvv']) && !empty($params['cvv'])) ? strval($params['cvv']) : $cvv;
    }

    switch ($config_app->setting_app_payment['payment_gateway']) {
      case 'pay_u':
        // TODO Validar para CO Invoices.
        if ($product_type == 'invoices') {
          $body["purchaseDetails"] = [
            "name" => (string) $data_transaction->numberReference,
            "quantity" => "1",
            "amount" => (string) $data_transaction->amount,
          ];
        }
        break;

      default:
        $body['productReference'] = ((bool)$params["isMultipay"] ? "0": $data_transaction->numberReference);
        break;
    }

    foreach ($add_details as $key => $value) {
      if ($key != "paymentTokenId" && $key != "numberCard") {
        $body[$key] = $value;
      }
    }

    if ($body['productReference'] != 0) {
      if (isset($data_transaction->additionalData)) {
        $additional_data = unserialize($data_transaction->additionalData);
      }
      if (!isset($additional_data)) {
        $additional_data = new \stdClass();
      }
      if (is_object($additional_data)) {
        $additional_data->productReference = $body['productReference'];
      }
      else {
        $additional_data['productReference'] = $body['productReference'];
      }
      if (isset($add_details["numberCard"]) && $add_details["numberCard"]) {
        $additional_data->numberCard = $add_details["numberCard"];
      }
      if (isset($add_details["paymentTokenId"]) && $add_details["paymentTokenId"]) {
        $additional_data->paymentTokenId = $add_details["paymentTokenId"];
      }
      $this->transactions->updateDataTransaction($purchase_order_id, ['additionalData' => serialize($additional_data)]);
    }

    if (!$params["isMultipay"]) {
      if ($business_unit == "mobile" && isset($body["multipleAccountsDetail"])) {
        $body['productReference'] = $body["multipleAccountsDetail"][0]["productReference"];
      } elseif ($business_unit == "home") {
        $body['productReference'] = $params["period"];
      } else {
        $body['productReference'] = $additional_data["productReference"];
      }
      $body['purchaseDetails'] = [];
      unset($body['multipleAccountsDetail']);
    }else{
      $results = [];
      foreach ($body["multipleAccountsDetail"] as $key => $multipleAccount){
        if($business_unit == "mobile"){
          $formatPeriod = explode("-", $params["period"][$key]);
          $params["period"][$key] = $formatPeriod[0] . $formatPeriod[1];
        }
        if($body["multipleAccountsDetail"][$key]["productReference"] == $params["period"][$key]){
          $results[] = $multipleAccount;
        }
      }
      $body['multipleAccountsDetail'] = $results;
      $body['purchaseDetails'] = [];
    }

    return $body;
  }

}
