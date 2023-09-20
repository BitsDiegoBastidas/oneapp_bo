<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

/**
 * Class that calculate the details of the invoices to pay.
 */
class VerificationTcRestLogic
{

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
  public function __construct($utils, $utils_express_bo, $request_stack, $entity_manager)
  {
    $this->utils = $utils;
    $this->utilsExpressBo = $utils_express_bo;
    $this->queryParams = $request_stack->getCurrentRequest()->query->all();
    $this->entityManager = $entity_manager;
  }

  /**
   * Responds to setConfig.
   *
   * @param mixed $configBlock
   *   Config card or default.
   */
  public function setConfig($configBlock)
  {
    $this->configBlock = $configBlock;
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
  public function getData($type, $id)
  {
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
            $row[$key]['details'] = $this->parsePaymentDetails($id, $field);
            break;
          case "paymentMethods":
            $row[$key]['details'] = $this->parsePaymentMethods($id, $field);
            break;
          case "labelPaymentDetails":
            $row['paymentDetails']['label'] = $field['label'];
            $row['paymentDetails']['show'] = (bool) $field['show'];
            break;
          case "labelPaymentMethods":
            $row['paymentMethods']['label'] = $field['label'];
            $row['paymentMethods']['show'] = (bool) $field['show'];
            break;
          case "fullName":
            $row[$key]['label'] = $field['label'];
            $row[$key]['show'] = (bool) $field['show'];
            break;
          case "nit":
            $row[$key]['label'] = $field['label'];
            $row[$key]['show'] = (bool) $field['show'];
            break;
          case "mail":
            $row[$key]['label'] = $field['label'];
            $row[$key]['show'] = (bool) $field['show'];
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
          'fullName' => $row['fullName'],
          'nit' => $row['nit'],
          'mail' => $row['mail'],
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
  public function getConfig()
  {
    $config = [
      'actions' => [],
      'termsAndConditions' => []
    ];

    $this->buildActions($config);
    $this->buildTermsAndConditions($config);

    return $config;
  }

  /**
   * Get meta section structure.
   *
   * @return array
   *   Meta structure.
   */
  public function getMeta()
  {
    return [];
  }

  /**
   * Get error state.
   *
   * @return array
   *   Returns empty structure for data section.
   */
  protected function errorState()
  {
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
  public function buildActions(array &$config)
  {
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
  public function buildTermsAndConditions(array &$config)
  {
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
   * function parse payment details for getData
   * @param $business_unit
   * @param $id
   * @param $fields
   *
   * @return array[]
   */
  public function parsePaymentDetails($id, $fields)
  {
    return [
      "period" => [
        "label" => $fields["period"]["label"],
        "value" => "",
        "show" => (bool) $fields["period"]["show"]
      ],
      "lineNumber" => [
        "label" => $fields["lineNumber"]["label"],
        "value" => $id,
        "show" => (bool) $fields["lineNumber"]["show"]
      ],
      "dueAmount" => [
        "label" => $fields["dueAmount"]["label"],
        "value" => "",
        "show" => (bool) $fields["dueAmount"]["show"]
      ]
    ];
  }

  public function parsePaymentMethods($id, $fields)
  {
    return [
      "methods" => [
        "label" => $fields["methods"]["label"],
        "value" => "",
        "show" => (bool) $fields["methods"]["show"]
      ],
      "name" => [
        "label" => $fields["name"]["label"],
        "value" => "",
        "show" => (bool) $fields["name"]["show"]
      ],
      "card" => [
        "label" => $fields["card"]["label"],
        "value" => "",
        "show" => (bool) $fields["card"]["show"]
      ],
      "expiration" => [
        "label" => $fields["expiration"]["label"],
        "value" => "",
        "show" => (bool) $fields["expiration"]["show"]
      ],
    ];
  }

  public function parsePaymentReceipt($id, $fields)
  {
    return [
      "email" => [
        "label" => $fields["email"]["label"],
        "value" => "",
        "show" => (bool) $fields["email"]["show"]
      ]
    ];
  }
}
