<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp_convergent_express_payment\Services\v2_0\OrderDetailsExpressRestLogic;

/**
 * Extend OrderDetailsExpressRestLogic to BO.
 */
class OrderDetailsExpressRestLogicBo extends OrderDetailsExpressRestLogic {
  protected const TYPE_CONVERGENT = 'convergent';

  /**
   * Service to identify accounts.
   *
   * @var mixed
   */
  protected $accountService;

  /**
   * Payment Gateway logic class.
   *
   * @var mixed
   */
  protected $paymentGatewayService;

  /**
   * Params of the request.
   *
   * @var array
   */
  protected $queryParams;

  /**
   * Class constructor.
   *
   * @param mixed $manager
   *   Class that manage the endpoint connection.
   * @param mixed $utils
   *   OneApp utilities.
   * @param mixed $utils_payment
   *   Utils class for payments.
   * @param mixed $config_mananger
   *   System setting class.
   * @param mixed $account_service
   *   Master account service.
   * @param mixed $payment_service
   *   Payment Gateway Service.
   * @param mixed $request_stack
   *   Service that manage the requests.
   */
  public function __construct($manager, $utils, $utils_payment, $config_mananger, $account_service, $payment_service, $request_stack) {
    parent::__construct($manager, $utils, $utils_payment, $config_mananger);
    $this->accountService = $account_service;
    $this->paymentGatewayService = $payment_service;
    $this->queryParams = $request_stack->getCurrentRequest()->query->all();
  }

  /**
   * Get config section structure.
   *
   * @return array
   *   Config structure.
   */
  public function getConfig() {
    $config = [];
    $this->buildSubTitle($config);
    $this->buildPaymentMethods($config);
    $this->buildForm($config);
    $this->buildImagePath($config);
    return $config;
  }

  /**
   * Process all the invoices fetching all of its information.
   *
   * @param string $business_unit
   *   The category of the service, e.g home or mobile.
   * @param string $id_type
   *   The kind of the billing type: suscribers or contract.
   * @param mixed $params
   *   The body of the request.
   * @param string $id
   *   The id of the account.
   * @param mixed $amount_parcial_payment
   *   Amount of the partial payment, not used.
   *
   * @return array
   *   The array with the data of the invoices.
   */
  public function iterateInvoices($business_unit, $id_type, $params, $id, $amount_parcial_payment = NULL) {
    $list_invoices = [];
    $is_convergent = FALSE;
    if ($business_unit == self::TYPE_HOME || $business_unit == self::TYPE_CONVERGENT) {
      $id_type_convergent = 'billingaccounts';
    }
    else {
      $id_type_convergent = $id_type;
    }
    $force_convergent_param = $this->queryParams['force_convergent'] ?? '';
    $billing_account_id_param = $this->queryParams['billing_account_id'] ?? '';
    if ($force_convergent_param === "true" && !empty($billing_account_id_param)) {
      $id = $billing_account_id_param;
    }
    $is_convergent_data = $this->paymentGatewayService->getBillingAccountIdForConvergentMsisdn($id, $id_type_convergent);
    if ($is_convergent_data['value']) {
      $business_unit = 'home';
      $id_type = 'billingaccounts';
      $id = $is_convergent_data['billingAccountId'];
      $is_convergent = TRUE;
    }

    if (isset($params["list-invoices"])) {
      $invoices = $this->getInvoiceList($id, $id_type, $business_unit);
      foreach ($params["list-invoices"] as $invoice_id) {
        try {
          foreach ($invoices as $invoice) {
            if ($invoice->invoiceId == $invoice_id || $invoice->period == $invoice_id) {
              if ($invoice->dueAmount <= 0) {
                continue;
              }
              $invoice_data = $this->getDataForInvoice($id, $invoice, $business_unit, $is_convergent);
              if (empty($invoice_data)) {
                continue;
              }
              $list_invoices[] = $invoice_data;
            }
          }

        }
        catch (\Exception $e) {
          continue;
        }
      }
    }
    return $list_invoices;
  }

