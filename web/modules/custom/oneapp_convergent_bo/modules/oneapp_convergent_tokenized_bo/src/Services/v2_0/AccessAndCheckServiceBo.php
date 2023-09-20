<?php

namespace Drupal\oneapp_convergent_tokenized_bo\Services\v2_0;

use Drupal\oneapp_rest\Services\AccessAndCheckService;

/**
 * Class GetInvoicesFormRestLogicBo.
 */
class AccessAndCheckServiceBo extends AccessAndCheckService {

    /**
   * Validate if request have contract or suscriber acording to the token anonymous
   */
  public function validateAccessExpressToken($request, $payload, $id, $document, $documentType, $businessUnit) {
    $express_auth_settings = \Drupal::config('express_auth.settings')->getRawData();
    if (isset($express_auth_settings['activate']) && $express_auth_settings['activate']) {
      try {
        if (isset($businessUnit) & !empty($businessUnit)) {
          $service = \Drupal::service('oneapp_convergent_payment.v2_0.order_details_rest_logic');
          if ($businessUnit == 'home') {
            // Implementacion para home.
            $contracts = $service->queryEndpointByDocId('oneapp_convergent_payment_v2_0_home_find_customer_accounts_by_identification_endpoint', $payload->document, 'cc', TRUE);
            if (empty($id) || $id == "") {
              $listIds = json_decode($request->getContent(), TRUE);
              if (isset($listIds['list-invoices'])) {
                $listIds = is_array($listIds['list-invoices']) ? $listIds["list-invoices"] : [];
                $access = FALSE;
                foreach ($listIds as $idInvoices) {
                  if (isset($idInvoices['billingaccounts']) && !empty($idInvoices['billingaccounts'])) {
                    $access = $this->validateAccessByAccountExpressHome($contracts, $idInvoices['billingaccounts']);
                  }
                }
                if ($access) {
                  return TRUE;
                }
              }
            }
            else {
              return $this->validateAccessByAccountExpressHome($contracts, $id);
            }
          }
          else {
            // Implementacion para mobile.
            if (isset($payload->msisdn) && $id == $payload->msisdn) {
              return TRUE;
            }
            else {
              // Implementacion para mobile.
              $listIds = json_decode($request->getContent(), TRUE);
              if (isset($listIds['list-invoices'])) {
                $listIds = is_array($listIds['list-invoices']) ? $listIds["list-invoices"] : [];
                if (isset($payload->msisdn)) {
                  foreach ($listIds as $subscribers) {
                    if ($payload->msisdn == $subscribers["subscribers"]) {
                      return TRUE;
                    }
                  }
                  return FALSE;
                }
                else {
                  return $this->validateAccessByAccountExpressSubscribers($payload->document, $listIds, $service);
                }
              }
              else {
                return $this->validateAccessByAccountExpressMobile($payload->document, $id, $service);
              }
            }
          }
        } elseif(isset($payload->msisdn) && $payload->msisdn == $request->get('contract_or_line')) {
            return TRUE;
        }
      }
      catch (\Exception $e) {
        $error = new ErrorBase();
        $error->getError()->set('message', $e->getMessage());
        throw new UnauthorizedHttpException($error, $e);
      }
    }
    else {
      return TRUE;
    }
    $error = new ErrorBase();
    $error->getError()->set('message', 'You dont have permissions to check the account info.');
    throw new UnauthorizedHttpException($error);
  }

}