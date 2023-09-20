<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

/**
 * Class that calculate the details of the invoices to pay.
 */
class BalancesExpressRestLogic {

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

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $utilsExpressBo;

  /**
   * {@inheritdoc}
   */
  public function __construct($utils, $utils_express_bo, $request_stack) {
    $this->utils = $utils;
    $this->utilsExpressBo = $utils_express_bo;
    $this->queryParams = $request_stack->getCurrentRequest()->query->all();
  }

  /**
   * Responds to setConfig.
   *
   * @param mixed $config_block
   *   Config card or default.
   */
  public function setConfig($config_block) {
    $this->configBlock = $config_block;
  }

  /**
   * Return the data formatted.
   *
   * @param mixed $id
   *   Endpoint id.
   *
   * @return array
   *   The HTTP response object.
   */
  public function getData($id): array {
    try {
      $permalink = $this->queryParams['token_permalink'] ?? '';
      if ($permalink) {

        $value = "target=".$id."&operacion=pago-factura";
        $secret = $this->configBlock['config']['permalinkSection']['secret'];
        $permalink_encrypt= hash_hmac('sha256', $value, $secret);
        if ($permalink != $permalink_encrypt) {
          return [
            'message' => $this->configBlock['config']['messages']['permalink'],
            'flag' => 3,
            'noData' => ['value' => 'empty'],
          ];
        }
      }
      if (strlen($id) < 8) {
        $business_unit = 'home';
        $id_type = 'contracts';
      }
      else {
        $business_unit = 'mobile';
        $id_type = 'subscribers';
      }
      $balance = $this->utilsExpressBo->getBalance($id, $id_type, $business_unit);
      $results = $balance['pendingInvoices'];

      if (!empty($results)) {
        $data = $this->buildData($results, $business_unit, $balance['is_convergent'], $balance["billingAccountId"]);
      }
      else {
        return $this->emptyState();
      }
      if (empty($data)) {
        return $this->emptyState();
      }

      $order_invoices = $this->orderInvoices($data);

      return [
        'invoiceList' => [
          'label' => '',
          'show' => TRUE,
          'invoices' => $order_invoices,
        ],
      ];

    }
    catch (\Throwable $th) {
      return $this->errorState();
    }
  }

  /**
   * Create the structure data.
   *
   * @param mixed $results
   *   The invoice list.
   * @param string $business_unit
   *   The type of contract (home, mobile).
   * @param string $is_convergent
   *   If the line is convergent or not.
   * @param string $billing_account_id
   *   Billing account.
   *
   * @return array
   *   The list of invoice in the desired format.
   */
  public function buildData($results, $business_unit, $is_convergent, $billing_account_id): array {
    $invoices = [];
    $row = [];
    foreach ($results as $invoice) {
      foreach ($this->configBlock["fields"] as $key => $field) {
        $row[$key] = [
          'label' => $field['value'],
          'show'  => (bool) $field['show'],
        ];

        switch ($key) {
          case "invoiceId":
            // If the invoice is NULL the invoice is advanced.
            if ($invoice->invoiceId !== NULL) {
              $invoice_id = $invoice->invoiceId;
            }
            else {
              $invoice_id = (string) $invoice->period;
            }
            $row[$key]['value'] = $invoice_id;
            $row[$key]['formattedValue'] = $invoice_id;
            break;

          case "contractId":
            $row[$key]['value'] = $invoice->contractId;
            $row[$key]['formattedValue'] = $invoice->contractId;
            break;

          case "billingAccountId":
            $row[$key]['value'] = (!is_null($invoice->billingAcountId) ? $invoice->billingAcountId : $billing_account_id);
            $row[$key]['formattedValue'] = (!is_null($invoice->billingAcountId) ? $invoice->billingAcountId : $billing_account_id);
            break;

          case "dueAmount":
            $local_currency = $this->configBlock["configs"]["currency"]["format"] == 'localCurrency';
            $row[$key]['value'] = $invoice->dueAmount;
            $row[$key]['formattedValue'] = $this->utils->formatCurrency($invoice->dueAmount, $local_currency);
            break;

          case "period":
            $row[$key]['value'] = (strlen($invoice->period) >= 6 ? $invoice->period : $invoice->dueDate);
            if (strlen($invoice->period) >= 6) {
              $row[$key]['formattedValue'] = $this->utilsExpressBo->getFormattedPeriod($invoice->period);
            }
            else {
              $row[$key]['formattedValue'] = $this->utilsExpressBo->getFormattedPeriod($invoice->dueDate);
            }
            break;

          case "invoiceType":
            $row[$key]['value'] = $is_convergent ? 'convergent' : $business_unit;
            $row[$key]['formattedValue'] = $is_convergent ? 'convergent' : $business_unit;
            break;

          default:
            break;
        }
      }

      $invoices[] = $row;
    }

    return $invoices;
  }

