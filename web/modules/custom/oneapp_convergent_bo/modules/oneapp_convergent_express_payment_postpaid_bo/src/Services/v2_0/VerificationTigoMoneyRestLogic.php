<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;



/**
 * Class VerificationTigoMoneyRestLogic.
 */
class VerificationTigoMoneyRestLogic{

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $configBlock;

  /**
   * Params.
   *
   * @var array
   */
  protected $queryParams;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $utils;
  
  protected $transactions;
  protected $paymentGatewayAsyncRestLogic;
  /**
   * Default configuration.
   *
   * @var mixed
   */
  
  /**
   * {@inheritdoc}
   */
  public function __construct($utils, $utils_express_bo, $transactions, $request_stack, $entity_manager, $paymentgatewayasync_rest_logic) {
    $this->utils = $utils;
    $this->utilsExpressBo = $utils_express_bo;
    $this->transactions = $transactions;
    $this->queryParams = $request_stack->getCurrentRequest()->query->all();
    $this->entityManager = $entity_manager;
    $this->paymentGatewayAsyncRestLogic = $paymentgatewayasync_rest_logic;
  }

  /**
   * Responds to setConfig.
   *
   * @param mixed $configBlock
   *   Config card or default.
   */
  public function setConfig($config_block) {
    $this->configBlock = $config_block;
  }

  /**
   * Return the data formatted.
   *
   * @param mixed $contractOrLine
   *   Endpoint id.
   *
   * @return array
   *   The HTTP response object.
   */
  public function getData($business_unit, $id_type, $contract_id, $purchaseorders, $tigomoney) {
    $product_type="invoices";
    $data_db= $this->getDataDb($business_unit, $product_type, $id_type, $contract_id, $purchaseorders, $tigomoney);
    try {
      $row = [];
      foreach ($this->configBlock["fields"] as $key => $field) {

        switch ($key) {
          
          case "verifyInformation":
            $row[$key]['label'] = $field['label'];
            $row[$key]['show'] = (bool) $field['show'];
            break;
          case "legend":
            $row[$key]['label'] = $field['label'];
            $row[$key]['show'] = (bool) $field['show'];
            break;
          case "verifyDescription":
            $row[$key]['label'] = $field['label'];
            $row[$key]['show'] = (bool) $field['show'];
            break;
          case "invoiceDetails":
            $row[$key]['label'] = $field['label'];
            $row[$key]['show'] = (bool) $field['show'];
            break;
          case "paymentDetails":
            $row[$key]['details'] = $this->parsePaymentDetails($field, $data_db);
            break;
          case "paymentMethods":
            $row[$key]['details'] = $this->parsePaymentMethods($field, $data_db);
            break;
          case "labelPaymentDetails":
            $row['paymentDetails']['label'] = $field['label'];
            $row['paymentDetails']['show'] = (bool) $field['show'];
            break;
          case "labelPaymentMethods":
            $row['paymentMethods']['label'] = $field['label'];
            $row['paymentMethods']['show'] = (bool) $field['show'];
            break;
        
          default:
            break;
        }
      }
      $row = [
        'verifyInformation' => $row['verifyInformation'],
        'legend' => $row['legend'],
        'verifyDescription' => $row['verifyDescription'],
        'invoiceDetails' => [
          'label' => $row['invoiceDetails'],
        ],
        'paymentDetails' => $row['paymentDetails'],
        'paymentMethods' => $row['paymentMethods'],
      ];

      return $row;
    } catch (\Exception $exception) {
      return $exception;
    }
  }

  /**
   * Get config section structure.
   *
   * @return array
   *   Config structure.
   */
  public function getConfig() {
    $config = [
      'actions' => [],
      'termsAndConditions' => [],
      'forms' => []
    ];

    $this->buildActions($config);

    $this->buildTermsAndConditions($config);
    $this->buildGetForms($config);
    return $config;
  }

  /**
   * Get meta section structure.
   *
   * @return array
   *   Meta structure.
   */
  public function getMeta() {
    return [];
  }

