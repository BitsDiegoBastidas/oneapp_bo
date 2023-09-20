<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('oneapp.settings.config.convergent.home_payment_gateway_tm_invoices')) {
      $route->setDefault('_form',
      'Drupal\oneapp_convergent_express_payment_postpaid_bo\Form\OneappHomePaymentGatewayExpressTmConfigForm');
    }
    if ($route = $collection->get('oneapp.settings.config.convergent.mobile_payment_gateway_tm_invoices')) {
      $route->setDefault('_form',
      'Drupal\oneapp_convergent_express_payment_postpaid_bo\Form\OneappMobilePaymentGatewayExpressTmConfigForm');
    }

  }

}
