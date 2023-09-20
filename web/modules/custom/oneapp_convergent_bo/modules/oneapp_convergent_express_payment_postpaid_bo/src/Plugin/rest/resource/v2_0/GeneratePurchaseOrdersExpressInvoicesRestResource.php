<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\oneapp_rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\oneapp\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a rest resource to make payment in the client endpoint.
 *
 * @RestResource(
 *   api_response_version = "v2_0",
 *   id =
 *   "oneapp_convergent_express_payment_postpaid_bo_v2_0_generate_purchase_orders_express_invoices_rest_resource",
 *   block_id =
 *   "oneapp_convergent_express_payment_postpaid_bo_v2_0_generate_purchase_orders_express_invoices_block",
 *   label = @Translation("OneApp convergent express payment gateway invoices generate purchaseorders rest resource v2_0"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/express/postpaid/{business_unit}/{product_type}/{id_type}/{id}/purcharseorder/{purchase_order_id}",
 *     "create" = "/api/v2.0/express/postpaid/{business_unit}/{product_type}/{id_type}/{id}/purcharseorder"
 *   },
 *   anonymous_token = TRUE
 * )
 */
class GeneratePurchaseOrdersExpressInvoicesRestResource extends ResourceBase {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->expressTokenService = $container->get('express_auth.jwt.decoder.express');
    $instance->tokenAuthorization = $container->get('oneapp_convergent_payment_gateway.token_service');
    $instance->paymentGatewayExpress = $container
      ->get('oneapp_convergent_express_payment_postpaid_bo.v2_0.payment_gateway_express_invoices_rest_logic');
    $instance->oneappUtils = $container->get('oneapp.utils');
    $instance->utilsService = $container->get('oneapp_convergent_payment_gateway.v2_0.utils_service');
    return $instance;
  }

  /**
   * Method POST.
   *
   * @param string $business_unit
   *   The category of the phone line, e.g home or mobile.
   * @param string $product_type
   *   The type of product to pay: invoices, packets, etc.
   * @param string $id_type
   *   The kind of the id, suscribers or contract.
   * @param string $id
   *   The id of the account, phone number for mobile, etc.
   * @param mixed $data
   *   The body of the request.
   */
  public function post($business_unit, $product_type, $id_type, $id, $data) {
    $this->init();
    $this->configServices();
    if (isset($data['deviceId'])) {
      $data['query']['deviceId'] = $data['deviceId'];
    }
    else {
      $data['query']['userAgent'] = $this->request->headers->get('user-agent');
    }
    $data['msisdn'] = $id;
    $data['tokenUuid'] = $this->expressToken->uuid;
    try {
      $this->tokenAuthorization->setDecode($this->request, $product_type, NULL, $business_unit);
      if (!$this->utilsService->validIdType($business_unit, $id_type)) {
        throw new \Exception("Url no valida");
      }
      $this->paymentGatewayExpress->setConfig($this->configBlock);
      $validations = $this->paymentGatewayExpress->validParams($data, $business_unit);
      if ($validations == "ok") {
        $transaction_data = $this->paymentGatewayExpress->start($id, $id_type, $business_unit, $product_type, $data);
      }
      else {
        throw new \Exception($validations['message']);
      }
    }
    catch (\Exception $e) {
      $this->apiErrorResponse->getError()->set('message', $e->getMessage());
      throw new NotFoundHttpException($this->apiErrorResponse, $e);
    }
    $formatted_data = $this->paymentGatewayExpress->formatData($transaction_data);
    $this->apiResponse->getData()->setAll($formatted_data);
    $config_data = $this->paymentGatewayExpress->getConfig($data, $transaction_data, $business_unit, $product_type, $this->configBlock);
    $this->responseConfig($config_data);
    $response = new ResourceResponse($this->apiResponse);
    $response->addCacheableDependency($this->cacheMetadata);
    return $response;

  }

  /**
   * Method PUT.
   *
   * @param string $business_unit
   *   The category of the phone line, e.g home or mobile.
   * @param string $product_type
   *   The type of product to pay: invoices, packets, etc.
   * @param string $id_type
   *   The kind of the id, suscribers or contract.
   * @param string $id
   *   The id of the account, phone number for mobile, etc.
   * @param string $purchase_order_id
   *   The id of the purchase order.
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function put($business_unit, $product_type, $id_type, $id, $purchase_order_id, Request $request) {
    $this->init();
    $this->configServices();
    $data = json_decode($request->getContent(), TRUE);
    if (isset($data['deviceId'])) {
      $data['query']['deviceId'] = $data['deviceId'];
    }
    else {
      $data['query']['userAgent'] = $this->request->headers->get('user-agent');
    }
    $data['msisdn'] = $id;
    // Get the user IP address.
    $data['customerIpAddress'] = $this->oneappUtils->getUserIp();
    $data['tokenUuId'] = $this->expressToken->uuid;
    try {
      $this->tokenAuthorization->setDecode($this->request, $product_type, NULL, $business_unit);
      if (!$this->utilsService->validIdType($business_unit, $id_type)) {
        throw new \Exception("Url no valida");
      }
      $this->paymentGatewayExpress->setConfig($this->configBlock);
      $this->paymentGatewayExpress->setParams($data);
      $formatted_transaction = $this->paymentGatewayExpress->generateOrderId($business_unit, $product_type,
      $id_type, $id, $purchase_order_id);
    }
    catch (\Exception $e) {
      $this->apiErrorResponse->getError()->set('message', $e->getMessage());
      throw new NotFoundHttpException($this->apiErrorResponse, $e);
    }

    $formatted_data = $this->paymentGatewayExpress->formatPaymentInformation($formatted_transaction);
    $this->apiResponse->getData()->setAll($formatted_data);
    $response = new ResourceResponse($this->apiResponse);
    $response->addCacheableDependency($this->cacheMetadata);
    return $response;

  }

  /**
   * Function in charge of initializing the values of the token.
   */
  private function configServices() {
    $this->expressToken = $this->expressTokenService->payload($this->request);
  }

  /**
   * Add the config to the request.
   *
   * @param array $additional_data
   *   Additional data.
   */
  public function responseConfig(array $additional_data) {
    unset($additional_data['data']['dueAmount']);
    unset($additional_data['data']['invoiceId']);
    if (isset($additional_data['data']['accountId'])) {
      unset($additional_data['data']['accountId']);
    }
    if (isset($additional_data['data']['productType'])) {
      unset($additional_data['data']['productType']);
    }
    foreach ($additional_data as $key => $value) {
      $this->apiResponse
        ->getConfig()
        ->set($key, $value);
    }
  }

}