  /**
   * Get error state.
   *
   * @return array
   *   Returns empty structure for data section.
   */
  protected function errorState() {
    if ($this->configBlock['config']['messages']['error']) {
      return [
        'message' => $this->configBlock['config']['messages']['error'],
        'flag' => 2,
        'noData' => ['value' => 'empty'],
      ];
    }

    return [];
  }

  /**
   * Build config actions form structure.
   *
   * @param array $config
   *   Array where configuration structure is stored.
   */
  public function buildActions(array &$config) {
    foreach ($this->configBlock['config']['actions'] as $id => $entity) {
      $item = [];

      $item['label'] = $entity['label'];
      $item['type'] = $entity['type'];
      $item['url'] = $entity['url'];
      $item['show'] = (bool) $entity['show'];

      $config['actions'][$id] = $item;
    }
  }

  /**
   * @param array $config
   *
   * @return void
   */
  public function buildTermsAndConditions(array &$config) {
    foreach ($this->configBlock['config']['terms'] as $id => $entity) {
      $node = $this->entityManager->getStorage('node')->load($entity['modalcontent']);
      if ($node) {
        $node = $node->get('body');
        $node = $node->getValue();
        $node = reset($node);
      }
      $item = [];
      $item['label'] = $entity['label'];
      $item['description'] = $entity['description'];
      $item['url_text'] = $entity['url_text'];
      $item['modalTitle'] = $entity['modalTitle'];
      $item['modalcontent'] = $node['value'];
      $item['link'] = $entity['link'];
      $item['show'] = (bool) $entity['show'];
      $config['termsAndConditions'][$id] = $item;
    }
  }
  /**
   * @param array $config
   *
   * @return void
   */
  public function buildGetForms(array &$config) {
    $config['forms']['otp']=[
      'title'=>[
        'value'=>$this->configBlock['config']['label_otp']['label'],
        'show' => (bool) $this->configBlock['config']['label_otp']['show'],
      ],
    ];
    foreach ($this->configBlock['config']['form_otp'] as $id => $entity) {
      $node = $this->entityManager->getStorage('node')->load($entity['modalcontent']);
      if ($node) {
        $node = $node->get('body');
        $node = $node->getValue();
        $node = reset($node);
      }
      $item = [];
      $item['label'] = $entity['label'];
      $item['value'] = $entity['value'];
      $item['show'] = (bool) $entity['show'];
      $item['placeholder'] = $entity['placeholder'];
      $item['type'] = $entity['type'];
      
      $item['error_messaje']=[
        'error_message_required' => $entity['error_message_required'],
        'error_message_validation' => $entity['error_message_validation'],
      ];
      $item['validations']=[
        'required' =>(bool) $entity['required'],
        'minLength' => $entity['minLength'],
        'maxLength' => $entity['maxLength'],
        'pattern' => $entity['pattern'],
      ];
      $config['forms']['otp']['form'][$id] = $item;
    }
    $config['forms']['email']=[
      'title'=>[
        'value'=>$this->configBlock['config']['label_email']['label'],
        'show' => (bool) $this->configBlock['config']['label_email']['show'],
      ],
    ];
    foreach ($this->configBlock['config']['form_email'] as $id => $entity) {
      $item = [];
      $item['label'] = $entity['label'];
      $item['value'] = $entity['value'];
      $item['show'] = (bool) $entity['show'];
      $item['placeholder'] = $entity['placeholder'];
      $item['type'] = $entity['type'];
      
      $item['error_messaje']=[
        'error_message_required' => $entity['error_message_required'],
        'error_message_validation' => $entity['error_message_validation'],
      ];
      $item['validations']=[
        'required' =>(bool) $entity['required'],
        'minLength' => $entity['minLength'],
        'maxLength' => $entity['maxLength'],
        'pattern' => $entity['pattern'],
      ];
      $config['forms']['email']['form'][$id] = $item;
    }
    $config['forms']['actions']=[
      'sendCode'=>[
        'label'=> $this->configBlock['config']['actions_otp']['send_code']['label'],
        'type' => $this->configBlock['config']['actions_otp']['send_code']['type'],
        'show' => (bool) $this->configBlock['config']['actions_otp']['send_code']['show'],
        'url' => $this->configBlock['config']['actions_otp']['send_code']['url'],
      ],
      'cancelCode'=>[
        'label'=> $this->configBlock['config']['actions_otp']['cancel_code']['label'],
        'type' => $this->configBlock['config']['actions_otp']['cancel_code']['type'],
        'show' => (bool) $this->configBlock['config']['actions_otp']['cancel_code']['show'],
        'url' => $this->configBlock['config']['actions_otp']['cancel_code']['url'],
      ]
    ];
  }
  /**
   * function parse payment details for getData
   * @param $business_unit
   * @param $id_type
   * @param $contract_id
   * @param $purchaseorders
   * @param $fields
   * @param $tigomoney
   * @return array[]
   */
  public function parsePaymentDetails($fields, $data_db) {
    return [
      "period" => [
        "label" => $fields["period"]["label"],
        "value" => $data_db['period'],
        "show" => (bool) $fields["period"]["show"]
      ],
      "lineNumber" => [
        "label" => $fields["lineNumber"]["label"],
        "value" => $data_db['nline'],
        "show" => (bool) $fields["lineNumber"]["show"]
      ],
      "dueAmount" => [
        "label" => $fields["dueAmount"]["label"],
        "value" => $data_db['amount'],
        'formattedValue' =>$this->utils->formatCurrency($data_db['amount'], TRUE),
        "show" => (bool) $fields["dueAmount"]["show"]
      ]
    ];
  }

