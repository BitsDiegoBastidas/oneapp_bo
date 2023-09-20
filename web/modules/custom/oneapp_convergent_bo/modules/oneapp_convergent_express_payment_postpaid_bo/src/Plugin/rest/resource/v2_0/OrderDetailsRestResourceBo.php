<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\oneapp_rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to view order details.
 *
 * @RestResource(
 *   api_response_version = "v2_0",
 *   block_id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_order_details_block",
 *   id =
 *   "oneapp_convergent_express_payment_postpaid_bo_v2_0_order_details_rest_resource",
 *   label = @Translation("OneApp Convergent Express Payment BO - Order details rest resource v2.0"),
 *   uri_paths = {
 *   "create" = "/api/v2.0/express/postpaid/{business_unit}/invoices/{id_type}/{id}/orderdetails"
 *   },
 *   anonymous_token = TRUE
 * )
 */
class OrderDetailsRestResourceBo extends ResourceBase {

  /**
   * {@inheritDoc}
   */
  public function post($business_unit, $id_type, $id, $data) {
    $this->init();
    $this->setService();
    $response_invoices = $this->setData($business_unit, $id_type, $id, $data);
    $this->setConfig($response_invoices);
    $this->setMeta();

    $response = new ResourceResponse($this->apiResponse);
    $response->addCacheableDependency($this->cacheMetadata);
    return $response;
  }

  /**
   * Initialize service that contains the business logic.
   */
  public function setService() {
    $this->service = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.order_details_rest_logic_bo');
    $this->service->setConfig($this->configBlock);
    $this->service->setRequest($this->request);
  }

  /**
   * Set the data values in the response.
   *
   * @param string $business_unit
   *   The category of the service, e.g home or mobile.
   * @param string $id_type
   *   The kind of the billing type: suscribers or contract.
   * @param string $id
   *   The id of the account or phone number.
   * @param mixed $data
   *   The body of the request.
   *
   * @return array
   *   List of invoices.
   */
  public function setData($business_unit, $id_type, $id, $data) {
    $data = $this->service->getData($id, $business_unit, $id_type, $data, "");
    $this->apiResponse->getData()->setAll($data);
    return isset($data["invoiceList"]) ? $data["invoiceList"]["invoices"] : $data;
  }

  /**
   * Set the configuration values in the api response.
   *
   * @param mixed $invoices
   *   List of invoices.
   */
  public function setConfig($invoices) {
    $config = $this->apiResponse->getConfig()->getAll();
    $config = array_merge($config, $this->service->getConfig());
    $this->apiResponse
      ->getConfig()
      ->setAll($config);
  }

  /**
   * Set the metadata to the response.
   */
  public function setMeta() {
    $meta = $this->apiResponse->getMeta()->getAll();
    $meta['params'] = array_merge(
        $meta['params'],
        $this->service->getMeta()
        );
    $this->apiResponse->getMeta()->setAll($meta);
  }

}
