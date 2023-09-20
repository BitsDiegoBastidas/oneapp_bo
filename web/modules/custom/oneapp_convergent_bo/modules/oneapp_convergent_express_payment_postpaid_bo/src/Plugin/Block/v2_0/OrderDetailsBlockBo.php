<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\adf_block_config\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config Block for order details.
 *
 * @Block(
 *  id =
 * "oneapp_convergent_express_payment_postpaid_bo_v2_0_order_details_block",
 *  admin_label = @Translation("Resumen de las facturas seleccionadas BO (Order Details Block) 2.0"),
 * )
 */
class OrderDetailsBlockBo extends BlockBase {

  /**
   * Container for fields.
   *
   * @var mixed
   */
  protected $contentFields;

  /**
   * Property to define responsive priority.
   *
   * @var mixed
   */
  protected $priority;

  /**
   * Property to define textfield size.
   *
   * @var mixed
   */
  protected $size;

  /**
   * Property to define headers fields.
   *
   * @var array
   */
  protected $header;

  /**
   * {@inheritdoc}
   *
   * @param $form
   *   this is form
   *
   * @param $form_state
   *   this is form state
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $details_field = [
      'address' => [
        'title' => $this->t('Dirección'),
        'label' => $this->t('Dirección:'),
        'show' => TRUE,
      ],
      'dueDate' => [
        'title' => $this->t('Fecha límite de pago'),
        'label' => $this->t('Fecha límite de pago:'),
        'show' => TRUE,
      ],
      'amount' => [
        'title' => $this->t('Valor Factura'),
        'label' => $this->t('Valor Factura:'),
        'show' => TRUE,
      ],
      'paymentReferrer' => [
        'title' => $this->t('Referente de pago'),
        'label' => $this->t('Referente de pago:'),
        'show' => TRUE,
      ],
      'msisdn' => [
        'title' => $this->t('Número de línea'),
        'label' => $this->t('Número de línea:'),
        'show' => TRUE,
      ],
      'contract' => [
        'title' => $this->t('Número de contrato'),
        'label' => $this->t('Número de contrato:'),
        'show' => FALSE,
      ],
      'period' => [
        'title' => $this->t('Período'),
        'label' => $this->t('Período:'),
        'show' => FALSE,
      ],
    ];
    $this->contentFields = [
      'config' => [
        'summary' => [
          'totalAmount' => [
            'title' => $this->t('Total A pagar'),
            'label' => $this->t('Total A pagar'),
            'show' => TRUE,
          ],
          'invoiceList' => [
            'title' => $this->t('Detalles del pago'),
            'label' => $this->t('Detalles del pago'),
            'show' => TRUE,
          ],
        ],
        'details' => [
          'home' => $details_field,
          'mobile' => $details_field,
        ],
        'form' => [
          'subTitle' => [
            'title' => $this->t('Métodos de pago'),
            'show' => TRUE,
          ],
        ],
        'paymentMethods' => [
          'payment_card' => [
            'title' => $this->t('Tarjeta de Credito'),
            'payment_id' => 0,
            'label' => $this->t('Tarjeta de Crédito/Débito'),
            'img' => $this->t('pt-payment'),
            'icon' => "pt-row-rigth",
            'url' => '/',
            'type' => 'button',
            'show' => TRUE,
          ],
          'payment_tigomoney' => [
            'title' => $this->t('Tigomoney'),
            'payment_id' => 0,
            'label' => $this->t('TigoMoney'),
            'img' => $this->t('brand-pt-tigo-money-full'),
            'icon' => "pt-row-rigth",
            'url' => '/',
            'type' => 'link',
            'show' => TRUE,
          ],
          'payment_qr_simple' => [
            'title' => $this->t('QR Simple'),
            'payment_id' => 0,
            'label' => $this->t('QR simple'),
            'img' => $this->t('QRSIMPLE.png'),
            'icon' => "pt-row-rigth",
            'url' => '/',
            'type' => 'button',
            'show' => TRUE,
          ],
        ],
        'imagePath' => [],
      ],
      'messages_modal_tigo_money' => [
        'fields' => [
          'text_modal_description_tigo_money' => [
            'title' => $this->t('Descripción modal'),
            'label' => $this->t('Introduce el numero asociado a Tigo Money con el que deseas realizar el pago'),
            'show' => TRUE,
          ],
        ],
      ],
      'form_input_modal_tigo_money' => [
        'fields' => [
          'text_placeholder_tigo_money' => [
            'title' => $this->t('Input para ingresar número de TigoMoney'),
            'label' => $this->t('Ingrese el número a pagar'),
            'min_digits' => 7,
            'max_digits' => 8,
            'show' => TRUE,
          ],
        ],
      ],
      'reg_exp_input_modal_tigo_money' => [
        'fields' => [
          'reg_exp_tigo_money' => [
            'title' => $this->t('Validar número ingresado (primer dígito, máximo de dígitos)'),
            'label' => '^[6-7][0-9]*$',
            'show' => TRUE,
          ],
        ],
      ],
      'actions_modal_tigo_money' => [
        'fields' => [
          'cancel_modal_tigo_money' => [
            'title' => $this->t('Botón Cancelar'),
            'label' => $this->t('CANCELAR'),
            'url' => '/',
            'type' => 'button',
            'show' => TRUE,
          ],
          'continue_modal_tigo_money' => [
            'title' => $this->t('Botón Continuar'),
            'label' => $this->t('CONTINUAR'),
            'url' => '/',
            'type' => 'link',
            'show' => TRUE,
          ],
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
   *
   * @param $form
   *   this is form
   *
   * @param $form_state
   *   this is form state
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {
    $form['config'] = $this->getItemDetail('Configuration', ['#open' => FALSE]);
    $this->size = [
      '#size' => 30,
    ];
    $this->header = [
      $this->t('Field'),
      $this->t('label'),
      $this->t('Show'),
      $this->t('Weight'),
      '',
    ];
    $this->addFieldsSummary($form);
    $this->addFieldsDetails($form);
    $this->addImagePath($form);
    $this->addSubTitle($form);
    $this->addFieldsButtons($form);
    $this->formMessageModalTigoMoney($form);
    $this->formInputModalTigoMoney($form);
    $this->formRegExpInputModalTigoMoney($form);
    $this->formActionModalTigoMoney($form);

    return $form;
  }

  /**
   * Fields payment summary to the form.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsSummary(array &$form) {
    $fields = $this->configuration['config']['summary'] ?? $this->contentFields['config']['summary'];

    uasort(
      $fields,
      [
        'Drupal\Component\Utility\SortArray',
        'sortByWeightElement',
      ]
    );
    $form['config']['summary'] = $this->getItemDetail('Resumen del Pago',
    ['#open' => FALSE]);

    $form['config']['summary']['properties'] = $this->getItemTable($this->header);

    foreach ($fields as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['label'] = $this->getItemTextField('', $entity['label'], $this->size);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);

      $form['config']['summary']['properties'][$id] = $item;

    }
  }

  /**
   * Fields payment details to the form.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsDetails(array &$form) {
    $categories = array_keys($this->configuration['config']['details']) ?? array_keys($this->contentFields['config']['details']);
    foreach ($categories as $category) {
      $fields = $this->configuration['config']['details'][$category];
      uasort(
        $fields,
        [
          'Drupal\Component\Utility\SortArray',
          'sortByWeightElement',
        ]
      );
      $detail_title = "Detalles del Pago";
      if ($category === "mobile") {
        $detail_title = "Detalles del Pago Móvil";
      }
      elseif ($category === "home") {
        $detail_title = "Detalles del Pago Hogar";
      }
      $form['config']['details'][$category] = $this->getItemDetail($detail_title, ['#open' => FALSE]);
      $form['config']['details'][$category]['properties'] = $this->getItemTable($this->header);

      foreach ($fields as $id => $entity) {
        $item = [];
        $item['#attributes']['class'][] = 'draggable';
        $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
        $item['label'] = $this->getItemTextField('', $entity['label'], $this->size);
        $item['show'] = $this->getItemCheckBox('', $entity['show']);
        $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);

        $form['config']['details'][$category]['properties'][$id] = $item;
      }
    }
  }

  /**
   * ImagePath(Icons) of payment methods configurations.
   *
   * @param $form
   *   this is form
   */
  public function addImagePath(array &$form) {
    $diffConfig = array_diff_key($this->contentFieldsConfig, $this->configuration['config']);
    $config = array_replace($this->configuration['config'], $diffConfig);
    $size = [
      '#size' => 15,
    ];
    uasort(
        $fields,
        [
          'Drupal\Component\Utility\SortArray',
          'sortByWeightElement',
        ]
    );

    $form['config']['imagePath'] = [
      '#type' => 'details',
      '#title' => $this->t('Iconos'),
      '#open' => FALSE,
    ];
    $form['config']['imagePath']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL para las imágenes'),
      '#default_value' => $config['imagePath']['url'],
    ];

