<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\rest\ResourceResponse;
use Drupal\oneapp_rest\Plugin\ResourceBase;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   api_response_version = "v2_0",
 *   block_id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_verification_tigo_money_block",
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_verification_tigo_money_rest_resource",
 *   label = @Translation("OneApp Convergent Express Payment BO - Verification TigoMoney"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/express/postpaid/{business_unit}/{id_type}/{contract_id}/{purchaseorders}/verification/{tigomoney}"
 *   },
 *   anonymous_token = TRUE
 * )
 */
class VerificationTigoMoneyRestResource extends ResourceBase {

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $service;

  /**
   * {@inheritDoc}
   */
  public function get($business_unit, $id_type, $contract_id, $purchaseorders, $tigomoney) {
    $this->init();
    $this->setService();
    $this->setData($business_unit, $id_type, $contract_id, $purchaseorders, $tigomoney);
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
    $this->service = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.verification_tigo_money_rest_logic');
    $this->service->setConfig($this->configBlock);
  }

  /**
   * Set the data values in the response.
   */
  public function setData($business_unit, $contract_id, $id_type, $purchaseorders, $tigomoney) {
    $data = $this->service->getData($business_unit, $contract_id, $id_type, $purchaseorders, $tigomoney);
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
