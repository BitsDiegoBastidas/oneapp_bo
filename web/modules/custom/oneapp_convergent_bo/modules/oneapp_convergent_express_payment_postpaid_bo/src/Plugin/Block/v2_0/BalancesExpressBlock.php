<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\adf_block_config\Block\BlockBase;

/**
 * Provides a 'InvoicesBlock'.
 *
 * @Block(
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_balances_block",
 *   admin_label = @Translation("Selección facturas vencidas a pagar (Balances Block) 2.0"),
 * )
 */
class BalancesExpressBlock extends BlockBase {

  /**
   * Content Fields.
   *
   * @var mixed
   */
  protected $contentFields;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $this->contentFields = [
      'fields' => [
        'invoiceId' => [
          'label' => "invoice Id",
          'value' => '',
          'format' => '',
          'show' => false,
          'weight' => 1,
        ],
        'contractId' => [
          'label' => "contractId",
          'value' => '',
          'format' => '',
          'show' => false,
          'weight' => 2,
        ],
        'billingAccountId' => [
          'label' => "billingAccountId",
          'value' => '',
          'format' => '',
          'show' => false,
          'weight' => 3,
        ],
        'dueAmount' => [
          'label' => 'Deuda total',
          'value' => '',
          'format' => '',
          'show' => true,
          'weight' => 4,
        ],
        'period' => [
          'label' => 'Fecha de periodo',
          'value' => '',
          'format' => '',
          'show' => true,
          'weight' => 5,
        ],
        'invoiceType' => [
          'label' => 'Tipo de Factura',
          'value' => '',
          'format' => '',
          'show' => true,
          'weight' => 6,
        ],
      ],
      'config' => [
        'checkFilters' => [
          'label' => $this->t('Recuerda iniciar la seleccion desde la mas antigua'),
          'show' => TRUE,
          'options' => [
            'checkAll' => [
              'label' => 'Seleccionar todas',
              'value' => false,
              'description' => '',
              'show' => true
            ]
          ]
        ],
        'modal' => [
          'label' => 'Debe seleccionar su factura mas antigua primero',
          'description' => 'Debe seleccionar su factura mas antigua primero',
          'show' => true,
          'actions' => [
            'submit' => [
              'label' => 'Cerrar',
              'type' => 'button',
              'url' => '',
              'show' => true,
              'weight' => 1,
            ]
          ]
        ],
        'actions' => [
          "newQuery" => [
            'label' => 'Nueva consulta',
            'type' => 'link',
            'url' => '',
            'show' => TRUE,
            'weight' => 1,
          ],
          'cancel' => [
            'label' => 'Cancelar',
            'type' => 'button',
            'url' => '',
            'show' => TRUE,
            'weight' => 2,
          ],
          'continue' => [
            'label' => 'Continuar',
            'type' => 'button',
            'url' => '',
            'show' => TRUE,
            'weight' => 3,
          ],
        ],
        'otherFields' => [
          'numberOrCode' => [
            'label' => 'Numero o codigo:',
            'value' => '',
            'description' => '',
            'show' => true,
            'weight' => 1,
          ],
          'totalAmount' => [
            'label' => 'Total a pagar',
            'value' => '',
            'description' => '',
            'show' => true,
            'weight' => 2
          ],
          'device_id' => [
            'label' => $this->t('Device Identifier:'),
            'value' => '',
            'description' => '',
            'show' => TRUE,
            'weight' => 3,
          ],
        ],
        'messages' => [
          'empty' => $this->t('No se encontraron resultados.'),
          'error' => $this->t('En este momento no podemos obtener el balance de deuda, intenta de nuevo más tarde.'),
          'permalink' => $this->t('No tiene autorización para realizar la consulta.'),
        ],
        'permalinkSection' => [
          'label' => $this->t('Permalink'),
        ],
      ],
    ];

