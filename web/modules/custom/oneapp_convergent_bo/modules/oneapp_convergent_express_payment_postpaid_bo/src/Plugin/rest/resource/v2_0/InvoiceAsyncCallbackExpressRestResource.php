<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\rest\Annotation\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_invoice_callback_rest_resource",
 *   label = @Translation("OneApp Convergent Express Payment BO - Invoice callback rest resource v2.0"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/express/postpaid/callbacks/{businessUnit}/{type}/invoices/orders/{purchaseOrderId}/{typePage}"
 *   }
 * )
 */
class InvoiceAsyncCallbackExpressRestResource extends ResourceBase {
  /**
   * {@inheritdoc}
   */
  public function put($businessUnit, $type, $purchaseOrderId, $typePage, Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    $invoices_callback_service = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.invoices_callbacks_rest_logic_bo');

    \Drupal::logger('oneapp_convergent_express_postpaid')->notice('<pre><code>' . print_r($data, TRUE) . '</code></pre>');
    try {
      $invoices_callback_service->setAsyncSuffix(($type == "creditCard" ? "" : $type));
      $result = $invoices_callback_service->apiPaymentProcessComplete($businessUnit, $type, $purchaseOrderId, $typePage, $data, $request);
      $response = new JsonResponse($result, $result['code']);
    }
    catch (\Exception $e) {
      $response = new JsonResponse(['message' => $e->getMessage()], $e->getCode());
    }
    $response->setMaxAge(0);
    $response->setVary(time());
    return $response;
  }
}
