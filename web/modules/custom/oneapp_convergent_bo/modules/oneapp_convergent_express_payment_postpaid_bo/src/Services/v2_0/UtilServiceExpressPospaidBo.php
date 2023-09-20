<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp\Exception\BadRequestHttpException;
use Drupal\oneapp\Exception\HttpException;

/**
 * Adaptation of the UtilServiceExpress from OneApp Convergent.
 */
class UtilServiceExpressPospaidBo {

  /**
   * Class constructor.
   *
   * @param mixed $endpoint_manager
   *   Class that manages the endpoint connection.
   * @param mixed $payment_gateway
   *   Convergent Payment gateway service.
   * @param mixed $payment_gateway_utils
   *   Utils for the Convergent Payment gateway service.
   * @param mixed $captcha_service
   *   Oneapp Captcha Token Service.
   * @param mixed $block_conf_service
   *   Service to access to the block config.
   */
  public function __construct($endpoint_manager, $payment_gateway, $payment_gateway_utils, $captcha_service, $block_conf_service) {
    $this->paymentGatewayService = $payment_gateway;
    $this->utilsPayment = $payment_gateway_utils;
    $this->manager = $endpoint_manager;
    $this->captchaService = $captcha_service;
    $this->blockConfigService = $block_conf_service;
  }

  /**
   * Return the formatted period for show on apiux init transaction.
   */
  public function getFormattedPeriod($period) {
    $date_period = explode("-", $period);
    if (strlen($period) == 6) {
      $year = substr($date_period[0], 0, 4);
      $month = substr($date_period[0], 4, 2);
    }
    else {
      $year = $date_period[0];
      $month = $date_period[1];
    }
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
   * Search by id contract get customer payment information.
   *
   * @return array
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   * @throws \ReflectionException
   *    Exeption.
   */
  public function getContractAccount($contract) {
    try {
      $response = $this
        ->getEndpoint('oneapp_home_billing_v2_0_invoices_endpoint', ['id' => $contract]);
    }
    catch (HttpException $exception) {
      if ($exception->getCode() == 404 || $exception->getCode() == 403 ||
        ($exception->getCode() == 400 && $exception->getMessage() == "Invalid Request") ||
        ($exception->getCode() == 400 && is_a($exception, BadRequestHttpException::class))) {
        return [];
      }
    }

    return $response;
  }

  /**
   * Search by number line get customer payment information.
   *
   * @return array
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   * @throws \ReflectionException
   *    Exeption.
   */
  public function getMobileAccount($contract) {
    try {
      $response = $this
        ->getEndpoint('oneapp_mobile_billing_v2_0_invoices_endpoint', ['id' => $contract]);
    }
    catch (HttpException $exception) {
      if ($exception->getCode() == 404 || $exception->getCode() == 403 ||
        ($exception->getCode() == 400 && $exception->getMessage() == "Invalid Request") ||
        ($exception->getCode() == 400 && is_a($exception, BadRequestHttpException::class))) {
        return [];
      }
    }

    return $response;
  }

  /**
   * Executes call to an endpoint.
   *
   * @param mixed $endpoint
   *   Endpoint id.
   * @param mixed $params
   *   Parameter array.
   * @param mixed $headers
   *   Headers array.
   * @param mixed $query
   *   Queries array.
   *
   * @return array
   *   The HTTP response object.
   *
   * @throws \Drupal\oneapp\Exception\HttpException
   * @throws \ReflectionException
   *    Exeption.
   */
  protected function getEndpoint($endpoint, $params = [], $headers = [], $query = []) {
    try {
      return $this->manager
        ->load($endpoint)
        ->setParams($params)
        ->setHeaders($headers)
        ->setQuery($query)
        ->sendRequest();
    }
    catch (HttpException $exception) {
      if ($exception->getCode() == 403
        || ($exception->getCode() == 400 && $exception->getMessage() == "Invalid Request")
        || ($exception->getCode() == 400 && is_a($exception, BadRequestHttpException::class))) {
        return [];
      }
      else {
        throw $exception;
      }
    }

    return [];
  }

  /**
   * Get account balance.
   *
   * @param string $account_id
   *   The id of the account.
   * @param string $id_type
   *   The kind of the billing: subscribers or contract.
   * @param string $business_unit
   *   The category of the service, e.g home or mobile.
   *
   * @return object
   *   The balance information of the account.
   */
  public function getBalance($account_id, $id_type, $business_unit) {
    if ($business_unit == 'home') {
      $id_type_convergent = 'billingaccounts';
    }
    else {
      $id_type_convergent = $id_type;
    }
    $is_convergent = $this->paymentGatewayService->getBillingAccountIdForConvergentMsisdn($account_id, $id_type_convergent);
    if ($is_convergent['value']) {
      $business_unit = 'home';
      $id_type = 'billingaccounts';
      $account_id = $is_convergent['billingAccountId'];
    }
    $balance = $this->utilsPayment->getBalance($account_id, $id_type, $business_unit);
    $balance['is_convergent'] = $is_convergent['value'];
    return $balance;
  }

  /**
   * Validate the captcha token sent by the front.
   *
   * @param string $captcha_token
   *   Token to validate sent by the frontend.
   *
   * @return array
   *   If the validation was succesful or not.
   */
  public function validateCaptcha($captcha_token) : array {
    $config_block = $this->blockConfigService->getDefaultConfigBlock("oneapp_convergent_payment_v2_0_get_invoices_form_block");
    if (!$config_block['config']['config_captcha']['active']['label']) {
      return [
        'success' => TRUE,
      ];
    }
    $config_captcha = $config_block['config']['config_captcha']['fields'];
    $secret_key = $config_captcha['secret_key']['label'];
    $verify = $this->captchaService->verifyToken($captcha_token, $secret_key);
    return $verify;
  }

  /**
   * Generates UUID for be used as deviceId.
   *
   * @return string
   *   UUID with v specification.
   */
  public function generateDeviceId() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      // 32 bits for the time_low
      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      // 16 bits for the time_mid
      mt_rand(0, 0xffff),
      // 16 bits for the time_hi,
      mt_rand(0, 0x0fff) | 0x4000,

      // 8 bits and 16 bits for the clk_seq_hi_res,
      // 8 bits for the clk_seq_low,
      mt_rand(0, 0x3fff) | 0x8000,
      // 48 bits for the node
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
  }

  /**
   * converts period to a string month and year
   * @param $dates
   * @return string
   */
  public function getFormatPeriods($dates) {
    setlocale(LC_TIME, 'es_ES.utf8');
    $periods = explode(",", $dates);

    $result = [];

    foreach ($periods as $period) {
      $year = substr($period, 0, 4);
      $month = substr($period, 4, 2);
      $month_string = strftime('%B', mktime(0, 0, 0, $month, 1, $year));
      $result[] = ucfirst($month_string) . " de $year";
    }

    return implode(",", $result);
  }

}
