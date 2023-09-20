<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes.
 *
 * @package Drupal\oneapp_convergent_express_payment_postpaid_bo
 * */

 class OneappConvergentExpressPaymentPostpaidBoServiceProvider extends ServiceProviderBase {
  
  /**
   * {@inheritdoc}
   * */
  public function alter(ContainerBuilder $container) {
    $addons_definition = $container->
    getDefinition('oneapp_convergent_payment_gateway.v2_0.email_callbacks_service');
    $addons_definition
    ->setClass('Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0\EmailCallbackRestLogicBo');
  }

}
