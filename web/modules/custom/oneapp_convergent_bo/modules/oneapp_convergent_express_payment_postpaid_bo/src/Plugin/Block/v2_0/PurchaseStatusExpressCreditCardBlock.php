<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\Block\v2_0;

use Drupal\adf_block_config\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'PurchaseStatusExpressCreditCardBlock' block.
 *
 * @Block(
 *  id = "oneapp_convergent_express_payment_postpaid_credit_card_v2_0_purchase_status_block",
 *  admin_label = @Translation("OneApp Convergent Express Payment Credit Card Purchase Status v2.0 - (Estado de transacción)"),
 *  group = "oneapp_convergent_payment_gateway_credit_card"
 * )
 */
class PurchaseStatusExpressCreditCardBlock extends BlockBase {

  protected $fields;
  protected $erros;
  protected $messages;
  protected $aditional;
  protected $actions;


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $this->fields = [
      'accountId' => [
        'label' => 'Número de contrato|Número de la línea',
        'show' => TRUE,
        'weight' => 1,
      ],
      'accountNumber' => [
        'label' => 'Número de factura',
        'show' => FALSE,
        'weight' => 1,
      ],
      'productType' => [
        'label' => 'Opción',
        'show' => TRUE,
        'weight' => 2,
      ],
      'cardBrand' => [
        'label' => 'Medio de pago',
        'show' => TRUE,
        'weight' => 3,
        'field' => 'Tarjeta',
      ],
      'orderId' => [
        'label' => 'Número de orden',
        'show' => TRUE,
        'weight' => 4,
      ],
      'changed' => [
        'label' => 'Fecha de la transacción',
        'show' => TRUE,
        'weight' => 5,
      ],
      'maskedAccountId' => [
        'label' => 'Número de tarjeta',
        'show' => TRUE,
        'weight' => 6,
      ],
      'transactionId' => [
        'label' => 'Número de auditoría',
        'show' => TRUE,
        'weight' => 7,
      ],
      'numberReference' => [
        'label' => 'Número de referencia',
        'show' => TRUE,
        'weight' => 8,
      ],
      'numberAccess' => [
        'label' => 'Número de autorización',
        'show' => TRUE,
        'weight' => 9,
      ],
      'amount' => [
        'label' => 'Monto autorizado',
        'show' => TRUE,
        'weight' => 10,
      ],
      'stateOrder' => [
        'label' => 'Estado de la transacción',
        'show' => FALSE,
        'weight' => 11,
      ],
      'periods' => [
        'label' => 'Periodo',
        'show' => FALSE,
        'weight' => 12,
      ],
    ];

    $this->aditional = [];

    $this->errors = [
      'valid' => [
        'label' => t('Cuando el campo no es valido'),
        'value' => 'El @field no es valido',
      ],
      'empty' => [
        'label' => t('Cuando no se encuentra en la base de datos'),
        'value' => 'No existen registros',
      ],
    ];

    $this->messages = [
      'fulfillment_complete' => [
        'title' => '¡Su pago ha sido procesado!',
        'body' => 'Su pago ha sido procesado satisfactoriamente.',
      ],
      'order_in_progress' => [
        'title' => '¡Tu transacción está siendo procesada!',
        'body' => 'El estado de tu transacción se verá reflejado en los próximos minutos',
      ],
      'payment_non_complete' => [
        'title' => '!No se ha completado la compra!',
        'body' => 'Ha ocurrido un error en la conexión, o el paquete ha expirado y ya no se encuentra disponible.',
      ],
      'fulfillment_non_complete' => [
        'title' => '!No se ha completado la compra!',
        'body' => 'Ha ocurrido un error en la conexión, o el paquete ha expirado y ya no se encuentra disponible.',
      ],
    ];

    $this->errorApi = [
      'error_default' => 'El id de la orden no se encotro.',
      'error_mapping' => '',
    ];

    $this->actions = [
      'home' => [
        'label' => 'VOLVER AL INICIO',
        'show' => TRUE,
        'type' => "button",
        "url" => "/",
      ],
      'details' => [
        'label' => 'VER DETALLES',
        'show' => TRUE,
        'type' => "link",
        "url" => "/",
      ],
    ];

    $this->configs = [
      'sendPayment' => [
        'label' => t('Enviar el parametro forceUpdate a payment para forzar la respuesta'),
        'value' => TRUE,
      ],
    ];

