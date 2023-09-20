<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

class QrExpressRestLogicBo {

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $qrService;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $transactions;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $utilsPayment;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $qrRestLogicService;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $configApp;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $qrPayment = "qr_payment";

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $params;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $balance;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $zeroRateLogic;


  /**
   * {@inheritdoc}
   */
  public function __construct($qrService, $transactions ,$utilsPayment, $qrRestLogicService) {
    $this->qrService = $qrService;
    $this->transactions = $transactions;
    $this->utilsPayment = $utilsPayment;
    $this->qrRestLogicService = $qrRestLogicService;
    $this->zeroRateLogic = \Drupal::service('oneapp_convergent_payment_gateway_qr.v2_0.rate_zero_qr_rest_logic');
  }

  /**
   * Get config of block
   */
  public function setConfig($config_block) {
    $this->configBlock = $config_block;
    return $this;
  }

  /**
   * start transaction and generate url QR
   *
   * @param [type] $id
   * @param [type] $id_type
   * @param [type] $business_unit
   * @param [type] $product_type
   * @param [type] $params
   * @return array
   */
  public function generateCodeQR($id, $id_type, $business_unit, $product_type, $params) {
    $this->params = $params;
    $this->params["business_unit"] = $business_unit;
    $this->params["id_type"] = $id_type;
    $account_number = $id;
    $transaction = $this->initPayment($id, $account_number, $id_type, $business_unit, $product_type);
    if (empty($transaction['transactionExist'])) {
      $data_payment = $this->updatePayment($id, $account_number, $id_type, $business_unit, $product_type, $transaction);
    } else {

      $data_payment = $this->getData($id, $transaction['transactionId'], $business_unit);
    }

    $data = array_merge($transaction, $data_payment);
    return $this->getFormat($data);

  }

  /**
   * initialize the transaction in the DB
   *
   * @param $id
   * @param $account_number
   * @param $id_type
   * @param $business_unit
   * @param $product_type
   * @return array
   */
  public function initPayment($id, $account_number, $id_type, $business_unit, $product_type) {
    $this->validAmount($id, $id_type, $business_unit, $product_type);
    $card_brand = t('QR Simple');

    $fields = [
      'uuid' => md5($this->params["email"]),
      'cardBrand' => $card_brand,
      'accountId' => $id,
      'accountNumber' => ($business_unit == "mobile" ? $this->balance["accountNumber"] : $account_number),
      'accountType' => $business_unit,
      'productType' => $product_type,
      'amount' => $this->params["amount"],
      'numberReference' => 0,
      'accessType' => "HE-OTP"
    ];

    $transaction_exist = $this->getDataId($id, $business_unit ,$account_number);
    if (!empty($transaction_exist)) {
      $purchaseorder_id = $this->transactions->encryptId($transaction_exist);
      $transaction_id = $this->transactions->decryptId($purchaseorder_id);
    }
    else {
      $transaction_id = $this->transactions->initTransaction($fields, $product_type);
      $purchaseorder_id = $this->transactions->encryptId($transaction_id);
    }

    return [
      'purchaseorderId' => $purchaseorder_id,
      'transactionId' => $transaction_id,
      'transactionExist' => $transaction_exist,
    ];
  }

