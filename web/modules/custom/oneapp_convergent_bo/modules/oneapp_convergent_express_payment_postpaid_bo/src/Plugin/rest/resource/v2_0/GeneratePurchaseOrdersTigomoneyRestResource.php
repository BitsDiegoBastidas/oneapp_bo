<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\rest\ResourceResponse;
use Drupal\oneapp_rest\Plugin\ResourceBase;
use Drupal\oneapp\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   api_response_version = "v2_0",
 *   block_id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_generate_purchaseorders_tigomoney_invoices_block",
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_generate_purchase_orders_tigomoney_rest_resource",
 *   label = @Translation("OneApp Convergent Express Payment BO - Generate Purchase Orders Tigomoney"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/express/postpaid/{business_unit}/{id_type}/{id}/tigomoney/purchaseorders",
 *     "create" = "/api/v2.0/express/postpaid/{business_unit}/{id_type}/{id}/tigomoney/purchaseorders"
 *   },
 *   anonymous_token = TRUE
 * )
 */
class GeneratePurchaseOrdersTigomoneyRestResource extends ResourceBase {
  /**
   * Token information.
   *
   * @var mixed
   */
  private $expressToken;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $service;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->expressTokenService = $container->get('express_auth.jwt.decoder.express');
    $instance->tokenAuthorization = $container->get('oneapp_convergent_payment_gateway.token_service');
    $instance->oneappUtils = $container->get('oneapp.utils');
    $instance->utilsService = $container->get('oneapp_convergent_payment_gateway.v2_0.utils_service');
    return $instance;
  }

  /**
   * Method post.
   */
  public function post($business_unit, $id_type, $id, $data) {
    $this->init();
    $product_type = 'invoices';
    try {
      if (isset($data["amount"]) && $data["amount"] <= 0) {
        throw new \Exception(t("El monto no puede ser 0 o un número negativo"));
      }
      $token_service = \Drupal::service('oneapp_convergent_payment_gateway.token_service');
      $token_service->setDecode($this->request, 'invoices', NULL);

      $payment_utils = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.utils_service_tm_invoice');
      $payment_utils->validIdType($businessUnit, $idType);
      $payment_gateway = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.payment_gateway_rest_logic');
      $payment_gateway->setBusinessUnit($businessUnit);
      $this->configBlock = $payment_gateway->formatInitConfigs($this->configBlock, $businessUnit);
      $payment_gateway->setConfig($this->configBlock);
      $validations = $payment_gateway->validParams($data, $businessUnit);
      if ($validations == "ok") {
        $transaction = $payment_gateway->start($id, $id_type, $business_unit, $product_type, $data);
        unset($transaction['isMultipay']);
      }
      else {
        throw new \Exception($validations['message']);
      }
    }
    catch (\Exception $e) {
      if ($e->getCode() == 0 || $e->getCode() == 404) {
        $this->apiErrorResponse->getError()->set('message', $e->getMessage());
      }
      else {
        $error_service = \Drupal::service('oneapp_convergent_payment_gateway.v2_0.error_service');
        $this->apiErrorResponse->getError()->set('message', $error_service->getMessageErrorConfigBlock($e->getCode(),
          'form', $this->configBlock));
      }
      throw new NotFoundHttpException($this->apiErrorResponse, $e);
    }
    $businessUnit = $token_service->getBusinessUnit();
    // It is validated if the line has an account in tigomoney.
    $general_config_tigomoney = \Drupal::config('oneapp_convergent_payment_gateway.config')->get('tigoMoney');
    if ($general_config_tigomoney['validateAccountTigoMoney']['value'] == "yes") {
      $valid_tigomoney_account = \Drupal::service('oneapp_convergent_payment_gateway_tigomoney.v2_0.validityTigomoneyAccount_service')
        ->get($transaction['payerAccount']);
    }
    else {
      $valid_tigomoney_account = [
        'value' => TRUE,
        'url' => '',
      ];
    }
    $transaction['businessUnit'] = $businessUnit;
    $transaction['validTigomoneyAccount'] = $valid_tigomoney_account;
    $formatted_transaction = $payment_gateway->formatData($transaction);
    // Seteamos los datos.
    $this->apiResponse->getData()->setAll($formatted_transaction);
    // Seteamos la configuración.
    $config = $payment_gateway->getConfig($data, $transaction);
    $this->responseConfig($config);

    // Seteamos los datos en el response.
    $response = new ResourceResponse($this->apiResponse);
    $response->addCacheableDependency($this->cacheMetadata);
    return $response;

  }
  /**
   * Send order to payment gateway.
   */
  public function put($business_unit, $id_type, $id, Request $request) {
    $params = json_decode($request->getContent(), TRUE);
    $payment_gateway = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.payment_gateway_rest_logic');   
    try {
      $this->init();
      $product_type = 'invoices';
      if (!isset($params['deviceId'])) {
        $params['userAgent'] = $this->request->headers->get('user-agent');
      }
      $token_service = \Drupal::service('oneapp_convergent_payment_gateway.token_service');
      $token_service->setDecode($this->request, 'invoices', NULL);

      try {
        $this->tokenAuthorization->setDecode($this->request, $product_type, NULL, $business_unit);
        if (!$this->utilsService->validIdType($business_unit, $id_type)) {
          throw new \Exception("Url no valida");
        }

        $payment_gateway->setBusinessUnit($business_unit);
        $payment_gateway->setConfig($this->configBlock);

        $params['customerIpAddress'] = $payment_gateway->getUserIP($purchaseorderId);
        $payment_gateway->setParams($params);
        $reponse_purchase_orders = $payment_gateway->generateOrderId($business_unit, $product_type, $id_type, $id, $params['purchaseorders']);

        $this->apiResponse->getData()->setAll($reponse_purchase_orders);

      }
      catch (\Exception $e) {
        $this->apiErrorResponse->getError()->set('message', $e->getMessage());
        throw new NotFoundHttpException($this->apiErrorResponse, $e);
      }  
       
    }
    catch (\Exception $e) {
      if (!empty($e->getMessage())) {
        $message = $e->getMessage();
      }
      else {
        $message = $this->configBlock['sendPayment']['error_default'];
      }
      if (isset($this->apiErrorResponse)) {
        $this->apiErrorResponse->getError()->set('message', $message);
        throw new NotFoundHttpException($this->apiErrorResponse, $e);
      }
      else {
        throw $e;
      }
    }

    // Returns config data.
    $response = new ResourceResponse($this->apiResponse);
    $response->addCacheableDependency($this->cacheMetadata);
    return $response;
  }
  /**
   * Returns config data. (Optional)
   *
   * @param array $additional_data
   *   Additional data.
   */
  public function responseConfig(array $additional_data) {
    if (isset($additional_data['blocked'])) {
      foreach ($additional_data['blocked'] as $key => $value) {
        $this->apiResponse
          ->getConfig()
          ->set($key, $value);
      }
    }
    else {
      foreach ($additional_data as $key => $value) {
        $this->apiResponse
          ->getConfig()
          ->set($key, $value);
      }
    }
  }

  /**
   * Función encargada de inicializar los valores del token.
   */
  private function configServices() {
    $service_auth = \Drupal::service('express_auth.jwt.decoder.express');
    $this->expressToken = $service_auth->payload($this->request);
  }

}