  /**
   * Get all the information of the invoice.
   *
   * @param string $id
   *   The id of the account.
   * @param object $invoice
   *   Object with the information of the invoice to pay.
   * @param string $business_unit
   *   The category of the service, e.g home or mobile.
   * @param bool $is_convergent
   *   If the phone line is convergent or not.
   *
   * @return array
   *   The data of the invoice to list.
   */
  private function getDataForInvoice($id, $invoice, $business_unit, $is_convergent = FALSE) {
    if (!isset($invoice)) {
      return [];
    }
    $phone_number = '';
    if ($business_unit == self::TYPE_HOME) {
      $fields = $this->configHomeDetailsKeys;
    }
    else {
      $fields = $this->configMobileDetailsKeys;
      $phone_number = $id;
    }
    $rows['accountAmount'] = $invoice->dueAmount;
    $service_type = $is_convergent ? self::TYPE_CONVERGENT : $business_unit;
    $rows['type'] = [
      'label' => '',
      'value' => $service_type,
      'formattedValue' => $service_type,
      'show' => FALSE,
    ];
    foreach ($fields as $key) {
      $value = '';
      switch ($key) {
        case 'msisdn':
          $value = $phone_number;
          break;

        case 'address':
          $value = "";
          break;

        case 'dueDate':
          $value = isset($invoice->extendedDueDate) ? $invoice->extendedDueDate : $invoice->dueDate;
          break;

        case 'paymentReferrer':
          $value = $invoice->invoiceId;
          break;

        case 'amount':
          $value = $invoice->dueAmount;
          break;

        case 'contract':
          if ($business_unit == self::TYPE_MOBILE) {
            $value = "";
            break;
          }
          if (isset($invoice->contractId) && $invoice->contractId > 0) {
            $value = $invoice->contractId;
          }
          else {
            $value = $invoice->billingAccountId;
          }
          if (($value === "" || $value === NULL) && $business_unit == self::TYPE_HOME) {
            $value = $id;
          }
          break;

        case 'period':
          $value = $this->getFormattedPeriod($invoice->period, $business_unit);
          break;

        default:
          $value = FALSE;
          break;
      }
      if ($value !== FALSE) {
        $rows[$key] = $this->buildItem('details', $business_unit, $key, $value);
      }
    }
    return $rows;

  }

  /**
   * Get the invoice from the billing system.
   *
   * @param string $id
   *   The id of the account.
   * @param string $id_type
   *   The kind of the billing type: suscribers or contract.
   * @param string $business_unit
   *   The category of the service, e.g home or mobile.
   *
   * @return array
   *   List of invoices from the account.
   */
  private function getInvoiceList($id, $id_type, $business_unit) {
    $balance = $this->utilsPayment->getBalance($id, $id_type, $business_unit);
    if (isset($balance['noData'])) {
      return [];
    }
    return $balance['pendingInvoices'];
  }

  /**
   * Build the structure of the invoice detail.
   *
   * @param string $config_block
   *   The name of the block to which the property is to be added.
   * @param mixed $category
   *   The name of the category (mobile, home) if need.
   * @param string $config_property
   *   The name of the property.
   * @param mixed $value
   *   The value of the property.
   *
   * @return array
   *   The invoice detail formatted as expected.
   */
  protected function buildItem($config_block, $category, $config_property, $value) {
    $currency_properties = [
      'totalAmount',
      'parcialPayment',
      'balance',
      'amount',
    ];
    $item = [];
    $configuration = [];
    if ($category !== FALSE) {
      $configuration = $this->configBlock['config'][$config_block][$category][$config_property];
    }
    else {
      $configuration = $this->configBlock['config'][$config_block][$config_property];
    }
    if (empty($configuration)) {
      throw new \Exception("Invalid value");
    }
    $item['label'] = $configuration['label'];
    $item['value'] = $value;
    if (in_array($config_property, $currency_properties)) {
      $formatted_amount = $this->utils->formatCurrency($value, TRUE);
      $item['formattedValue'] = $formatted_amount;
    }
    elseif ($config_property === 'dueDate' && !empty($value)) {
      if (preg_match('/\d{1,2}\/\d{1,2}\/\d{1,4}/', $value)) {
        $value = str_replace('/', '-', $value);
      }
      $value = (string) strtotime($value);
      $item['formattedValue'] = $this->utils->formatDate($value, '');
    }
    else {
      $item['formattedValue'] = $value;
    }
    $item['show'] = $this->utils->formatBoolean($configuration['show']);
    return $item;
  }

  /**
   * Builds the structure of the data to show.
   *
   * @param mixed $list_invoices
   *   Array of the invoices to show.
   *
   * @return array
   *   Array with all the information to show.
   */
  public function getInvoiceSummary($list_invoices) {
    $total_to_pay = 0;
    foreach ($list_invoices as &$invoice) {
      $total_to_pay += $invoice['amount']['value'];
      unset($invoice['accountAmount']);
    }
    foreach ($this->configSummaryKeys as $key) {
      $value = '';
      switch ($key) {
        case 'totalAmount':
          $value = $total_to_pay;
          break;

        default:
          break;
      }
      $order[$key] = $this->buildItem('summary', FALSE, $key, $value);
    }

    $order['invoiceList'] = [
      'label' => $this->configBlock['config']['summary']['invoiceList']['label'],
      'show' => $this->utils->formatBoolean($this->configBlock['config']['summary']['invoiceList']['show']),
      'invoices' => $list_invoices,
    ];
    return $order;
  }

