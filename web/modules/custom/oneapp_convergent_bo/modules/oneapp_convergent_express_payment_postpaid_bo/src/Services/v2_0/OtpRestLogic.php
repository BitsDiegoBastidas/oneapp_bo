<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

/**
 * Class OtpService.
 */
class OtpRestLogic {

  /**
   * {@inheritdoc}
   */
  public function get($id, $code) {
    $otp_service = \Drupal::service('mobile.otp.service');
    $response = $otp_service->validateOtp($id, $code);
    if ($response) {
      return ['data' => ['status' => true]];
    }
    else {
      return ['data' => ['status' => false]];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function post($id, $params) {
    $otp_service = \Drupal::service('oneapp_convergent_express_payment_postpaid_bo.v2_0.mobile.otp.service');
    $otp_status = $otp_service->createOtp($id, $params);
    if ($otp_status) {
      return [
        "config" => $otp_service->getPopupBox($id),
        'data' => [
          'status' => true,
        ],
      ];
    }
    return ['data' => ['status' => false]];
  }


}