    if (!empty($this->adfDefaultConfiguration())) {
      return $this->adfDefaultConfiguration();
    }
    else {
      return [
        'fields' => $this->fields,
        'aditional' => $this->aditional,
        'errors' => $this->errors,
        'messages' => $this->messages,
        'errorApi' => $this->errorApi,
        'actions' => $this->actions,
        'configs' => $this->configs,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {

    $form = [
      '#prefix' => '<div id="container-fields-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['data'] = [
      '#type' => 'details',
      '#title' => $this->t('Data'),
      '#open' => TRUE,
    ];

    $form['data']['fields'] = [
      '#type' => 'table',
      '#header' => [
        t('Label (home|mobile)'),
        t('Show'),
        t('Weight'),
        t('Field'),
        '',
      ],
      '#empty' => t('There are no items.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'mytable-order-weight',
        ],
      ],
    ];

    $fields = isset($this->configuration["fields"]) ? $this->configuration["fields"] : $this->fields;
    uasort($fields, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    foreach ($fields as $id => $entity) {
      $form['data']['fields'][$id]['#attributes']['class'][] = 'draggable';
      $form['data']['fields']['#weight'] = $entity['weight'];
      // Some table columns containing raw markup.
      $form['data']['fields'][$id]['label'] = [
        '#type' => 'textfield',
        '#description' => 'Para configurar el label para home y mobile se debe separar por | dejando primero configurado home y luego mobile así: labelhome|labelmobile',
        '#default_value' => $entity['label'],
      ];
      $form['data']['fields'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];

      $form['data']['fields'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $entity['weight']]),
        '#title_display' => 'invisible',
        '#default_value' => $entity['weight'],
        '#attributes' => ['class' => ['mytable-order-weight']],
      ];

      if ($id == 'cardBrand') {
        $form['data']['fields'][$id]['field'] = [
          '#type' => 'textfield',
          '#description' => 'Medio de pago',
          '#default_value' => $entity['field'] ??  $this->fields[$id]['field'],
        ];
      }
    }
    /**
     * Data adicional
     */
    $form['aditional'] = [
      '#type' => 'details',
      '#title' => $this->t('Data Adicional'),
      '#open' => TRUE,
    ];

    $form['aditional']['fields'] = [
      '#type' => 'table',
      '#header' => [
        t('Label'),
        t('Variable'),
        t('Show'),
      ],
      '#empty' => t('There are no items.'),
    ];

    $remove = ($form_state->get('remove') != NULL ) ? $form_state->get('remove') : FALSE;
    $count = ($form_state->get('count') != NULL) ? $form_state->get('count') : 0;
    $aditionals = isset($this->configuration["aditional"]) ? $this->configuration["aditional"] : $this->aditional;
    if (count($aditionals) < $count) {
      $aditionals["data{$count}"] = [];
      $form_state->set('count', $count);
    }
    else {
      if ($remove) {
        $count = 0;
        $form_state->set('count', 0);
      }
      elseif ($count != NULL) {
        $form_state->set('count', $count);
        $count = $count;
      }
      else {
        $form_state->set('count', count($aditionals));
        $count = count($aditionals);
      }
    }

    for ($i = 0; $i < $count; $i++) {

      $aditionalId = "data{$i}";

      $form['aditional']['fields'][$aditionalId]['label'] = [
        '#type' => 'textfield',
        '#default_value' => isset($this->configuration["aditional"][$aditionalId]["label"]) ? $this->configuration["aditional"][$aditionalId]["label"] : '',
      ];

      $form['aditional']['fields'][$aditionalId]['variable'] = [
        '#type' => 'textfield',
        '#default_value' => isset($this->configuration["aditional"][$aditionalId]["variable"]) ? $this->configuration["aditional"][$aditionalId]["variable"] : '',
      ];

      $form['aditional']['fields'][$aditionalId]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($this->configuration["aditional"][$aditionalId]["show"]) ? $this->configuration["aditional"][$aditionalId]["show"] : '',
      ];

    }

    $form['aditional']['add'] = [
      '#type' => 'submit',
      '#value' => t('Agregar una dato adicional'),
      '#submit' => [
        [$this, 'addContainerCallback'],
      ],
      '#ajax' => [
        'callback' => [$this, 'addFieldSubmit'],
        'wrapper' => 'container-fields-wrapper',
      ],
      '#attributes' => [
        'data-link-action' => ['Add service to portfolio'],
      ],
    ];