    if (!empty($this->adfDefaultConfiguration())) {
      return $this->adfDefaultConfiguration();
    }
    else {
      return $this->contentFields;
    }
  }

  /**
   * Build configuration form.
   *
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {
    $this->configFields($form);
    $form['config'] = $this->getItemDetail('Configuración', ['#open' => FALSE]);
    $this->configCheckFilters($form);
    $this->configModal($form);
    $this->configActions($form);
    $this->configOtherFields($form);
    $this->configMessages($form);
    $this->configPermalink($form);

    return $form;
  }

  public function configFields(&$form) {
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Detalles Facturas'),
      '#open' => FALSE,
    ];

    $form['fields']['fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Etiqueta'),
        $this->t('Valor'),
        $this->t('Formato'),
        $this->t('Mostrar'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'fields-order-weight',
        ],
      ],
    ];

    $fields = $this->configuration['fields'] ?? $this->contentFields;
    uasort($fields, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $utils = \Drupal::service('oneapp.utils');
    $options_format = [
      '' => 'Ninguno',
      'Formato Moneda' => [
        'globalCurrency' => 'Moneda',
        'localCurrency' => 'Moneda Local',
      ],
    ];
    $options_format += ['Formato Fecha' => $utils->getDateFormats()];

    foreach ($fields as $id => $entity) {
      $form['fields']['fields'][$id]['#attributes']['class'][] = 'draggable';
      $form['fields']['fields'][$id]['label_default'] = [
        '#plain_text' => $entity['label'],
      ];

      $form['fields']['fields'][$id]['value'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['value'],
        '#size' => 30,
      ];

      $form['fields']['fields'][$id]['format'] = [
        '#type' => 'select',
        '#options' => $options_format,
        '#default_value' => $entity['format'],
        '#attributes' => ['style' => 'width:125px'],
      ];

      $form['fields']['fields'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];

      $form['fields']['fields'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @label', ['@label' => $entity['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $entity['weight'],
        '#attributes' => ['class' => ['fields-order-weight']],
      ];

      $form['fields']['fields'][$id]['label'] = [
        '#type' => 'hidden',
        '#value' => $entity['label'],
      ];
    }
  }

  public function configCheckFilters(&$form) {
    $config = $this->configuration['config']['checkFilters'];

    $header = [
      $this->t('label'),
      $this->t('Seleccionar'),
      $this->t('Descripcion'),
      $this->t('Show'),
      '',
    ];

    $form['config']['checkFilters'] = $this->getItemDetail('Check filters');
    $form['config']['checkFilters']['label'] = $this->getItemTextField('Label', $config['label']);
    $form['config']['checkFilters']['show'] = $this->getItemCheckBox('Mostrar', $config['show']);

    $form['config']['checkFilters']['properties'] = $this->getItemTable($header);

    foreach ($config['options'] as $id => $entity) {
      $item = [];
      $item['label'] = $this->getItemTextField('', $entity['label']);
      $item['value'] = $this->getItemCheckBox('', $entity['value']);
      $item['description'] = $this->getItemTextField('', $entity['description']);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);

      $form['config']['checkFilters']['properties'][$id] = $item;
    }
  }

  public function configModal(&$form) {
    $config = $this->configuration['config']['modal'];

    $type = [
      'button' => $this->t('Button'),
      'link' => $this->t('Link'),
      'switch' => $this->t('Slide'),
    ];

    $header = [
      $this->t('Label'),
      $this->t('Tipo'),
      $this->t('Url'),
      $this->t('Show'),
      $this->t('Weight'),
      '',
    ];

    $form['config']['modal'] = $this->getItemDetail('Modal');
    $form['config']['modal']['label'] = $this->getItemTextField('Label', $config['label']);
    $form['config']['modal']['description'] = $this->getItemTextField('Descripcion', $config['description']);
    $form['config']['modal']['show'] = $this->getItemCheckBox('Mostrar', $config['show']);

    $form['config']['modal']['properties'] = $this->getItemTable($header);

    foreach ($config['actions'] as $id => $entity) {
      $item = [];
      $item['label'] = $this->getItemTextField('', $entity['label']);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['url'] = $this->getItemTextField('', $entity['url']);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);

      $form['config']['modal']['properties'][$id] = $item;
    }
  }

  public function configActions(&$form) {
    $actions = $this->configuration['config']['actions'];

    $type = [
      'button' => $this->t('Button'),
      'link' => $this->t('Link'),
      'switch' => $this->t('Slide'),
    ];

    $header = [
      $this->t('label'),
      $this->t('type'),
      $this->t('url'),
      $this->t('Show'),
      $this->t('Weight'),
      '',
    ];

    $form['config']['actions'] = $this->getItemDetail('Botones');
    $form['config']['actions']['properties'] = $this->getItemTable($header);

    foreach ($actions as $id => $entity) {
      $item = [];

      $item['label'] = $this->getItemTextField('', $entity['label']);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['url'] = $this->getItemTextField('', $entity['url']);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);

      $form['config']['actions']['properties'][$id] = $item;
    }
  }

  public function configOtherFields(&$form) {
    $other_fields = $this->configuration['config']['otherFields'];

    $header = [
      $this->t('Label'),
      $this->t('Valor'),
      $this->t('Descripcion'),
      $this->t('Show'),
      $this->t('Weight'),
      '',
    ];

    $form['config']['otherFields'] = $this->getItemDetail('Otros Campos');
    $form['config']['otherFields']['properties'] = $this->getItemTable($header);

    foreach ($other_fields as $id => $entity) {
      $item = [];
      $item['label'] = $this->getItemTextField('', $entity['label']);
      $item['value'] = $this->getItemTextField('', $entity['value']);
      $item['description'] = $this->getItemTextField('', $entity['description']);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);

      $form['config']['otherFields']['properties'][$id] = $item;
    }
  }

  public function configMessages(&$form) {
    $messages = $this->configuration['config']['messages'];

    $form['config']['messages'] = $this->getItemDetail('Mensajes');
    $form['config']['messages']['empty'] = $this->getItemTextField('Mensaje cuando no retorna datos', $messages['empty']);
    $form['config']['messages']['error'] = $this->getItemTextField('Mensaje de error', $messages['error']);
    $form['config']['messages']['permalink'] = $this->getItemTextField('Mensaje de error permalink', $messages['permalink']);
  }

  public function configPermalink(&$form) {
    $config = $this->configuration['config']['permalinkSection'];
    $form['config']['permalinkSection'] = $this->getItemDetail('Permalink');
    $form['config']['permalinkSection']['secret'] = $this->getItemTextField('Secret key', $config['secret']);
  }

  /**
   * Submit handler.
   *
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['fields'] = $form_state->getValue(['fields', 'fields']);
    $this->configuration['config'] = [
      'checkFilters' => [
        'label' => $form_state->getValue(['config', 'checkFilters'])['label'],
        'show' => $form_state->getValue(['config', 'checkFilters'])['show'],
        'options' => $form_state->getValue(['config', 'checkFilters'])['properties']
      ],
      'modal' => [
        'label' => $form_state->getValue(['config', 'modal'])['label'],
        'description' => $form_state->getValue(['config', 'modal'])['description'],
        'show' => $form_state->getValue(['config', 'modal'])['show'],
        'actions' => $form_state->getValue(['config', 'modal'])['properties']
      ],
      'actions' => $form_state->getValue(['config', 'actions'])['properties'],
      'otherFields' => $form_state->getValue(['config', 'otherFields'])['properties'],
      'messages' => $form_state->getValue(['config', 'messages']),
      'permalinkSection' => $form_state->getValue(['config', 'permalinkSection']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }
}