  /**
   * update the transaction and generate the QR code
   *
   * @param $id
   * @param $account_number
   * @param $id_type
   * @param $business_unit
   * @param $product_type
   * @param $transaction
   * @return array
   * @throws \Exception
   */
  public function updatePayment($id, $account_number, $id_type, $business_unit, $product_type, $transaction) {
    $config_app = $this->getConfigApp($business_unit, $this->qrPayment);
    $body = $this->generateBody($id, $account_number, $config_app, $transaction);
    $this->params['apiHost'] = $config_app["api_path"];
    $this->params['aws_service'] = $config_app["aws_service"];
    $this->params['uuid'] = md5($this->params["email"]);
    if ($this->params['isMultipay']) {
      $body['multipleAccountsDetail'] = $this->qrRestLogicService->getMultiBalance($id, $id_type, $business_unit);
      if (is_array($this->params["period"])) {
        $accounts = [];
        foreach ($body['multipleAccountsDetail'] as $key => $multipleAccount) {
          if ($multipleAccount["productReference"] == $this->params["multipleAccounts"][$key]) {
            $accounts[] = $multipleAccount;
          }
        }
        $body['multipleAccountsDetail'] = $accounts;
      }
    }
    try {
      $response = $this->qrService->generate($this->params, $body, $this->params['isMultipay']);
    }catch (\Exception $e) {
      throw new \Exception($this->configBlock["messages"]["error"]);
    }

    $response = $response ?? new \stdClass;
    $qr_url = $response->body->paymentInstruction[0]->payment_instructions[0]->value ?? '';
    $order_id = $response->body->orderId ?? '';
    $order_body = $order_id->body ?? '';

    $additional_data = [
      'url' => $this->zeroRateLogic->getParseImageByUrl($qr_url, $business_unit, $product_type, $this->params['paymentMethod'])
    ];

    $fields = [
      'stateOrder' => "ORDER_IN_PROGRESS",
      'changed' => time(),
      'orderId' => $response->body->orderId,
      'transactionId' => $response->body->transactionId,
      'additionalData' => serialize($this->makeAdditionalData($id))
    ];

    $this->transactions->updateDataTransaction($transaction["transactionId"], $fields);

    $body_logs = $body;
    $body_logs['creditCardDetails'] = [];
    $message = 'Qr Payment';

    $fields_log = [
      'purchaseOrderId' => $transaction["transactionId"],
      'message' => $message,
      'codeStatus' => 200,
      'operation' => $this->transactions::CREATED_ORDER,
      'description' => "Back office response: \n" . json_encode($order_body, JSON_PRETTY_PRINT) .
        "\nBody: \n" . json_encode($body_logs, JSON_PRETTY_PRINT),
      'type' => $product_type,
    ];
    $this->transactions->addLog($fields_log);
    return $additional_data;
  }

  /**
   * @return array
   */
  public function makeAdditionalData($id) {
    $utils_express_bo = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.util_service');
    $payment_method = 'qrPayment';
    return [
      'qr' => TRUE,
      'accountNumber' => ($this->params["business_unit"] == "mobile" ?? $id),
      'paymentMethod' => $payment_method,
      'period' => $utils_express_bo->getFormatPeriods($this->validatePeriod())
    ];
  }

