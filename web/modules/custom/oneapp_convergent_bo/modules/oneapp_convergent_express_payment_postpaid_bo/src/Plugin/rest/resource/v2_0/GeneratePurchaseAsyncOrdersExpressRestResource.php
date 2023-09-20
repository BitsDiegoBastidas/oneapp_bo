<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\oneapp_rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\oneapp\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   api_response_version = "v2_0",
 *   block_id = "oneapp_convergent_payment_gateway_async_invoices_v2_0_generate_purchaseorders_block",
 *   id = "oneapp_convergent_payment_express_invoices_v2_0_generate_purchaseordersasync_rest_resource",
 *   label = @Translation("OneApp convergent payment express postpaid BO - Generate Purchaseorders Async Rest Resource V2_0"),
 *   uri_paths = {
 *     "create" = "/api/v2.0/express/pospaid/{businessUnit}/invoices/{idType}/{id}/purchaseordersasync",
 *   },
 *   anonymous_token = TRUE
 * )
 */

class GeneratePurchaseAsyncOrdersExpressRestResource extends ResourceBase {

  /**
   * @var \Drupal\oneapp_convergent_payment_gateway_invoices\Services\v2_0\PaymentGatewayRestLogic
   */
  public $qrRestLogicExpress;

  public function post($businessUnit, $idType, $id, $data, Request $request) {
    $this->init();
    $product_type = 'invoices';
    $deviceId = $this->request->query->get('deviceId');
    if (isset($deviceId)) {
      $data['deviceId'] = $deviceId;
    }
    else {
      $data['userAgent'] = $this->request->headers->get('user-agent');
    }
    try {
      $this->getServicesByPaymentMethod($businessUnit, $data["paymentMethod"]);
      $this->qrRestLogicExpress->setConfig($this->configBlock);
      if ($data["paymentMethod"] == 'qrPayment') {
        $transaction_info = $this->qrRestLogicExpress->generateCodeQR($id, $idType, $businessUnit, $product_type, $data);
        $this->apiResponse->getData()->setAll($transaction_info);
        $this->responseConfig($transaction_info, $data);
      }
    }catch (\Exception $e){
      $this->apiErrorResponse->getError()->set('message', $e->getMessage());
      throw new NotFoundHttpException($this->apiErrorResponse, $e);
    }
    $response = new ResourceResponse($this->apiResponse);
    $response->addCacheableDependency($this->cacheMetadata);
    return $response;
  }


  public function getServicesByPaymentMethod($business_unit, $payment_method){
    if($payment_method == 'qrPayment'){
      try {
        $module_handler = \Drupal::service('module_handler');
        if (empty($module_handler->moduleExists('oneapp_convergent_payment_gateway_qr'))) {
          throw new \Exception('The Oneapp Payment Gateway QR Invoices is not enabled');
        }
        $this->qrRestLogicExpress = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.qr_express_rest_logic_bo');
        $this->configBlock = $this->blockConfigService->getDefaultConfigBlock('oneapp_convergent_express_payment_pospaid_qr_generate_code_qr_v2_0_block');
      }
      catch (\Exception $e) {
        throw new \Exception('The Oneapp Payment Express QR Invoices is not enabled');
      }
    }
  }

  /**
   * Returns config data. (Optional).
   */
  public function responseConfig($additional_data, $params) {
    if ($params['paymentMethod'] == 'qrPayment') {
      $defaults_config = $this->qrRestLogicExpress->getDefaultsConfig();
      $actions = $this->qrRestLogicExpress->getActions($params);
      $messages = $this->qrRestLogicExpress->getMessages();
      $this->apiResponse
        ->getConfig()
        ->setAll($defaults_config)
        ->set('messages', $messages)
        ->set('actions', $actions);
    }
    else {
      if (isset($additional_data['forms'])) {
        unset($additional_data['data']['dueAmount']);
        unset($additional_data['data']['invoiceId']);
        $this->configBlock["actions"]["submit"]["show"] = $this->configBlock["actions"]["submit"]["show"] ? TRUE : FALSE;
        $this->configBlock["actions"]["cancel"]["show"] = $this->configBlock["actions"]["cancel"]["show"] ? TRUE : FALSE;
        $this->apiResponse
          ->getConfig()
          ->set('params', $additional_data['data'])
          ->set('forms', $additional_data['forms'])
          ->set('actions', $this->configBlock["actions"]);
      }
      else {
        $this->apiResponse
          ->getConfig()
          ->set('message', $additional_data['message'])
          ->set('actions', $additional_data['actions']);
      }
    }
    if (isset($additional_data['termsAndConditions'])) {
      $this->apiResponse
        ->getConfig()
        ->set('termsAndConditions', $additional_data['termsAndConditions']);
    }
  }
}