  /**
   * Return the formatted period for show on apiux init transaction.
   */
  public function getFormattedPeriod($period, $business_unit) {
    $date_period = explode("-", $period);
    $year = ($business_unit == self::TYPE_HOME) ? substr($date_period[0], 0, 4) : $date_period[0];
    $month = ($business_unit == self::TYPE_HOME) ? substr($date_period[0], 4, 2) : $date_period[1];
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
    if ((strlen($month) > 0) && isset($year)) {
      return ($month . ' de ' . $year);
    }
    else {
      return $period;
    }
  }

  /**
   * Build payment methods array.
   *
   * @param array $config
   *   Array where configuration structure is stored.
   */
  protected function buildPaymentMethods(array &$config) {
    $payment_methods = [];
    foreach ($this->invoicesPaymentMethods as $key) {
      $item = [];
      $entity = $this->configBlock['config']['paymentMethods'][$key];
      $item['label'] = $entity['label'];
      $item['img'] = $entity['img'];
      $item['icon'] = $entity['icon'];
      $item['url'] = $entity['url'];
      $item['type'] = $entity['type'];
      $item['show'] = $this->utils->formatBoolean($entity['show']);
      $payment_methods[$key] = $item;
    }
    $config['actions']['paymenthMethods'][] = $payment_methods;
  }

  /**
   * Build form.
   *
   * @param array $config
   *   Array where configuration structure is stored.
   */
  protected function buildForm(array &$config) {

    $form = [];

    $form['input_number_tigo_money']['label'] = "";
    $form['input_number_tigo_money']['value'] = "";
    $form['input_number_tigo_money']['show'] = $this->configBlock['value_reg_exp_input_modal_tigo_money']['reg_exp_tigo_money']['show'] ? true : false;
    $form['input_number_tigo_money']['placeholder'] = $this->configBlock['value_form_input_modal_tigo_money']['text_placeholder_tigo_money']['label'];
    $form['input_number_tigo_money']['type'] = "number";
    $form['input_number_tigo_money']['error_messaje']['error_message_required'] = "El campo número es obligatorio.";
    $form['input_number_tigo_money']['error_messaje']['error_message_validation'] = "El número que ingresaste no es número Tigo Prepago. Por favor verificarlo e ingresarlo correctamente.";
    $form['input_number_tigo_money']['validations']["required"] = true;
    $form['input_number_tigo_money']['validations']["minLength"] = $this->configBlock['value_form_input_modal_tigo_money']['text_placeholder_tigo_money']['min_digits'];
    $form['input_number_tigo_money']['validations']["maxLength"] = $this->configBlock['value_form_input_modal_tigo_money']['text_placeholder_tigo_money']['max_digits'];
    $form['input_number_tigo_money']['validations']["pattern"] = $this->configBlock['value_reg_exp_input_modal_tigo_money']['reg_exp_tigo_money']['label'];

    $form['modal_tigo_money']['text_modal_description_tigo_money']["label"] = $this->configBlock['value_messages_modal_tigo_money']['text_modal_description_tigo_money']['label'];
    $form['modal_tigo_money']['text_modal_description_tigo_money']["show"] = $this->configBlock['value_messages_modal_tigo_money']['text_modal_description_tigo_money']['show'] ? true : false;
    $form['modal_tigo_money']['cancel_modal_tigo_money']["label"] = $this->configBlock['value_actions_modal_tigo_money']['cancel_modal_tigo_money']['label'];
    $form['modal_tigo_money']['cancel_modal_tigo_money']["url"] = $this->configBlock['value_actions_modal_tigo_money']['cancel_modal_tigo_money']['url'];
    $form['modal_tigo_money']['cancel_modal_tigo_money']["type"] = $this->configBlock['value_actions_modal_tigo_money']['cancel_modal_tigo_money']['type'];
    $form['modal_tigo_money']['cancel_modal_tigo_money']["show"] = $this->configBlock['value_actions_modal_tigo_money']['cancel_modal_tigo_money']['show'] ? true : false;
    $form['modal_tigo_money']['continue_modal_tigo_money']["label"] = $this->configBlock['value_actions_modal_tigo_money']['continue_modal_tigo_money']['label'];
    $form['modal_tigo_money']['continue_modal_tigo_money']["url"] = $this->configBlock['value_actions_modal_tigo_money']['continue_modal_tigo_money']['url'];
    $form['modal_tigo_money']['continue_modal_tigo_money']["type"] = $this->configBlock['value_actions_modal_tigo_money']['continue_modal_tigo_money']['type'];
    $form['modal_tigo_money']['continue_modal_tigo_money']["show"] = $this->configBlock['value_actions_modal_tigo_money']['continue_modal_tigo_money']['show'] ? true : false;

    $config['form'] = $form;

  }

}
