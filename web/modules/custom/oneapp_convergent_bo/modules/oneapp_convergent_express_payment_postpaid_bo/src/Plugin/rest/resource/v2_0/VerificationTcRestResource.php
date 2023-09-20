<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\rest\ResourceResponse;
use Drupal\oneapp_rest\Plugin\ResourceBase;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   api_response_version = "v2_0",
 *   block_id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_verification_tc_block",
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_verification_tc_rest_resource",
 *   label = @Translation("OneApp Convergent Express Payment BO - Verification tc"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/express/postpaid/{business_unit}/{type}/{id}/verification/tc"
 *   },
 *   anonymous_token = TRUE
 * )
 */
class VerificationTcRestResource extends ResourceBase {

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $service;

  /**
   * {@inheritDoc}
   */
  public function get($business_unit, $type, $id) {
    $this->init();
    $this->setService();
    $this->setData($type, $id);
    $this->setConfig();
    $this->setMeta();

    $response = new ResourceResponse($this->apiResponse);
    $response->addCacheableDependency($this->cacheMetadata);
    return $response;
  }

  /**
   * Initialize  service that contains the business logic.
   */
  public function setService() {
    $this->service = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.verification_tc_rest_logic');
    $this->service->setConfig($this->configBlock);
  }

  /**
   * Set the data values in the response.
   */
  public function setData($type, $id) {
    $data = $this->service->getData($type, $id);
    $this->apiResponse->getData()->setAll($data);
  }

  /**
   * Set the configuration values in the api response.
   */
  public function setConfig() {
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