    if ($count > 0) {
      $form['aditional']['remove'] = [
        '#type' => 'submit',
        '#value' => t('Eliminar un dato adicional'),
        '#submit' => [
          [$this, 'removeContainerCallback'],
        ],
        '#ajax' => [
          'callback' => [$this, 'addFieldSubmit'],
          'wrapper' => 'container-fields-wrapper',
        ],
        '#attributes' => [
          'data-link-action' => ['Delete service to portfolio'],
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Actions'),
      '#open' => TRUE,
    ];

    $form['actions']['actions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Etiqueta'), $this->t('Mostrar'), $this->t('Tipo'), $this->t('Url'), '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    $actions = isset($this->configuration['actions']) ? $this->configuration['actions'] : $this->actions;

    foreach ($actions as $id => $action) {

      if (isset($action['label'])) {
        $form['actions']['actions'][$id]['label'] = [
          '#type' => 'textfield',
          '#default_value' => $action['label'],
          '#size' => 20,
        ];
      }
      else {
        $form['actions']['actions'][$id]['label'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['show'])) {
        $form['actions']['actions'][$id]['show'] = [
          '#type' => 'checkbox',
          '#default_value' => $action['show'],
        ];
      }
      else {
        $form['actions']['actions'][$id]['show'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['type'])) {
        $form['actions']['actions'][$id]['type'] = [
          '#type' => 'select',
          '#options' => [
            'button' => $this->t('Boton'),
            'link' => $this->t('Link'),
          ],
          '#default_value' => $action['type'],
        ];
      }
      else {
        $form['actions']['actions'][$id]['type'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['url'])) {
        $form['actions']['actions'][$id]['url'] = [
          '#type' => 'textfield',
          '#default_value' => $action['url'],
        ];
      }
      else {
        $form['actions']['actions'][$id]['url'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

    }

    /**
     * Mensajes de estado
     */

    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Mensajes de estado'),
      '#open' => TRUE,
    ];

    /**
     * Mensaje de errores
     */

    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Mensajes de estado'),
      '#open' => TRUE,
    ];
    $form['messages']['fulfillment_complete'] = [
      '#type' => 'details',
      '#title' => t('MENSAJES PARA PAGO APROBADO'),
      '#open' => FALSE,
    ];

    $form['messages']['fulfillment_complete']['title'] = [
      '#type' => 'textfield',
      '#default_value' => isset($this->configuration['messages']['fulfillment_complete']['title']) ? $this->configuration['messages']['fulfillment_complete']['title'] : $this->messages['fulfillment_complete']['title'],
    ];

    $form['messages']['fulfillment_complete']['body'] = [
      '#type' => 'textarea',
      '#default_value' => isset($this->configuration['messages']['fulfillment_complete']['body']) ? $this->configuration['messages']['fulfillment_complete']['body'] : $this->messages['fulfillment_complete']['body'],
    ];

    $form['messages']['order_in_progress'] = [
      '#type' => 'details',
      '#title' => t('MENSAJES PARA PAGO EN PROCESO'),
      '#open' => FALSE,
    ];

    $form['messages']['order_in_progress']['title'] = [
      '#type' => 'textfield',
      '#default_value' => isset($this->configuration['messages']['order_in_progress']['title']) ? $this->configuration['messages']['order_in_progress']['title'] : $this->messages['order_in_progress']['title'],
    ];

    $form['messages']['order_in_progress']['body'] = [
      '#type' => 'textarea',
      '#default_value' => isset($this->configuration['messages']['order_in_progress']['body']) ? $this->configuration['messages']['order_in_progress']['body'] : $this->messages['order_in_progress']['body'],
    ];

    $form['messages']['payment_non_complete'] = [
      '#type' => 'details',
      '#title' => t('MENSAJES PARA PAGO NO COMPLETADO'),
      '#open' => FALSE,
    ];

    $form['messages']['payment_non_complete']['title'] = [
      '#type' => 'textfield',
      '#default_value' => isset($this->configuration['messages']['payment_non_complete']['title']) ? $this->configuration['messages']['payment_non_complete']['title'] : $this->messages['payment_non_complete']['title'],
    ];

    $form['messages']['payment_non_complete']['body'] = [
      '#type' => 'textarea',
      '#default_value' => isset($this->configuration['messages']['payment_non_complete']['body']) ? $this->configuration['messages']['payment_non_complete']['body'] : $this->messages['payment_non_complete']['body'],
    ];

    $form['messages']['fulfillment_non_complete'] = [
      '#type' => 'details',
      '#title' => t('MENSAJES PARA FULFILLMENT NO COMPLETADO'),
      '#open' => FALSE,
    ];

    $form['messages']['fulfillment_non_complete']['title'] = [
      '#type' => 'textfield',
      '#default_value' => isset($this->configuration['messages']['fulfillment_non_complete']['title']) ? $this->configuration['messages']['fulfillment_non_complete']['title'] : $this->messages['fulfillment_non_complete']['title'],
    ];

    $form['messages']['fulfillment_non_complete']['body'] = [
      '#type' => 'textarea',
      '#default_value' => isset($this->configuration['messages']['fulfillment_non_complete']['body']) ? $this->configuration['messages']['fulfillment_non_complete']['body'] : $this->messages['fulfillment_non_complete']['body'],
    ];

    $form['messages']['payment_non_complete']['codeMapping'] = [
      '#title' => t('Mapeo de codigos de errores recibios en callbacks'),
      '#type' => 'textarea',
      '#default_value' => isset($this->configuration['messages']['payment_non_complete']['codeMapping']) ? $this->configuration['messages']['payment_non_complete']['codeMapping'] : '',
      '#description' => t('Mapeo de mensajes para codigos de error recibidos en callbacks e.g: 404|Mensaje personalizado'),
    ];

    $form['messages']['fulfillment_non_complete']['codeMapping'] = [
      '#title' => t('Mapeo de codigos de errores recibios en callbacks'),
      '#type' => 'textarea',
      '#default_value' => isset($this->configuration['messages']['fulfillment_non_complete']['codeMapping']) ? $this->configuration['messages']['fulfillment_non_complete']['codeMapping'] : '',
      '#description' => t('Mapeo de mensajes para codigos de error recibidos en callbacks e.g: 404|Mensaje personalizado'),
    ];

    $form['errors'] = [
      '#type' => 'details',
      '#title' => $this->t('Mensaje de errores'),
      '#open' => TRUE,
    ];

    $errors = isset($this->configuration['errors']) ? $this->configuration['errors'] : $this->errors;

    foreach ($errors as $name => $error) {

      $form['errors'][$name]['value'] = [
        '#type' => 'textfield',
        '#title' => $error['label'],
        '#default_value' => $error["value"],
      ];

      $form['errors'][$name]['label'] = [
        '#type' => 'hidden',
        '#title' => $error['label'],
        '#default_value' => $error["label"],
      ];

    }

    $form['errorApi'] = [
      '#type' => 'details',
      '#title' => $this->t('Errores al consultar el API de order'),
      '#open' => FALSE,
      '#weight' => 3,
    ];

    $form['errorApi']['error_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje de error por defecto"),
      '#default_value' => isset($this->configuration['errorApi']['error_default']) ? $this->configuration['errorApi']['error_default'] : '',
    ];

    $form['errorApi']['error_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Mapeo de errores"),
      '#default_value' => isset($this->configuration['errorApi']['error_mapping']) ? $this->configuration['errorApi']['error_mapping'] : '',
      '#rows' => 3,
      '#cols' => 5,
    ];

    //configs
    $form['configs'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuraciones'),
      '#open' => FALSE,
    ];

    $configs = isset($this->configuration['configs']) ? $this->configuration['configs'] : $this->configs;

    foreach ($configs as $key => $config) {
      $form['configs'][$key] = [
        '#type' => 'details',
        '#title' => $key,
        '#open' => FALSE,
      ];
      $form['configs'][$key]['label'] = [
        '#type' => 'hidden',
        '#default_value' => $configs[$key]['label'],
      ];
      $form['configs'][$key]['value'] = [
        '#type' => 'checkbox',
        '#title' => $configs[$key]['label'],
        '#default_value' => $configs[$key]['value'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['fields'] = $form_state->getValue(['data', 'fields']);
    $this->configuration['aditional'] = $form_state->getValue(['aditional', 'fields']);
    $this->configuration['messages'] = $form_state->getValue('messages');
    $this->configuration['errors'] = $form_state->getValue('errors');
    $this->configuration['errorApi'] = $form_state->getValue('errorApi');
    $this->configuration['actions'] = $form_state->getValue(['actions', 'actions']);
    $this->configuration['configs'] = $form_state->getValue('configs');
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldSubmit(array &$form, FormStateInterface $form_state) {
    return $form['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function addContainerCallback(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('count') + 1;
    $form_state->set('count', $count);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function removeContainerCallback(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('count');
    if ($count > 0) {
      $count = $count - 1;
      $form_state->set('count', $count);
      if ($count == 0) {
        $form_state->set('remove', TRUE);
      }
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#cache']['max-age'] = 0;
    return $build;
  }
}