  /**
   * Order invoices by date.
   *
   * @param mixed $invoices
   *   The list to be sorted.
   *
   * @return array
   *   The list with the sort element.
   */
  protected function orderInvoices($invoices): array {
    $order = [];
    foreach ($invoices as $key => $invoice) {
      $date_period = explode("-", $invoice["period"]["value"]);
      $year = $date_period[0];
      $month = $date_period[1];
      $day = $date_period[2];
      $order[$key]["year"] = $year . "-" . $month . "-" . $day;
      $order[$key]["invoiceId"] = $invoice["invoiceId"]["value"];
    }

    sort($order);
    return $this->buildOrderCheck($invoices, $order);
  }

  /**
   * Create the field inputCheck in the data structure.
   *
   * @param mixed $invoices
   *   The list of invoices.
   * @param mixed $orders
   *   The list of orders elements.
   *
   * @return array
   *   The invoices with the inputCheck element.
   */
  protected function buildOrderCheck($invoices, $orders): array {
    foreach ($orders as $keyOrder => $order) {
      foreach ($invoices as $key => $invoice) {
        if ($invoice["invoiceId"]["value"] == $order["invoiceId"]) {
          $invoices[$key]["inputCheck"] = [
            "label" => "",
            "value" => "",
            "name" => $invoice["invoiceId"]["value"],
            "order" => $keyOrder + 1,
            "show" => FALSE,
          ];
        }
      }
    }

    return $invoices;
  }

  /**
   * Get config section structure.
   *
   * @return array
   *   Config structure.
   */
  public function getConfig() {
    $config = [
      'form' => [],
      'actions' => [],
    ];

    $this->buildConfigCheckFilters($config);
    $this->buildConfigModal($config);
    $this->buildConfigActions($config);
    $this->buildConfigOtherFields($config);

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
   * Get empty state.
   *
   * @return array
   *   Returns empty structure for data section.
   */
  protected function emptyState() {
    if ($this->configBlock['config']['messages']['empty']) {

      return [
        'message' => $this->configBlock['config']['messages']['empty'],
        'flag' => 3,
        'noData' => ['value' => 'empty'],
      ];
    }

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
        'flag' => 3,
        'noData' => ['value' => 'empty'],
      ];
    }

    return [];
  }

  /**
   * Add the checkFilters config to the config section of the response.
   *
   * @param array $config
   *   The config section of the response.
   */
  protected function buildConfigCheckFilters(array &$config) {
    $config['form']['checkFilters'] = [
      'label' => $this->configBlock['config']['checkFilters']['label'],
      'show' => (bool) $this->configBlock['config']['checkFilters']['show'],
      'options' => [],
    ];

    foreach ($this->configBlock['config']['checkFilters']['options'] as $id => $entity) {
      $item = [];

      $item['label'] = $entity['label'];
      $item['value'] = (bool) $entity['value'];
      $item['description'] = $entity['description'];
      $item['show'] = (bool) $entity['show'];

      $config['form']['checkFilters']['options'][$id] = $item;
    }

    return $config;
  }

  /**
   * Add the modal window config to the config section of the response.
   *
   * @param array $config
   *   The config section of the response.
   */
  protected function buildConfigModal(array &$config) {
    $config['modal'] = [
      'label' => $this->configBlock['config']['modal']['label'],
      'description' => $this->configBlock['config']['modal']['description'],
      'show' => (bool) $this->configBlock['config']['modal']['show'],
      'actions' => [],
    ];

    foreach ($this->configBlock['config']['modal']['actions'] as $id => $entity) {
      $item = [];

      $item['label'] = $entity['label'];
      $item['show'] = (bool) $entity['show'];
      $item['type'] = $entity['type'];

      $config['modal']['actions'][$id] = $item;
    }
  }

  /**
   * Add the actions config to the config section of the response.
   *
   * @param array $config
   *   The config section of the response.
   */
  protected function buildConfigActions(array &$config) {
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
   * Add the others fields config to the config section of the response.
   *
   * @param array $config
   *   The config section of the response.
   */
  protected function buildConfigOtherFields(array &$config) {
    foreach ($this->configBlock['config']['otherFields'] as $id => $entity) {
      $item = [];

      $item['label'] = $entity['label'];
      if ($id === 'device_id') {
        $item['value'] = $this->generateDeviceId();
      }
      else {
        $item['value'] = $entity['value'];
      }
      $item['description'] = $entity['description'];
      $item['show'] = (bool) $entity['show'];

      $config['otherFields'][$id] = $item;
    }
  }

  /**
   * Generates a device id.
   *
   * @return string
   *   A UUID to identify the device.
   */
  public function generateDeviceId() {
    if (isset($this->queryParams['has_device_id']) && $this->queryParams['has_device_id'] == 'true') {
      return '';
    }
    else {
      return $this->utilsExpressBo->generateDeviceId();
    }
  }

}
