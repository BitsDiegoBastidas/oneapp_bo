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
 *    id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_verification_tc_block",
 *   admin_label = @Translation("Verificación de pagos TC"),
 * )
 */
class VerificationTcBlock extends BlockBase
{

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
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
  public function defaultConfiguration()
  {
    $regex_email = '^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,14}))$';
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
              'title' => "Monto a Pagar:",
              'label' => "Monto a Pagar",
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
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
            'name' => [
              'title' => "Nombre",
              'label' => "Nombre del titular de la tarjeta",
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
            'card' => [
              'title' => "Número tarjeta",
              'label' => "Número tarjeta",
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
            'expiration' => [
              'title' => "Vencimiento",
              'label' => "Vencimiento",
              'show' => 1,
              'format' => '',
              'weight' => 1,
            ],
          ],
        ],
        'invoiceDetails' => [
          'label' => $this->t('Datos de factura:'),
          'show' => TRUE,
        ],
        'fullName' => [
          'label' => $this->t('Nombre y apellido'),
          'show' => TRUE,
        ],
        'nit' => [
          'label' => $this->t('NIT'),
          'show' => TRUE,
        ],
        'mail' => [
          'label' => $this->t('Correo Electrónico'),
          'show' => TRUE,
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
  public function adfBlockForm($form, FormStateInterface $form_state)
  {

    $this->configFields($form);
    $this->configFieldsPaymentDetails($form);
    $this->configFieldsPaymentMethods($form);
    $this->configFieldsPaymentReceipt($form);
    $form['config'] = $this->getItemDetail('Configuración', ['#open' => FALSE]);
    $this->configFieldsActions($form);

    $this->configTermsAndConditions($form);

    return $form;
  }

  public function configFields(&$form)
  {
    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Ventana de verificación'),
      '#open' => TRUE,
    ];

    $fields = $this->configuration['fields'] ?? $this->contentFields;

    $form['fields']['verifyInformation']['label'] = $this->getItemTextField('Label verificación de información.', $fields['verifyInformation']['label']);
    $form['fields']['verifyInformation']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['verifyInformation']['show']);

    $form['fields']['verifyDescription']['label'] = $this->getItemTextField('Label descripción de información.', $fields['verifyDescription']['label']);
    $form['fields']['verifyDescription']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['verifyDescription']['show']);

