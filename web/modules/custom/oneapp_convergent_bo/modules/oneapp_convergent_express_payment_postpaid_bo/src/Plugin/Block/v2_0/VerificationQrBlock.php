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
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_verification_qr_block",
 *   admin_label = @Translation("Verificación de pagos QR"),
 * )
 */
class VerificationQrBlock extends BlockBase {

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
    ConfigFactory $config) {
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
    $regex_email = '^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,14}))$';
    $this->contentFields = [
      'fields' => [
        'verifyInformation' => [
          'label' => $this->t('Verifica tu información:'),
          'show' => TRUE,
        ],
        'verifyDescription' => [
          'label' => $this->t('Revisa tus datos antes de que tu pago sea procesado.'),
          'show' => TRUE,
        ],
        'paymentDetails' => [
          'label' => $this->t('Datos de pago:'),
          'show' => TRUE,
          'inputs' => [
            'billPayment' => [
              'title' => "Pago de Factura",
              'label' => "Pago de Factura",
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
            'period' => [
              'title' => "Periodo",
              'label' => "Periodo",
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
            'lineNumber' => [
              'title' => "Número de linea",
              'label' => "Número de linea",
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
            'dueAmount' => [
              'title' => "Valor:",
              'label' => "Valor",
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
              'label' => "QR (Simple)",
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
          ],
        ],
        'paymentReceipt' => [
          'label' => $this->t('Recibo de pago:'),
          'show' => TRUE,
        ],
      ],
      'config' => [
        'actions' => [
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
          'change' => [
            'show' => TRUE,
            'url' => '',
            'label' => $this->t('Cambiar'),
            'title' => $this->t('Cambiar'),
            'type' => 'button',
          ],
        ],
        'form' => [
          'mail' => [
            'title' => $this->t('Correo'),
            'label' => '',
            'placeholder' => $this->t('Correo electrónico'),
            'value' => '',
            'description' => '',
            'error_message_required' => 'Este campo es obligatorio',
            'error_message_validation' => 'Ingrese un correo valido',
            'pattern' => $regex_email,
            'type' => 'text',
            'show' => TRUE,
            'required'  => TRUE,
            'minLength' => '',
            'maxLength' => '',
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
    $this->configFieldsPaymentDetails($form);
    $this->configFieldsPaymentMethods($form);
    $this->configFieldsPaymentReceipt($form);
    $form['config'] = $this->getItemDetail('Configuración', ['#open' => FALSE]);
    $this->configFieldsActions($form);
    $this->addFieldsForm($form);
    $this->configTermsAndConditions($form);

    return $form;
  }

  public function configFields(&$form){
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Ventana de verificación'),
      '#open' => TRUE,
    ];

    $fields = $this->configuration['fields'] ?? $this->contentFields;

    $form['fields']['verifyInformation']['label'] = $this->getItemTextField('Label verificación de información.', $fields['verifyInformation']['label']);
    $form['fields']['verifyInformation']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['verifyInformation']['show']);
  }

  public function configFieldsPaymentDetails(&$form){
    $form['fields']['paymentDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de pago'),
      '#open' => FALSE,
    ];

    $fields = $this->configuration['fields']['paymentDetails']['inputs'] ?? $this->configuration['fields']['paymentDetails'];
    $textLabels = $this->configuration['fields']['paymentDetails'];

    $form['fields']['paymentDetails']['label'] = $this->getItemTextField('Label',$textLabels['label'] ?? $this->configuration['fields']['labelPaymentDetails']['label']);
    $form['fields']['paymentDetails']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['labelPaymentDetails']['show']);

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

  public function configFieldsPaymentMethods(&$form){
    $form['fields']['paymentMethods'] = [
      '#type' => 'details',
      '#title' => $this->t('Forma de pago'),
      '#open' => FALSE,
    ];

    $fields = $this->configuration['fields']['paymentMethods']['inputs'] ?? $this->configuration['fields']['paymentMethods'];
    $textLabels = $this->configuration['fields']['paymentMethods'];

    $form['fields']['paymentMethods']['label'] = $this->getItemTextField('Label',$textLabels['label'] ?? $this->configuration['fields']['labelPaymentMethods']['label']);
    $form['fields']['paymentMethods']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['labelPaymentMethods']['show']);

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

  public function configFieldsPaymentReceipt(&$form){
    $form['fields']['paymentReceipt'] = [
      '#type' => 'details',
      '#title' => $this->t('Recibo de pago'),
      '#open' => FALSE,
    ];

    $textLabels = $this->configuration['fields']['labelPaymentReceipt']['label'] ? $this->configuration['fields']['paymentReceipt'] : $this->contentFields['fields']['paymentReceipt'];

    $form['fields']['paymentReceipt']['label'] = $this->getItemTextField('Label',$textLabels['label'] ?? $this->configuration['fields']['labelPaymentReceipt']['label']);
    $form['fields']['paymentReceipt']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['labelPaymentReceipt']['show']);
  }

  public function configFieldsActions(&$form){
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

  public function configTermsAndConditions(&$form){
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
      }
      elseif ($category === "home") {
        $section_title = "Términos y condiciones - Hogar";
      }
      $form['config']['terms'][$category] = $this->getItemDetail($section_title);
      $form['config']['terms'][$category]['modalTitle'] = $this->getItemTextField('Titulo de ventana emergente', $terms['modalTitle']);
      $node_storage = $this->entityTypeManager->getStorage('node');
      $form['config']['terms'][$category]['modalcontent'] = $this->getItemEntityAutocomplete(
        'Contenido de ventana emergente',
        $node_storage->load($terms['modalcontent']),
        [
          '#tags' => TRUE,
          '#target_type' => 'node',
          '#selection_settings' => [
            'target_bundles' => ['page'],
          ],
        ]
      );
      $form['config']['terms'][$category]['show']
        = $this->getItemCheckBox('Mostrar información de términos y condiciones.', $terms['show']);
      $form['config']['terms'][$category]['label'] = $this->getItemTextField('Label', $terms['label'], $state);
      $form['config']['terms'][$category]['description'] = $this->getItemTextField('Descripción', $terms['description'], $state);
      $form['config']['terms'][$category]['url_text'] = $this->getItemTextField('Texto del enlace', $terms['url_text'], $state);
      $form['config']['terms'][$category]['link'] = $this->getItemTextField('Enlace', $terms['link'], $state);
    }
  }

  /**
   * Submit handler.
   *
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $verifyInformation = $form_state->getValue(['fields','verifyInformation']);
    $labelPaymentDetails = $form_state->getValue(['fields', 'paymentDetails']);
    $labelPaymentMethods = $form_state->getValue(['fields', 'paymentMethods']);
    $labelPaymentReceipt = $form_state->getValue(['fields', 'paymentReceipt']);
    $paymentDetails = $form_state->getValue(['fields','paymentDetails'])['properties'];
    $paymentMethods = $form_state->getValue(['fields','paymentMethods'])['properties'];
    $paymentReceipt = $form_state->getValue(['fields','paymentReceipt'])['properties'];
    $actions = $form_state->getValue(['config', 'actions'])['properties'];
    $terms = [];

    $categories = array_keys($form_state->getValue(['config', 'terms']));
    foreach ($categories as $category) {
      $terms[$category] = $form_state->getValue(['config', 'terms'])[$category];
      $terms[$category]['modalcontent'] = $terms[$category]['modalcontent'][0]['target_id'];
    }

    $this->configuration['fields'] = [
      'verifyInformation' => $verifyInformation,
      'labelPaymentDetails' => $labelPaymentDetails,
      'labelPaymentMethods' => $labelPaymentMethods,
      'labelPaymentReceipt' => $labelPaymentReceipt,
      'paymentDetails' => $paymentDetails,
      'paymentMethods' => $paymentMethods,
      'paymentReceipt' => $paymentReceipt,
    ];

    $this->configuration['config'] = [
      'actions' => $actions,
      'terms' => $terms,
      'form' => $form_state->getValue(['config', 'form'])['properties'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

  /**
   * Fields form configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsForm(array &$form) {
    $fields = $this->configuration['config']['form'] ?? $this->contentFields['config']['form'];

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

    uasort(
      $fields,
      [
        'Drupal\Component\Utility\SortArray',
        'sortByWeightElement',
      ]
    );

    $form['config']['form'] = $this->getItemDetail('Formulario');
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

}
