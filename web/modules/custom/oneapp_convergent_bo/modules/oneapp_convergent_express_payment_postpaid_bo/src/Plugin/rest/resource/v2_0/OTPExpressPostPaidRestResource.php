<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\rest\resource\v2_0;

use Drupal\oneapp_rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\oneapp\Exception\NotFoundHttpException;

/**
 * Provide a resource to view and recive OTP.
 *
 *  @RestResource(
 *   api_response_version = "v2_0",
 *   *   block_id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_generate_purchaseorders_tigomoney_invoices_block",
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_otp_express_postpaid_rest_resource",
 *   label = @Translation("OneApp Convergent Express Payment BO - OTP PostPaid"),
 *   uri_paths = {
 *     "canonical" = "/api/v2.0/express/postpaid/{business_unit}/{id_type}/{id}/code",
 *     "create" = "/api/v2.0/express/postpaid/{business_unit}/{id_type}/{id}/code/verify"
 *   },
 *   anonymous_token = TRUE
 * )
 */
class OTPExpressPostPaidRestResource extends ResourceBase {

  /**
   * GET method to load the form.
   *
   * @param string $id
   *   The phone number to send the otp.
   *
   * @return mixed
   *   The form information to build the modal.
   */
  public function get($id) {
    $this->init();
    $otp_logic = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.mobile.otp.rest.logic');
    $data['templateId'] = $this->request->query->get('templateId');
    $data['numberTM'] = $this->request->query->get('numberTM');
    try {
      $response = $otp_logic->post($data['numberTM'], $data);
      $this->apiResponse->getData()->setAll($response['data']);
      $config = $this->apiResponse->getConfig()->getAll();
      $config = array_merge($config, $response['config']);
      $this->apiResponse->getConfig()->setAll($config);

      $response = new ResourceResponse($this->apiResponse);
      $response->addCacheableDependency($this->cacheMetadata);
      return $response;
    }
    catch (\Exception $exception) {
      $message = 'No se pudo crear el otp, por favor intente de nuevo mas tarde';
      $this->apiErrorResponse->getError()->set('message', $message);
      throw new NotFoundHttpException($this->apiErrorResponse, $exception);
    }
  }

  /**
   * POST method to verify the OTP.
   *
   * @param string $id
   *   The phone number to send the otp.
   * @param mixed $data
   *   The parameters of the request.
   *
   * @return mixed
   *   If the code is valid or not.
   */
  public function post($id, $data) {
    $this->init();
    $otp_logic = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.mobile.otp.rest.logic');
    try {
      $response = $otp_logic->get($data['numberTM'], $data['code']);
      $this->apiResponse->getData()->setAll($response['data']);
      $response = new ResourceResponse($this->apiResponse);
      $response->addCacheableDependency($this->cacheMetadata);
      return $response;
    }
    catch (\Exception $exception) {
      $message = 'Fallo al verificar otp, por favor intente de nuevo mas tarde';
      $this->apiErrorResponse->getError()->set('message', $message);
      throw new NotFoundHttpException($this->apiErrorResponse, $exception);
    }

  }

}
