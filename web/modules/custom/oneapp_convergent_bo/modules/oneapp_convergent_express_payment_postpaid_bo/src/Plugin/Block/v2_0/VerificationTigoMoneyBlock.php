<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\Block\v2_0;
  
use Drupal\adf_block_config\Services\BlockConfigServiceInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\adf_block_config\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'InvoicesBlock'.
 *
 * @Block(
 *    id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_verification_tigo_money_block",
 *   admin_label = @Translation("Verificación de pagos TigoMoney"),
 * )
 */
class VerificationTigoMoneyBlock extends BlockBase{
  /**
   * Content Fields.
   *
   * @var mixed
   */
  protected $contentFields;

  /**
   * Interface for entity type managers.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   Configuration array.
   * @param mixed $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\adf_block_config\Services\BlockConfigServiceInterface $block_config_service
   *   Block config service interface.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      BlockConfigServiceInterface $block_config_service,
      EntityTypeManagerInterface $entity_type_manager,
      ConfigFactory $config
  ) {
      $this->configService = $block_config_service;
      parent::__construct($configuration, $plugin_id, $plugin_definition, $block_config_service);
      $this->entityTypeManager = $entity_type_manager;
      $this->configManager = $config;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
      return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('adf_block_config.config_block'),
        $container->get('entity_type.manager'),
        $container->get('config.factory')
      );
  }
 
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
      $regex_email = '[a-z0-9]+@[a-z]+\.[a-z]{2,3}';
      $this->contentFields = [
      'fields' => [
        'verifyInformation' => [
        'label' => $this->t('Verifica tu información'),
        'show' => TRUE,
      ],
      'verifyDescription' => [
        'label' => $this->t('Revisa tus datos antes de que tu pago sea procesado.'),
          'show' => TRUE,
      ],
      'legend' => [
        'label' => $this->t('Recuerda habilitar tu tarjeta para compras por internet llamando a tu banco.'),
        'show' => TRUE,
      ],
      'paymentDetails' => [
        'label' => $this->t('Datos de pago:'),
          'show' => TRUE,
        'inputs' => [
          'period' => [
          'title' => "Periodo a pagar",
          'label' => "Periodo a pagar",
          'show' => 1,
          'format' => '',
          'weight' => 1,
        ],
        'lineNumber' => [
          'title' => "Número a pagar",
          'label' => "Número a pagar",
          'show' => 1,
          'format' => '',
          'weight' => 1,
        ],
        'dueAmount' => [
          'title' => "Monto a pagar",
          'label' => "Monto",
          'show' => 1,
          'format' => '',
          'weight' => 1,
        ],
        ],
      ],
      'paymentMethods' => [
        'label' => $this->t('Forma de pago:'),
        'show' => TRUE,
        'inputs' => [
          'methods' => [
            'title' => "Método de pago",
            'label' => "Método de pago",
            'show' => 0,
            'format' => '',
            'weight' => 1,
          ],
        ],
      ],
      'invoiceDetails' => [
        'label' => $this->t('Datos de factura:'),
        'show' => FALSE,
      ],
      ],
      'config' => [
        'actions' => [
          'changePaymentMethods' => [
            'show' => TRUE,
            'url' => '',
            'label' => $this->t('Cambiar'),
            'title' => $this->t('Cambiar (Método de Pago)'),
            'type' => 'button',
          ],
          'submit' => [
            'show' => TRUE,
            'url' => '',
            'label' => $this->t('Continuar'),
            'title' => $this->t('Continuar'),
            'type' => 'button',
          ],
          'cancel' => [
            'show' => TRUE,
            'url' => '',
            'label' => $this->t('Cancelar'),
            'title' => $this->t('Cancelar'),
            'type' => 'button',
          ],
        ],
        'title_email' => [
          'label' => $this->t('Titulo email'),
          'show' => TRUE,
        ],
        'form_email' => [
          'input_email' => [
            'title' => $this->t('Email'),
            'label' => 'Email',
            'placeholder' => $this->t('Email'),
            'value' => '',
            'description' => '',
            'error_message_required' => 'Este campo es obligatorio',
            'error_message_validation' => 'Ingrese un correo valido.',
            'pattern' => $regex_email,
            'type' => 'email',
            'show' => TRUE,
            'required'  => TRUE,
            'minLength' => '',
            'maxLength' => '',
          ],
        ],
        'title_otp' => [
          'label' => $this->t('Te enviaremos un código de seguridad a tu numero de cuenta Tigomoney'),
          'show' => TRUE,
        ],
        'form' => [
          'input_number_tigo_money' => [
            'title' => $this->t('Número de Cuenta Tigomoney'),
            'label' => 'Número de Cuenta Tigomoney',
            'placeholder' => $this->t('Número de Cuenta Tigomoney'),
            'value' => '',
            'description' => '',
            'error_message_required' => 'El campo OTP es obligatorio.',
            'error_message_validation' => 'El OTP que ingresaste no es valido. Por favor verificarlo e ingresarlo correctamente.',
            'pattern' => "^[8][0-9]*$",
            'type' => 'number',
            'show' => TRUE,
            'required'  => TRUE,
            'minLength' => '8',
            'maxLength' => '8',
          ],
        ],
        'actions_otp' => [
          'send_code' => [
            'show' => TRUE,
              'url' => '',
              'label' => $this->t('Enviar Código'),
              'title' => $this->t('Enviar Código'),
              'type' => 'button',
          ],
          'cancel_code' => [
            'show' => TRUE,
            'url' => '',
            'label' => $this->t('Cancelar'),
            'title' => $this->t('Cancelar'),
            'type' => 'button',
          ],
        ],
        'terms' => [
            'mobile' => [
            'label' => '',
            'description' => $this->t('Al presionar CONTINUAR éstas aceptando los'),
            'url_text' => $this->t('términos y condiciones'),
            'modalTitle' => $this->t('Términos y condiciones'),
            'modalcontent' => '',
            'link' => '',
            'show' => TRUE,
          ],
          'home' => [
            'label' => '',
            'description' => $this->t('Al presionar CONTINUAR éstas aceptando los'),
            'url_text' => $this->t('términos y condiciones'),
            'modalTitle' => $this->t('Términos y condiciones'),
            'modalcontent' => '',
            'link' => '',
            'show' => TRUE,
          ],
          ],
      ],
      ];
      if (!empty($this->adfDefaultConfiguration())) {
        return $this->adfDefaultConfiguration();
      } else {
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
    $this->configFieldsPaymentDetails($form);
    $this->configFieldsPaymentMethods($form);
    $this->configFieldsPaymentReceipt($form);
    $form['config'] = $this->getItemDetail('Configuración', ['#open' => FALSE]);
    $this->configFieldsActions($form);
    $this->addFieldsForm($form);
    $this->addFieldsFormEmail($form);
    $this->configFieldsActionsOtp($form);
    $this->configTermsAndConditions($form);
    return $form;
  }
  public function configFields(&$form) {
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Ventana de verificación'),
      '#open' => TRUE,
    ];
    $fields = $this->configuration['fields'] ?? $this->contentFields;
    $var_aux = $this->getItemTextField('Label verificación de información.', $fields['verifyInformation']['label']);
    $form['fields']['verifyInformation']['label'] = $var_aux;
    $var_aux = $this->getItemCheckBox('Mostrar Información.', $fields['verifyInformation']['show']);
    $form['fields']['verifyInformation']['show'] = $var_aux;
    $var_aux = $this->getItemTextField('Label descripción de información.', $fields['verifyDescription']['label']);
    $form['fields']['verifyDescription']['label'] = $var_aux;
    $var_aux = $this->getItemCheckBox('Mostrar Información.', $fields['verifyDescription']['show']);
    $form['fields']['verifyDescription']['show'] = $var_aux;
    $form['fields']['legend']['label'] = $this->getItemTextField('Label legend.', $fields['legend']['label']);
    $form['fields']['legend']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['legend']['show']);
    $form['fields']['invoiceDetails']['label'] = $this->getItemTextField('Datos de factura', $fields['invoiceDetails']['label']);
    $var_aux = $this->getItemCheckBox('Mostrar Información.', $fields['invoiceDetails']['show']);
    $form['fields']['invoiceDetails']['show'] = $var_aux;
  }

  public function configFieldsPaymentDetails(&$form) {
    $form['fields']['paymentDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de pago'),
      '#open' => FALSE,
    ];
    $fields = $this->configuration['fields']['paymentDetails']['inputs'] ?? $this->configuration['fields']['paymentDetails'];
    $text_labels = $this->configuration['fields']['paymentDetails'];
    $var_aux = $this->getItemTextField('Label', $text_labels['label'] ?? $this->configuration['fields']['labelPaymentDetails']['label']);
    $form['fields']['paymentDetails']['label'] = $var_aux;
    $var_aux2 = $this->configuration['fields']['labelPaymentDetails']['show'];
    $var_aux = $this->getItemCheckBox('Mostrar Información.', $text_labels['show'] ??  $var_aux2);
    $form['fields']['paymentDetails']['show'] =  $var_aux;
    $header = [
      $this->t('Campo'),
      $this->t('Etiqueta'),
      $this->t('Mostrar'),
      $this->t('Weight'),
      '',
    ];
    $form['fields']['paymentDetails']['properties'] = $this->getItemTable($header);
    foreach ($fields as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['label'] = $this->getItemTextField('', $entity['label']);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);
      $form['fields']['paymentDetails']['properties'][$id] = $item;
    }
  }
  public function configFieldsPaymentMethods(&$form) {
    $form['fields']['paymentMethods'] = [
      '#type' => 'details',
      '#title' => $this->t('Forma de pago'),
      '#open' => FALSE,
    ];
    $fields = $this->configuration['fields']['paymentMethods']['inputs'] ?? $this->configuration['fields']['paymentMethods'];
    $text_labels = $this->configuration['fields']['paymentMethods'];
    $var_aux = $this->getItemTextField('Label', $text_labels['label'] ?? $this->configuration['fields']['labelPaymentMethods']['label']);
    $form['fields']['paymentMethods']['label'] = $var_aux;
    $var_aux2 = $this->configuration['fields']['labelPaymentMethods']['show'];
    $var_aux = $this->getItemCheckBox('Mostrar Información.', $text_labels['show'] ?? $var_aux2);
    $form['fields']['paymentMethods']['show'] = $var_aux;
    $header = [
      $this->t('Campo'),
      $this->t('Etiqueta'),
      $this->t('Mostrar'),
      $this->t('Weight'),
      '',
    ];
    $form['fields']['paymentMethods']['properties'] = $this->getItemTable($header);
    foreach ($fields as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['label'] = $this->getItemTextField('', $entity['label']);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);
      $form['fields']['paymentMethods']['properties'][$id] = $item;
    }
  }

  public function configFieldsPaymentReceipt(&$form) {
    $form['fields']['legend'] = [
      '#type' => 'details',
      '#title' => $this->t('Leyenda'),
      '#open' => TRUE,
    ];
    $form['fields']['invoiceDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de factura'),
      '#open' => TRUE,
    ];
    $fields = $this->configuration['fields']['invoiceDetails']['inputs'] ?? $this->contentFields['fields']['invoiceDetails']['inputs'];
    $fields_invoice_details_label = $this->configuration['fields']['invoiceDetails']['label'];
    $fields_invoice_details = $this->configuration['fields']['invoiceDetails'];
    $content_fields_invoice_details = $this->contentFields['fields']['invoiceDetails'];
    $var_aux = $this->configuration['fields']['invoiceDetails']['label'];
    $text_labels = $var_aux ? $this->configuration['fields']['invoiceDetails'] : $this->contentFields['fields']['invoiceDetails'];
    $text_labels = $fields_invoice_details_label ? $fields_invoice_details : $content_fields_invoice_details;
    $var_aux = $this->getItemTextField('Label', $text_labels['label'] ?? $this->configuration['fields']['invoiceDetails']['label']);
    $form['fields']['invoiceDetails']['label'] = $var_aux;
    $var_aux2 = $this->configuration['fields']['invoiceDetails']['show'];
    $var_aux = $this->getItemCheckBox('Mostrar Información.', $text_labels['show'] ?? $var_aux2);
    $form['fields']['invoiceDetails']['show'] = $var_aux;
    $fields = $this->configuration['fields']['legend']['inputs'] ?? $this->contentFields['fields']['legend']['inputs'];
    $var_aux = $this->configuration['fields']['legend']['label'];
    $text_labels = $var_aux ? $this->configuration['fields']['legend'] : $this->contentFields['fields']['legend'];
    $var_aux = $this->configuration['fields']['labellegend']['label'];
    $form['fields']['legend']['label'] = $this->getItemTextField('Label', $text_labels['label'] ?? $var_aux);
    $var_aux = $this->configuration['fields']['labellegend']['show'];
    $form['fields']['legend']['show'] = $this->getItemCheckBox('Mostrar Información.', $text_labels['show'] ?? $var_aux);
  }
  public function configFieldsActions(&$form) {
    $actions = $this->configuration['config']['actions'];
    $size = [
      '#size' => 30,
    ];
    $type = [
      'button' => $this->t('Button'),
      'link' => $this->t('Link'),
    ];
    $header = [
      $this->t('Field'),
      $this->t('label'),
      $this->t('type'),
      $this->t('url'),
      $this->t('Show'),
      '',
    ];
    uasort(
      $actions,
      [
        'Drupal\Component\Utility\SortArray',
        'sortByWeightElement',
      ]
    );
    $form['config']['actions'] = $this->getItemDetail($this->t('Actions'));
    $form['config']['actions']['properties'] = $this->getItemTable($header);
    foreach ($actions as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['Field'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['label'] = $this->getItemTextField('', $entity['label'], $size);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['url'] = $this->getItemTextField('', $entity['url'], $size);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $form['config']['actions']['properties'][$id] = $item;
    }
  }

  public function configTermsAndConditions(&$form) {
    $categories = array_keys($this->configuration['config']['terms']);
    foreach ($categories as $category) {
      $terms = $this->configuration['config']['terms'][$category];
      $show_attribute = ':input[name="settings[config][terms][' . $category . '][show]"]';
      $state = [
        '#states' => [
          'visible' => [
            $show_attribute => ['checked' => TRUE],
          ],
        ],
      ];
      $section_title = "Términos y condiciones";
      if ($category === "mobile") {
        $section_title = "Términos y condiciones - Móvil";
      } elseif ($category === "home") {
        $section_title = "Términos y condiciones - Hogar";
      }
      $form['config']['terms'][$category] = $this->getItemDetail($section_title);
      $form['config']['terms'][$category]['modalTitle'] = $this->getItemTextField('Titulo de ventana emergente', $terms['modalTitle']);
      $node_storage = $this->entityTypeManager->getStorage('node');
      $text_string='Contenido de ventana emergente';
      $arr_aux=[
        '#tags' => TRUE,
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => ['page'],
        ]
      ];
      $aux_store_load=$node_storage->load($terms['modalcontent']);
      $form['config']['terms'][$category]['modalcontent'] = $this->getItemEntityAutocomplete($text_string, $aux_store_load, $arr_aux);
      $text_string='Mostrar información de términos y condiciones.';
      $form['config']['terms'][$category]['show'] = $this->getItemCheckBox($text_string, $terms['show']);
      $form['config']['terms'][$category]['label'] = $this->getItemTextField('Label', $terms['label'], $state);
      $form['config']['terms'][$category]['description'] = $this->getItemTextField('Descripción', $terms['description'], $state);
      $form['config']['terms'][$category]['url_text'] = $this->getItemTextField('Texto del enlace', $terms['url_text'], $state);
      $form['config']['terms'][$category]['link'] = $this->getItemTextField('Enlace', $terms['link'], $state);
  }
  }
  /**
   * Config actions form otp.
   *
   * {@inheritdoc}
   */
  public function configFieldsActionsOtp(&$form) {
    $actions = $this->configuration['config']['actions_otp'];
    $size = [
      '#size' => 30,
    ];
    $type = [
      'button' => $this->t('Button'),
      'link' => $this->t('Link'),
    ];
    $header = [
      $this->t('Field1'),
      $this->t('label2'),
      $this->t('type3'),
      $this->t('url4'),
      $this->t('Show5'),
      '',
    ];
    uasort($actions, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);
    $form['config']['actions_otp'] = $this->getItemDetail($this->t('Actions OTP'));
    $form['config']['actions_otp']['properties'] = $this->getItemTable($header);
    foreach ($actions as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $sufix = $this->contentFields['config']['actions_otp'][$id]['label'];
      $item['Field'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $sufix]);
      $item['label'] = $this->getItemTextField('', $entity['label'], $size);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['url'] = $this->getItemTextField('', $entity['url'], $size);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $form['config']['actions_otp']['properties'][$id] = $item;
    }
  }
  /**
   * Submit handler.
   *
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $verify_information = $form_state->getValue(['fields', 'verifyInformation']);
    $legend = $form_state->getValue(['fields', 'legend']);
    $verify_description = $form_state->getValue(['fields', 'verifyDescription']);
    $verify_information = $form_state->getValue(['fields', 'verifyInformation']);
    $invoice_details = $form_state->getValue(['fields', 'invoiceDetails']);
    $label_payment_details = $form_state->getValue(['fields', 'paymentDetails']);
    $payment_details = $form_state->getValue(['fields', 'paymentDetails'])['properties'];
    $label_payment_methods = $form_state->getValue(['fields', 'paymentMethods']);
    $payment_methods = $form_state->getValue(['fields', 'paymentMethods'])['properties'];
    $actions = $form_state->getValue(['config', 'actions'])['properties'];
    $actions_otp = $form_state->getValue(['config', 'actions_otp'])['properties'];
    $label_otp = $form_state->getValue(['config', 'title_otp']);
    $form_otp = $form_state->getValue(['config', 'form'])['properties'];
    $label_email = $form_state->getValue(['config', 'title_email']);
    $form_email = $form_state->getValue(['config', 'form_email'])['properties'];
    $terms = [];
    $categories = array_keys($form_state->getValue(['config', 'terms']));
    foreach ($categories as $category) {
      $terms[$category] = $form_state->getValue(['config', 'terms'])[$category];
      $terms[$category]['modalcontent'] = $terms[$category]['modalcontent'][0]['target_id'];
    }
    $this->configuration['fields'] = [
      'verifyInformation' => $verify_information,
      'legend' => $legend,
      'verifyDescription' => $verify_description,
      'invoiceDetails' => $invoice_details,
      'labelPaymentDetails' => $label_payment_details,
      'paymentDetails' => $payment_details,
      'labelPaymentMethods' => $label_payment_methods,
      'paymentMethods' => $payment_methods,
    ];
    $this->configuration['config'] = [
      'actions' => $actions,
      'actions_otp' => $actions_otp,
      'label_otp' => $label_otp,
      'form_otp' => $form_otp,
      'label_email' => $label_email,
      'form_email' => $form_email,
      'terms' => $terms,
    ];
  }
  /**
   * Fields form configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsForm(array &$form) {
    $form['config']['title_otp'] = [
      '#type' => 'details',
      '#title' => $this->t('Titulo Formulario OTP'),
      '#open' => FALSE,
    ];
    $fields_title = $this->configuration['config']['label_otp'];
    $var_aux = $fields_title['label'];
    $text_labels = $var_aux ? $this->configuration['config']['label_otp'] : $this->contentFields['config']['title_otp'];
    $var_aux = $this->configuration['config']['label_otp']['label'];
    $form['config']['title_otp']['label'] = $this->getItemTextField('Label', $text_labels['label'] ?? $var_aux);
    $var_aux = $this->configuration['config']['label']['show'];
    $form['config']['title_otp']['show'] = $this->getItemCheckBox('Mostrar Información.', $text_labels['show'] ?? $var_aux);
    $fields = $this->configuration['config']['form_otp'] ?? $this->contentFields['config']['form'];
    $size_small = [
      '#size' => 10,
    ];
    $size_big = [
      '#size' => 25,
    ];
    $size_error_required = [
      '#size' => 25,
    ];
    $size_error_validation = [
      '#size' => 40,
    ];
    $type = [
      'text' => $this->t('Texto'),
      'number' => $this->t('Númerico'),
      'email' => $this->t('Correo electrónico'),
    ];
    $header = [
      $this->t('Field'),
      $this->t('label'),
      $this->t('placeholder'),
      $this->t('Valor'),
      $this->t('descripción'),
      $this->t('mensaje error requerido'),
      $this->t('mensaje error validación'),
      $this->t('patrón'),
      $this->t('tipo'),
      $this->t('Show'),
      $this->t('Campo requerido'),
      $this->t('Cantidad mínima de caracteres'),
      $this->t('Cantidad máxima de caracteres'),
      $this->t('Weight'),
      '',
    ];

    uasort($fields, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);
    $form['config']['form'] = $this->getItemDetail('Formulario OTP');
    $form['config']['form']['properties'] = $this->getItemTable($header);
    foreach ($fields as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['label'] = $this->getItemTextField('', $entity['label'], $size_small);
      $item['placeholder'] = $this->getItemTextField('', $entity['placeholder'], $size_big);
      $item['value'] = $this->getItemTextField('', $entity['value'], $size_small);
      $item['description'] = $this->getItemTextField('', $entity['description'], $size_big);
      $item['error_message_required'] = $this->getItemTextField('', $entity['error_message_required'], $size_error_required);
      $item['error_message_validation'] = $this->getItemTextField('', $entity['error_message_validation'], $size_error_validation);
      $item['pattern'] = $this->getItemTextArea('', $entity['pattern'], $size_big);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['required'] = $this->getItemCheckBox('', $entity['required']);
      $item['minLength'] = $this->getItemTextField('', $entity['minLength'], $size_small);
      $item['maxLength'] = $this->getItemTextField('', $entity['maxLength'], $size_small);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);
      $form['config']['form']['properties'][$id] = $item;
    }
  }
  /**
   * Fields form configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsFormEmail(array &$form) {
    $form['config']['title_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Titulo Formulario Email'),
      '#open' => FALSE,
    ];
    $fields_title = $this->configuration['config']['label_email'];
    $var_aux = $fields_title['label'];
    $text_labels = $var_aux ? $this->configuration['config']['label_email'] : $this->contentFields['config']['title_email'];
    $var_aux = $this->configuration['config']['label_email']['label'];
    $form['config']['title_email']['label'] = $this->getItemTextField('Label', $text_labels['label'] ?? $var_aux);
    $var_aux = $this->configuration['config']['label']['show'];
    $form['config']['title_email']['show'] = $this->getItemCheckBox('Mostrar Información.', $text_labels['show'] ?? $var_aux);
    $fields = $this->configuration['config']['form_email'] ?? $this->contentFields['config']['form_email'];
    $size_small = [
      '#size' => 10,
    ];
    $size_big = [
      '#size' => 25,
    ];
    $size_error_required = [
      '#size' => 25,
    ];
    $size_error_validation = [
      '#size' => 40,
    ];
    $type = [
      'text' => $this->t('Texto'),
      'number' => $this->t('Númerico'),
      'email' => $this->t('Correo electrónico'),
    ];
    $header = [
      $this->t('Field'),
      $this->t('label'),
      $this->t('placeholder'),
      $this->t('Valor'),
      $this->t('descripción'),
      $this->t('mensaje error requerido'),
      $this->t('mensaje error validación'),
      $this->t('patrón'),
      $this->t('tipo'),
      $this->t('Show'),
      $this->t('Campo requerido'),
      $this->t('Cantidad mínima de caracteres'),
      $this->t('Cantidad máxima de caracteres'),
      $this->t('Weight'),
      '',
    ];

    uasort($fields, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);
    $form['config']['form_email'] = $this->getItemDetail('Formulario Email');
    $form['config']['form_email']['properties'] = $this->getItemTable($header);
    foreach ($fields as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['label'] = $this->getItemTextField('', $entity['label'], $size_small);
      $item['placeholder'] = $this->getItemTextField('', $entity['placeholder'], $size_big);
      $item['value'] = $this->getItemTextField('', $entity['value'], $size_small);
      $item['description'] = $this->getItemTextField('', $entity['description'], $size_big);
      $item['error_message_required'] = $this->getItemTextField('', $entity['error_message_required'], $size_error_required);
      $item['error_message_validation'] = $this->getItemTextField('', $entity['error_message_validation'], $size_error_validation);
      $item['pattern'] = $this->getItemTextArea('', $entity['pattern'], $size_big);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['required'] = $this->getItemCheckBox('', $entity['required']);
      $item['minLength'] = $this->getItemTextField('', $entity['minLength'], $size_small);
      $item['maxLength'] = $this->getItemTextField('', $entity['maxLength'], $size_small);
      $item['weight'] = $this->getItemWeight('', $entity['weight'], ['#attributes' => ['class' => ['mytable-order-weight']]]);
      $form['config']['form_email']['properties'][$id] = $item;
    }
  }
  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }
}