    $form['fields']['legend']['label'] = $this->getItemTextField('Label legend.', $fields['legend']['label']);
    $form['fields']['legend']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['legend']['show']);

    $form['fields']['fullName']['label'] = $this->getItemTextField('Label nombre completo..', $fields['fullName']['label']);
    $form['fields']['fullName']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['fullName']['show']);

    $form['fields']['invoiceDetails']['label'] = $this->getItemTextField('Datos de factura', $fields['invoiceDetails']['label']);
    $form['fields']['invoiceDetails']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['invoiceDetails']['show']);

    $form['fields']['nit']['label'] = $this->getItemTextField('Label nit.', $fields['nit']['label']);
    $form['fields']['nit']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['nit']['show']);

    $form['fields']['mail']['label'] = $this->getItemTextField('Label correo electrónico.', $fields['mail']['label']);
    $form['fields']['mail']['show'] = $this->getItemCheckBox('Mostrar Información.', $fields['mail']['show']);
  }

  public function configFieldsPaymentDetails(&$form)
  {
    $form['fields']['paymentDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de pago'),
      '#open' => FALSE,
    ];

    $fields = $this->configuration['fields']['paymentDetails']['inputs'] ?? $this->configuration['fields']['paymentDetails'];
    $textLabels = $this->configuration['fields']['paymentDetails'];

    $form['fields']['paymentDetails']['label'] = $this->getItemTextField('Label', $textLabels['label'] ?? $this->configuration['fields']['labelPaymentDetails']['label']);
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

  public function configFieldsPaymentMethods(&$form)
  {
    $form['fields']['paymentMethods'] = [
      '#type' => 'details',
      '#title' => $this->t('Forma de pago'),
      '#open' => FALSE,
    ];
    $fields = $this->configuration['fields']['paymentMethods']['inputs'] ?? $this->configuration['fields']['paymentMethods'];
    $textLabels = $this->configuration['fields']['paymentMethods'];

    $form['fields']['paymentMethods']['label'] = $this->getItemTextField('Label', $textLabels['label'] ?? $this->configuration['fields']['labelPaymentMethods']['label']);
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

  public function configFieldsPaymentReceipt(&$form)
  {
    $form['fields']['legend'] = [
      '#type' => 'details',
      '#title' => $this->t('Leyenda'),
      '#open' => TRUE,
    ];
    $form['fields']['fullName'] = [
      '#type' => 'details',
      '#title' => $this->t('Nombre Completo'),
      '#open' => TRUE,
    ];
    $form['fields']['invoiceDetails'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos de factura'),
      '#open' => TRUE,
    ];
    $form['fields']['nit'] = [
      '#type' => 'details',
      '#title' => $this->t('Nit'),
      '#open' => TRUE,
    ];
    $form['fields']['mail'] = [
      '#type' => 'details',
      '#title' => $this->t('Correo Electrónico'),
      '#open' => TRUE,
    ];
    $fields = $this->configuration['fields']['fullName']['inputs'] ?? $this->contentFields['fields']['fullName']['inputs'];
    $textLabels = $this->configuration['fields']['fullName']['label'] ? $this->configuration['fields']['fullName'] : $this->contentFields['fields']['fullName'];
    $form['fields']['fullName']['label'] = $this->getItemTextField('Label', $textLabels['label'] ?? $this->configuration['fields']['fullName']['label']);
    $form['fields']['fullName']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['fullName']['show']);
      

    $fields = $this->configuration['fields']['invoiceDetails']['inputs'] ?? $this->contentFields['fields']['invoiceDetails']['inputs'];
    $textLabels = $this->configuration['fields']['invoiceDetails']['label'] ? $this->configuration['fields']['invoiceDetails'] : $this->contentFields['fields']['invoiceDetails'];
    $form['fields']['invoiceDetails']['label'] = $this->getItemTextField('Label', $textLabels['label'] ?? $this->configuration['fields']['invoiceDetails']['label']);
    $form['fields']['invoiceDetails']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['invoiceDetails']['show']);
  
    $fields = $this->configuration['fields']['mail']['inputs'] ?? $this->contentFields['fields']['mail']['inputs'];
    $textLabels = $this->configuration['fields']['mail']['label'] ? $this->configuration['fields']['mail'] : $this->contentFields['fields']['mail'];
    $form['fields']['mail']['label'] = $this->getItemTextField('Label', $textLabels['label'] ?? $this->configuration['fields']['mail']['label']);
    $form['fields']['mail']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['mail']['show']);


    $fields = $this->configuration['fields']['nit']['inputs'] ?? $this->contentFields['fields']['nit']['inputs'];
    $textLabels = $this->configuration['fields']['nit']['label'] ? $this->configuration['fields']['nit'] : $this->contentFields['fields']['nit'];
    $form['fields']['nit']['label'] = $this->getItemTextField('Label', $textLabels['label'] ?? $this->configuration['fields']['nit']['label']);
    $form['fields']['nit']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['nit']['show']);



    $fields = $this->configuration['fields']['legend']['inputs'] ?? $this->contentFields['fields']['legend']['inputs'];
    $textLabels = $this->configuration['fields']['legend']['label'] ? $this->configuration['fields']['legend'] : $this->contentFields['fields']['legend'];



    $form['fields']['legend']['label'] = $this->getItemTextField('Label', $textLabels['label'] ?? $this->configuration['fields']['labellegend']['label']);
    $form['fields']['legend']['show'] = $this->getItemCheckBox('Mostrar Información.', $textLabels['show'] ?? $this->configuration['fields']['labellegend']['show']);
  }

  public function configFieldsActions(&$form)
  {
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

  public function configTermsAndConditions(&$form)
  {
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
  public function adfBlockSubmit($form, FormStateInterface $form_state)
  {
    $verifyInformation = $form_state->getValue(['fields', 'verifyInformation']);
    $legend = $form_state->getValue(['fields', 'legend']);
    $verifyDescription = $form_state->getValue(['fields', 'verifyDescription']);
    $verifyInformation = $form_state->getValue(['fields', 'verifyInformation']);
    $invoiceDetails = $form_state->getValue(['fields', 'invoiceDetails']);
    $fullName = $form_state->getValue(['fields', 'fullName']);
    $nit = $form_state->getValue(['fields', 'nit']);
    $mail = $form_state->getValue(['fields', 'mail']);
    $labelPaymentDetails = $form_state->getValue(['fields', 'paymentDetails']);
    $labelPaymentMethods = $form_state->getValue(['fields', 'paymentMethods']);
    $paymentDetails = $form_state->getValue(['fields', 'paymentDetails'])['properties'];
    $paymentMethods = $form_state->getValue(['fields', 'paymentMethods'])['properties'];
    $actions = $form_state->getValue(['config', 'actions'])['properties'];
    $terms = [];

    $categories = array_keys($form_state->getValue(['config', 'terms']));
    foreach ($categories as $category) {
      $terms[$category] = $form_state->getValue(['config', 'terms'])[$category];
      $terms[$category]['modalcontent'] = $terms[$category]['modalcontent'][0]['target_id'];
    }

    $this->configuration['fields'] = [
      'verifyInformation' => $verifyInformation,
      'legend' => $legend,
      'verifyDescription' => $verifyDescription,
      'invoiceDetails' => $invoiceDetails,
      'fullName' => $fullName,
      'nit' => $nit,
      'mail' => $mail,
      'labelPaymentDetails' => $labelPaymentDetails,
      'labelPaymentMethods' => $labelPaymentMethods,
      'paymentDetails' => $paymentDetails,
      'paymentMethods' => $paymentMethods,
    ];

    $this->configuration['config'] = [
      'actions' => $actions,
      'terms' => $terms,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    return [];
  }
}