  public function validatePeriod() {
    $periods = "";
    if ($this->params["isMultipay"]) {
      $result = [];
      foreach ($this->balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"] as $key => $invoices) {
        if ($this->params["period"][$key]) {
          $result[] = $invoices["productReference"];
        }
      }
      $periods = implode(",", $result);
    } elseif ($this->params["business_unit"] == "home") {
      $periods = $this->params["period"];
    } elseif ($this->params["business_unit"] == "mobile" && !$this->params["isMultipay"]) {
      $periods = (isset($this->balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"])
        ? $this->balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"][0]["productReference"]
        : $this->balance["additionalData"]["fieldsForPaymentBody"]["productReference"]) ;
    }

    return $periods;
  }

  /**
   * create the body structure of the QR service for invoices
   *
   * @param $id
   * @param $account_number
   * @param $config_app
   * @param $transaction
   * @return array
   */
  public function generateBody($id, $account_number, $config_app, $transaction) {
    $oneapp_utils = \Drupal::service('oneapp.utils');
    $product_reference = (!$this->params["isMultipay"] ?
      $this->params["period"] :
      $this->balance["additionalData"]["fieldsForPaymentBody"]["productReference"] ?? "0"
    );
    $application_name = $config_app["applicationName"];
    $payment_channel = $config_app["paymentChannel"];

    $body = [
      "accountNumber" => ($this->params["business_unit"] == "mobile" ? $this->balance["accountNumber"] : $account_number),
      "accountType" => $config_app["typePay"],
      "deviceId" => $this->qrRestLogicService->getDevice($this->params),
      "applicationName" => $application_name,
      "paymentChannel" => $payment_channel,
      "phoneNumber" => $this->utilsPayment->cutOraddPhone($id),
      "customerIpAddress" => $oneapp_utils->getUserIp(),
      "customerName" => "",
      "purchaseOrderId" => $transaction["transactionId"],
      "asyncPayment" => [
        "responseUrl" => $config_app["redirect"],
      ],
      "billToAddress" => $this->getBillingToAddress(),
      "email" => $this->params['email'],
      "deviceFingerprintId" => $this->getDeviceFingerprint($transaction["transactionId"]),
      "paymentCurrencyCode" => $config_app["currency"],
      "paymentAmount" => isset($this->params['amount']) ? (string) $this->params['amount'] : "0",
      "productReference" => (count($this->balance["additionalData"]["fieldsForPaymentBody"]["productReference"]) == 1 ? $this->balance["additionalData"]["fieldsForPaymentBody"]["productReference"] : $product_reference) ,
      "nameValuePairList" => $this->getNameValuePairList($id,  $this->params["business_unit"] == "home" ? $account_number : $this->balance["contractId"]),
    ];

    if (!$this->params['isMultipay']) {
      $body['purchaseDetails'] = [];
    }

    return $body;
  }

  /**
   * get info of the billing
   *
   * @return array
   */
  public function getBillingToAddress() {
    $config_value_default = (object) \Drupal::config('oneapp_convergent_payment_gateway.config')->get("fields_default_values");

    return [
      "firstName" => $config_value_default->name["send_default_value_name"] ? $config_value_default->name["name_default_value"] : '',
      "lastName" => $config_value_default->name["send_default_value_name"] ? $config_value_default->name["last_name_default_value"] : '',
      "country" => $config_value_default->address["send_default_value_address"] ? $config_value_default->address["payment_country"] : '',
      "city" => $config_value_default->address["send_default_value_address"] ? $config_value_default->address["payment_city"] : '',
      "street" => $config_value_default->address["send_default_value_address"] ? $config_value_default->address["address_default_value"] : '',
      "postalCode" => $config_value_default->address["send_default_value_address"] ? $config_value_default->address["payment_postal_code"] : '',
      "state" => $config_value_default->address["send_default_value_address"] ? $config_value_default->address["payment_state"] : '',
      "email" => $config_value_default->address["send_default_value_address"] ? $this->params["email"] : ''
    ];
  }

  public function getDeviceFingerprint($transactionId) {
    $config_app = (object) $this->getConfigApp($this->params["business_unit"], "configuration_app");
    $country = $config_app->setting_app_payment["country"];
    if (isset($config_app->setting_app_payment["payment_gateway"]) && $config_app->setting_app_payment["payment_gateway"] == 'pay_u') {
      $fingerprint_id = 'MTW' . $country . '' . $transactionId;
      $data_transaction = $this->transactions->getDataTransaction("", $transactionId);
      $fingerprint_id = md5($fingerprint_id . $data_transaction->created);
    } else {
      $fingerprint_id = 'MTW' . $country . '' . $transactionId;
    }
    return $fingerprint_id;
  }

  /**
   * get name value pair list
   *
   * @param [type] $id
   * @param [type] $account_number
   * @return array
   */
  public function getNameValuePairList($id, $account_number) {
    $months = (!$this->params["isMultipay"] ? $this->formatMonthByPeriod($this->params["period"]) : $this->getMonths($id));
    $type = $this->params["business_unit"] == "mobile" ? 'Movil' : 'Hogar';
    if (is_array($this->params["period"])) {
      $months = [];
      foreach ($this->params["multipleAccounts"] as $key => $periods) {
        $months[] = $this->formatMonthByPeriod($periods);
        $value = "Serv. {$type},Factura," . $account_number . "," . implode(",", $months);
      }
    } else {
      if (count($this->balance["additionalData"]["fieldsForPaymentBody"]["productReference"]) == 1) {
        $months = $this->formatMonthByPeriod($this->balance["additionalData"]["fieldsForPaymentBody"]["productReference"]);
      }
      $value = "Serv. {$type},Factura," . $account_number . ",". $months;
    }
    $value = substr($value, 0, 60);
    return [
      [
        "name" => "DESCRIPTION",
        "value" => $value
      ]
    ];
  }

  /**
   * get months
   *
   * @param [type] $id
   * @param [type] $account_number
   * @return string
   */
  public function getMonths($id) {
    $balance = $this->qrRestLogicService->getBalance($id, $this->params["id_type"], $this->params["business_unit"]);
    $months = "";
    if (!isset($balance["pendingInvoices"])) {
      $balance["pendingInvoices"][0] = new \stdClass;
      $balance["pendingInvoices"][0]->period = $balance["additionalData"]["fieldsForPaymentBody"]["productReference"];
    }
    foreach ($balance["pendingInvoices"] as $pendingInvoice) {
      $date = $this->getDate($pendingInvoice);
      if (!empty($date)) {
        $month = $this->params["business_unit"] == "mobile" ?
          $this->getMonthByPeriod($date) : $this->qrRestLogicService->getMonthHome($date, 'mes');
        $this->period[] = $this->params["business_unit"] == "mobile" ?
          $this->getMonthByPeriod($date) : $this->qrRestLogicService->getMonthHome($date, 'mes_ano_largo');
        $months = $months . "," . $month;
      }
      if ($this->params["isPartialPayment"]) {
        return $months;
      }
    }

    return $months;
  }

  /**
   * @param $period
   * @return string
   */
  public function formatMonthByPeriod($period) {
    $formatPeriod = str_split($period, 4);
    return $this->getMonthByPeriod($formatPeriod[0]. "-". $formatPeriod[1]);
  }

  /**
   * @param $date
   * @param $type
   * @return string
   */
  public function getMonthByPeriod($date) {
    $month = strftime('%b',strtotime($date));

    $months = array(
      'Jan' => 'ENE',
      'Feb' => 'FEB',
      'Mar' => 'MAR',
      'Apr' => 'ABR',
      'May' => 'MAY',
      'Jun' => 'JUN',
      'Jul' => 'JUL',
      'Aug' => 'AGO',
      'Sep' => 'SEP',
      'Oct' => 'OCT',
      'Nov' => 'NOV',
      'Dec' => 'DIC'
    );

    return $months[$month];
  }

  /**
   * get date
   *
   * @param [type] $invoice
   * @return get string
   */
  public function getDate($invoice) {
    if ($this->params["business_unit"] == "mobile") {
      return $invoice->billingPeriod->startDateTime ?? '';
    }
    else {
      return $invoice->period ?? '';
    }
  }

  /**
   * return id if exist
   */
  public function getDataId($id, $business_unit, $account_number) {
    $config_app = $this->getConfigApp($business_unit, $this->qrPayment);
    $number = !empty($account_number) ? $account_number : $id;
    $data_payments = $this->transactions->getDataForAccountNumberValidAmount($number, $this->params["amount"], 'ORDER_IN_PROGRESS');
    $time = $config_app["time"] ?? 20;
    foreach ($data_payments as $data) {
      $additional_data = unserialize($data->additionalData);
      if (is_array($additional_data) and isset($additional_data["qr"]) and $additional_data["qr"]) {
        $date_payment = $data->created;
        $now = strtotime("now");
        $min = round(abs($now - $date_payment) / 60);
        if ($min <= $time) {
          return $data->id;
        }
      }
    }
    return '';
  }

  /**
   * get config of payment gateway
   *
   * @return object
   */
  public function getConfigApp($businessUnit, $data_type, $payment_type = "_invoices") {
    $this->configApp = \Drupal::config("oneapp.payment_gateway.{$businessUnit}{$payment_type}.config")->get($data_type);
    return $this->configApp["setting_qr_payment_express"];
  }


  /**
   * Valid fields.
   */
  public function validAmount($id, $id_type, $business_unit, $product_type = 'invoice') {
    $config_name = "oneapp_{$business_unit}.config";
    $payment = \Drupal::config($config_name)->getRawData()['payment'];

    if (stripos($product_type, 'invoice') !== FALSE) {
      $this->balance = $this->qrRestLogicService->getBalance($id, $id_type, $business_unit);

      if (isset($this->params["isPartialPayment"]) && !$this->params["isPartialPayment"]) {
        $this->params['amount'] = $this->balance["dueAmount"];
      }
      if ($payment["minimumAmount"] >= $this->params['amount']) {
        throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
      }
      if ($this->balance["dueAmount"] != $this->params['amount'] && $this->params['isPartialPayment']) {
        throw new \Exception("El monto es incorrecto");
      }
      if ($this->balance["dueAmount"] <= 0 && $this->params['isPartialPayment']) {
        throw new \Exception("No se pueden pagar facturas con deuda 0 o con un valor negativo");
      }
      if (!$this->params["isMultipay"]) {
        if ($this->params["period"] != $this->balance["pendingInvoices"][0]->period) {
          throw new \Exception("el periodo a pagar no es valido");
        }else{
          if($this->params["business_unit"] == "mobile"){
            $this->params['period'] = $this->balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"][0]["productReference"];
          }
          $this->params['amount'] = $this->balance["pendingInvoices"][0]->dueAmount;
        }
      }
      if ($this->params["isMultipay"] && is_array($this->params["period"])) {
        $totalAmount = [];
        $multipleAccounts = [];
        foreach ($this->balance["pendingInvoices"] as $key => $invoices) {
          if (in_array($this->balance["pendingInvoices"][$key]->period, $this->params["period"])) {
            $multipleAccounts[] = $this->balance["additionalData"]["fieldsForPaymentBody"]["multipleAccountsDetail"][$key]["productReference"];
            $totalAmount[] = $invoices->dueAmount;
          }
        }

        $this->params['amount'] = array_sum($totalAmount);
        $this->params["multipleAccounts"] = $multipleAccounts;
      }
    }
    else {
      if ($this->params['amount'] < ($payment['minimumAmount'] ?? 0)) {
        throw new \Exception('Amount cannot be less then ' . $payment['minimumAmount']);
      }
    }
  }

  /**
   * verify if exits and get data of database
   */
  public function getData($id, $transaction_id, $business_unit) {
    $data_payments = $this->transactions->getTransactionById($transaction_id, []);
    $get_status = $this->qrService->getStatus($this->getConfigApp($business_unit, $this->qrPayment), $data_payments);
    if (!empty($get_status)) {
      $fields = [
        'changed' => time(),
      ];
      $this->transactions->updateDataTransaction($data_payments->id, $fields);
      return $get_status;
    }
    return [
      'url' => '',
    ];
  }

  /**
   * get format of the dates
   *
   * @param [type] $data
   * @return array
   */
  public function getFormat($data) {
    $response = [];
    foreach ($this->configBlock["fields"] as $key => $field) {
      $response[$key] = [
        'label' => $field['label'] ?? '',
        'show' => isset($field["show"]) && $field["show"] ? TRUE : FALSE,
        'value' => $data[$key] ?? '',
        'formattedValue' => isset($data[$key]) ? (string) $data[$key] : '',
      ];
    }
    return $response;
  }

  /**
   * get buttons in the config
   *
   * @return array
   */
  public function getActions($params) {
    $response = [];
    $device_id = isset($params["deviceId"]) ? $params["deviceId"] : '';
    foreach ($this->configBlock["actions"] as $key => &$actions) {
      if ($this->qrRestLogicService->validBrowser($device_id, $key)) {
        $actions["show"] = FALSE;
      }

      $response[$actions["key"]] = [
        'label' => isset($actions["label"]) ? $actions["label"] : '',
        'show' => isset($actions["show"]) && $actions["show"] ? TRUE : FALSE,
        'type' => isset($actions["type"]) ? $actions["type"] : '',
        'url' => isset($actions["url"]) ? $actions["url"] : '',
      ];
    }

    return $response;
  }

  /**
   * getDefaultsConfig function
   *
   * @return array
   */
  public function getDefaultsConfig() {
    $title_value = (!empty($this->configBlock['label'])) ? t($this->configBlock['label']) : '';
    $title_show = (!empty($this->configBlock['label_display'])) ? $this->configBlock['label_display'] : '';
    $description = (!empty($this->configBlock['description'])) ? t($this->configBlock['description']) : '';
    $footer = (!empty($this->configBlock['footer'])) ? t($this->configBlock['footer']) : '';

    return [
      'title' => [
        'value' => $title_value,
        'show' => ($title_show === 'visible') ? TRUE : FALSE,
      ],
      'description' => $description,
      'footer' => $footer,
    ];
  }

  /**
   * get messages in the config
   *
   * @return mixed
   */
  public function getMessages() {
    $success = isset($this->configBlock["messages"]['success']) ? $this->configBlock["messages"]['success'] : '';
    $response["success"] = $success;
    return $response;
  }
}
