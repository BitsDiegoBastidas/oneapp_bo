<?php

namespace Drupal\oneapp_convergent_tokenized_bo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the classes of AccessAndCheckService.
 *
 * @package Drupal\oneapp_convergent_tokenized_bo
 */
class OneappConvergentTokenizedBoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $invoices = $container->getDefinition('oneapp_rest.access_rest');
    $invoices->setClass('Drupal\oneapp_convergent_tokenized_bo\Services\v2_0\AccessAndCheckServiceBo');
  }

}