    foreach ($diffConfig as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['url'] = $this->getItemTextField('', $entity['url'], $size);
      $form['config']['imagePath']['properties'][$id] = $item;
    }
  }
  /**
   * Fields form configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addSubTitle(array &$form) {
    $fields = $this->configuration['config']['form'] ?? $this->contentFields['config']['form'];
    $size = [
      '#size' => 15,
    ];
    $header = [
      $this->t('Field'),
      $this->t('Show'),
      $this->t('Weight'),
      '',
    ];

    uasort(
      $fields,
      [
        'Drupal\Component\Utility\SortArray',
        'sortByWeightElement',
      ]
    );

    $form['config']['form'] = $this->getItemDetail('SubTitulo');
    $form['config']['form']['properties'] = $this->getItemTable($header);

    foreach ($fields as $id => $entity) {
      $item = [];

      $item['#attributes']['class'][] = 'draggable';
      $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);

      $form['config']['form']['properties'][$id] = $item;
    }
  }

  /**
   * Settings for payment method buttons.
   *
   * @param $form
   *   this is form
   */
  public function addFieldsButtons(array &$form) {
    $buttons = $this->configuration['config']['paymentMethods'] ? $this->
    configuration['config']['paymentMethods'] : $this->contentFields['config']['paymentMethods'];
    $size = [
      '#size' => 15,
    ];
    $type = [
      'button' => $this->t('Button'),
      'link' => $this->t('Link'),
    ];
    $header = [
      $this->t('field'),
      $this->t('label'),
      $this->t('url'),
      $this->t('type'),
      $this->t('Show'),
      $this->t('multipagos'),
      $this->t('Weight'),
      '',
    ];

    uasort(
        $buttons,
        [
          'Drupal\Component\Utility\SortArray',
          'sortByWeightElement',
        ]
    );

    $form['config']['paymentMethods'] = $this->getItemDetail('Botones');
    $form['config']['paymentMethods']['properties'] = $this
      ->getItemTable(
      $header,
      [
        '#suffix' => '<strong>IMPORTANTE: </strong>Solo subir imágenes de extensión PNG en el administrador de imágenes.',
      ],

    );

    foreach ($buttons as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['paymentMethodName'] = $this->getItemHidden('', $entity['paymentMethodName'], ['#suffix' => $entity['paymentMethodName']]);
      $item['label'] = $this->getItemTextField('', $entity['label'], $size);
      $item['url'] = $this->getItemTextField('', $entity['url'], $size);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['multipay'] = $this->getItemCheckBox('', $entity['multipay']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);
      $item['img'] = $this->getItemTextField('', $entity['img']);
      $item['icon'] = $this->getItemTextField('', $entity['icon']);

      $form['config']['paymentMethods']['properties'][$id] = $item;
    }
  }

  /**
   *  Función auxiliar para crear el formulario de mensajes del modal requerido
   *  para el proceso de pago de TigoMoney
   *
   * @param $form
   *
   * @return void
   */
  private function formMessageModalTigoMoney(&$form) {
    $configs = $this->configuration['messages_modal_tigo_money']['fields'] ?? $this->contentFields['messages_modal_tigo_money']['fields'];
    $options_header = [
      $this->t('Field'),
      $this->t('Label'),
      $this->t('Show'),
    ];

    $form['details_config']['details_messages_modal_tigo_money'] =
      $this->getItemDetail($this->t('Mensajes Modal (Pago TigoMoney)'));

    $form['details_config']['details_messages_modal_tigo_money']['fields'] =
      [
        '#type' => 'table',
        '#header' => $options_header,
        '#responsive' => FALSE,
      ];

    foreach ($configs as $id => $field) {
      $item = [];
      $item['title'] = [
        '#plain_text' => $this->contentFields['messages_modal_tigo_money']['fields'][$id]['title'],
      ];
      $item['label'] = $this->getItemTextField('', $field['label']);
      $item['show'] = $this->getItemCheckBox('', $field['show']);
      $form['details_config']['details_messages_modal_tigo_money']['fields'][$id] = $item;
    }
  }

  /**
   *  Función auxiliar para crear el input del modal requerido
   *  para el proceso de pago de TigoMoney
   *
   * @param $form
   *
   * @return void
   */
  private function formInputModalTigoMoney(&$form) {
    $configs = $this->configuration['form_input_modal_tigo_money']['fields'] ?? $this->contentFields['form_input_modal_tigo_money']['fields'];
    $options_header = [
      $this->t('Field'),
      $this->t('Placeholder '),
      $this->t('Min digits'),
      $this->t('Max digits'),
      $this->t('Show'),
    ];

    $form['details_config']['details_form_input_modal_tigo_money'] =
      $this->getItemDetail($this->t('Form Modal (Pago TigoMoney)'));

    $form['details_config']['details_form_input_modal_tigo_money']['fields'] =
      [
        '#type' => 'table',
        '#header' => $options_header,
        '#responsive' => FALSE,
      ];

    foreach ($configs as $id => $field) {
      $item = [];
      $item['title'] = [
        '#plain_text' => $this->contentFields['form_input_modal_tigo_money']['fields'][$id]['title'],
      ];
      $item['label'] = $this->getItemTextField('', $field['label']);
      $item['min_digits'] = $this->getItemTextField('', $field['min_digits']);
      $item['max_digits'] = $this->getItemTextField('', $field['max_digits']);
      $item['show'] = $this->getItemCheckBox('', $field['show']);
      $form['details_config']['details_form_input_modal_tigo_money']['fields'][$id] = $item;
    }
  }

  /**
   *  Función auxiliar para validar la información input del modal requerido
   *  para el proceso de pago de TigoMoney
   *
   * @param $form
   *
   * @return void
   */
  private function formRegExpInputModalTigoMoney(&$form) {
    $configs = $this->configuration['reg_exp_input_modal_tigo_money']['fields'] ?? $this->contentFields['reg_exp_input_modal_tigo_money']['fields'];

    $options_header = [
      $this->t('field'),
      $this->t('Label'),
      $this->t('Show'),
    ];

    $form['details_config']['details_reg_exp_input_modal_tigo_money'] =
      $this->getItemDetail($this->t('Expresión Regular Validar Número (Pago TigoMoney)'));

    $form['details_config']['details_reg_exp_input_modal_tigo_money']['fields'] = [
      '#type' => 'table',
      '#header' => $options_header,
      '#responsive' => FALSE,
    ];

    foreach ($configs as $id => $field) {
      $item = [];
      $item['title'] = [
        '#plain_text' => $this->contentFields['reg_exp_input_modal_tigo_money']['fields'][$id]['title'],
      ];
      $item['label'] = $this->getItemTextField('', $field['label']);
      $item['show'] = $this->getItemCheckBox('', $field['show']);
      $form['details_config']['details_reg_exp_input_modal_tigo_money']['fields'][$id] = $item;
    }
  }

  /**
   * Función creada para el manejo de los métodos de pago disponibles en la
   * vista de detalle de compra
   *
   * @param $form
   *
   * @return void
   */
  private function formActionModalTigoMoney(&$form) {
    $configs = $this->configuration['actions_modal_tigo_money']['fields'] ?? $this->contentFields['actions_modal_tigo_money']['fields'];
    $options_header = [
      $this->t('Field'),
      $this->t('Label'),
      $this->t('Url'),
      $this->t('Type'),
      $this->t('Show'),
    ];

    $options_type = [
      'button' => $this->t('Botón'),
      'link' => $this->t('Link'),
    ];

    $form['details_config']['details_actions_modal_tigo_money'] =
      $this->getItemDetail($this->t('Actions - Modal (Pago TigoMoney)'));

    $form['details_config']['details_actions_modal_tigo_money']['fields'] =
      [
        '#type' => 'table',
        '#header' => $options_header,
        '#responsive' => FALSE,
      ];

    foreach ($configs as $id => $field) {
      $item = [];
      $item['title'] = [
        '#plain_text' => $this->contentFields['actions_modal_tigo_money']['fields'][$id]['title'],
      ];
      $item['label'] = $this->getItemTextField('', $field['label']);
      $item['url'] = $this->getItemTextField('', $field['url']);
      $item['type'] = $this->getItemSelect('', $field['type'], $options_type);
      $item['show'] = $this->getItemCheckBox('', $field['show']);
      $form['details_config']['details_actions_modal_tigo_money']['fields'][$id] = $item;
    }
  }

  /**
   * Submit handler.
   *
   * {@inheritdoc}
   *
   * @param $form
   *   this is form
   *
   * @param $form_state
   *   this is form state
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $details = [];
    $categories = array_keys($form_state->getValue(['config', 'details']));
    foreach ($categories as $category) {
      $details[$category] = $form_state->getValue(['config', 'details'])[$category]['properties'];
    }
    $this->configuration['config'] = [
      'summary' => $form_state->getValue(['config', 'summary'])['properties'],
      'details' => $details,
      'imagePath' => $form_state->getValue(['config', 'imagePath'])['properties'],
      'form' => $form_state->getValue(['config', 'form'])['properties'],
      'paymentMethods' => $form_state->getValue(['config', 'paymentMethods'])['properties'],

    ];

    $this->configuration['value_messages_modal_tigo_money'] = $form_state->getValue([
      'details_config',
      'details_messages_modal_tigo_money',
      'fields',
    ]);

    $this->configuration['value_form_input_modal_tigo_money'] = $form_state->getValue([
      'details_config',
      'details_form_input_modal_tigo_money',
      'fields',
    ]);
    $this->configuration['value_reg_exp_input_modal_tigo_money'] = $form_state->getValue([
      'details_config',
      'details_reg_exp_input_modal_tigo_money',
      'fields',
    ]);

    $this->configuration['value_actions_modal_tigo_money'] = $form_state->getValue([
      'details_config',
      'details_actions_modal_tigo_money',
      'fields',
    ]);

  }

  /**
   * {@inheritdoc}
   *
   * @return [] structure of the formulary
   */
  public function build() {
    return [];
  }

}