  public function parsePaymentMethods($fields, $data_db) {
    return [
      "methods" => [
        "label" => $fields["methods"]["label"],
        "value" => $data_db['methodPayment'],
        "show" => (bool) $fields["methods"]["show"]
      ],
    ];
  }

 
  //methods custom
    /**
   * Generate order in payment gateway.
   */
public function getDataDb($business_unit, $product_type, $id_type, $id, $purchaseorder_id, $tigomoney) {
    $billing_account_id = $id;
    $this->paymentGatewayAsyncRestLogic->getVariablesIfConvergent($billing_account_id, $business_unit, $id_type);
    $decrypt_purchaseorder_id = $this->transactions->decryptId($purchaseorder_id);
    $data_transaction = $this->transactions->getTransactionById($decrypt_purchaseorder_id);
    $amount=$data_transaction->amount;
    $nline=$data_transaction->accountId;
    $additional_data = $this->setAdditionalData($data_transaction);
    $period=$additional_data['period'];
    $period=$this->getFormattedPeriod($period, $business_unit);
    return [
      'period'=>$period,
      'nline'=>$nline,
      'amount'=>$amount,
      'methodPayment'=>$tigomoney
    ];
  }
  /**
   * Get Set Aditional Data.
   */
  public function setAdditionalData($data_transaction) {
    $additional_data = [];
    if ($data_transaction) {
      if (!empty($data_transaction->additionalData)) {
        $data = unserialize($data_transaction->additionalData);
        if (isset($data)) {
          $additional_data = (array) $data;
        }
      }
    }
    return $additional_data;
  }
  /**
   * Return the formatted period in letters.
   */
  public function getFormattedPeriod($period, $business_unit) {
    $date_period = explode(",", $period);
    $period_format="";
    foreach ($date_period as $value) {
      if (!is_numeric($value)) {
        return (implode(",", $date_period));
      }else {
        $year = substr($value, 0, 4);
        $month = substr($value, 4, 2);
        $month = str_replace([
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
        ], [
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
        ], $month
        );
        if ($period_format!="") {
          $period_format=$month . ' de ' . $year.", ".$period_format;
        }else {
          $period_format=$month . ' de ' . $year;
        }
      }
    }
    if ((strlen($period_format) > 0) && isset($period_format)) {
      return ($period_format);
    }else {
      return ("");
    }

  }

}
