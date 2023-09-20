<?php
namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\adf_block_config\Block\BlockBase;

/**
 * Provides a block for tigomoney configurationu invoices.
 *
 * @Block(
 *   id = "oneapp_convergent_express_payment_postpaid_bo_v2_0_generate_purchaseorders_tigomoney_invoices_block",
 *   admin_label = @Translation("OneApp Convergent Express Payment Gateway Tigomoney Invoices Purchaseorders v2.0 - inicializar Pago"),
 * )
 */
class GeneratePurchaseOrdersTigomoneyInvoicesBlock extends BlockBase {

  /**
   * Action of the form.
   *
   * @var mixed
   */
  protected $actions;

  /**
   * Actions for the tigomoney form.
   *
   * @var mixed
   */
  protected $tigoMoneyFormActions;

  /**
   * Fields to show.
   *
   * @var mixed
   */
  protected $fields;

  /**
   * Error section.
   *
   * @var mixed
   */
  protected $errors;

  /**
   * Fields of the form.
   *
   * @var mixed
   */
  protected $config;

  /**
   * Extra form for errors.
   *
   * @var mixed
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $this->fields = [
      'accountNumber' => [
        'title' => $this->t("Número a recargar"),
        'show' => 1,
        'label' => "Número de línea:",
      ],
      'amount' => [
        'title' => $this->t("Valor de recarga"),
        'show' => 1,
        'label' => "Valor:",
      ],
      'productType' => [
        'title' => $this->t("Tipo de producto"),
        'show' => 1,
        'label' => "Tipo de producto:",
        'value' => "Recarga",
      ],
      'paymentMethod' => [
        'title' => $this->t("Método de pago:"),
        'label' => $this->t('Método de pago:'),
        'value' => "TigoMoney",
        'show' => 1,
      ],
      'wallet' => [
        'title' => $this->t("Billetera:"),
        'label' => $this->t('Billetera:'),
        'value' => "Principal",
        'show' => 1,
      ],
      'purchaseOrderId' => [
        'title' => $this->t("PurchaseOrderId"),
        'label' => $this->t('PurchaseOrderId'),
        'value' => "",
        'show' => 0,
      ],
    ];
    $this->errors = [
      'verifyOtp' => [
        'label' => $this->t('Valida que se envíe el verificationCode OTP'),
        'value' => 'Se requiere validar OTP envie el verificationCode',
      ],
      'invalidOtp' => [
        'label' => $this->t('Valida código OTP'),
        'value' => 'Codigo OTP incorrecto',
      ],
    ];
    $this->config = [
      'templateId' => [
        'label' => $this->t('Selecciona el id de plantilla a mostrar'),
        'title' => $this->t('Id Plantilla'),
        'type' => 'select',
        'show' => TRUE,
      ],
      'confirmationTitle' => [
        'title' => 'Label',
        'label' => $this->t('Resumen'),
        'show' => 1,
      ],
      'orderDetailsTitle' => [
        'title' => 'Label',
        'label' => $this->t('Datos de pago:'),
        'show' => 1,
      ],
      'paymentMethodsTitle' => [
        'title' => 'Label',
        'label' => $this->t('Forma de pago:'),
        'show' => 1,
      ],
      'invoiceDetails' => [
        'title' => 'Label',
        'label' => $this->t('Datos de factura:'),
        'show' => 1,
      ],
      'cancelTransactionTitle' => [
        'title' => 'Label',
        'label' => $this->t('¿Está seguro de que desea cancelar esta transacción?'),
        'show' => 1,
      ],
      'termOfCondition' => [
        'title' => 'Label',
        'label' => $this->t('Al presionar PAGAR estás aceptando los'),
        'show' => 1,
      ],
      'verificationCode' => [
        'title' => 'Label',
        'label' => $this->t('Codigo de  verificación'),
        'show' => 1,
      ],
      'keyWalletTigoMoney' => [
        'title' => 'Label',
        'label' => $this->t('Clave de billetera tigoMoney:'),
        'show' => 1,
      ],
      'tigoMoneyNumber' => [
        'title' => 'Label',
        'label' => $this->t('Número Tigo Money:'),
        'show' => 1,
      ],
    ];
    $this->actions = [
      'change' => [
        'title' => 'Button Cambiar Forma de pago',
        'label' => $this->t('CAMBIAR'),
        'type' => 'link',
        'url' => '/',
        'show' => 1,
      ],
      'changeInvoiceDetails' => [
        'title' => 'Button Cambiar Datos de Factura',
        'label' => $this->t('CAMBIAR'),
        'type' => 'link',
        'url' => '/',
        'show' => 1,
      ],
      'cancel' => [
        'title' => 'Button',
        'label' => $this->t('CANCELAR'),
        'type' => 'link',
        'url' => '/',
        'show' => 1,
      ],
      'purchase' => [
        'title' => 'Button',
        'label' => $this->t('PAGAR'),
        'type' => 'link',
        'url' => '/',
        'show' => 1,
      ],
      'termsOfServices' => [
        'title' => 'Button',
        'label' => $this->t('Al presionar PAGAR estás aceptando los términos y condiciones.'),
        'type' => 'link',
        'url' => '/',
        'show' => 1,
      ],
      'termsOfConditionButton' => [
        'title' => 'Button',
        'label' => $this->t('términos y condiciones.'),
        'type' => 'link',
        'url' => '/',
        'show' => 1,
      ],
    ];
    $this->form = [
      'error_default' => 'En este momento no podemos obtener información de la factura, por favor intente más tarde.',
      'error_mapping' => '',
    ];

    $this->tigoMoneyFormActions = [
      'submit' => [
        'label' => $this->t('CONTINUAR'),
        'type' => 'Button',
        'url' => '/',
        'show' => TRUE,
      ],
      'cancel' => [
        'label' => $this->t('CANCELAR'),
        'type' => 'Button',
        'url' => '/',
        'show' => TRUE,
      ],
    ];

    if (!empty($this->adfDefaultConfiguration())) {
      return $this->adfDefaultConfiguration();
    }
    else {
      return [
        'errors' => $this->errors,
        'config' => $this->config,
        'actions' => $this->actions,
        'form' => $this->form,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {

    $form['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Data'),
      '#open' => TRUE,
    ];
    $form['fields']['fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Campo'), $this->t('Mostrar'), $this->t('Etiqueta'), $this->t('Value'), '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    $fields = isset($this->configuration['fields']) ? $this->configuration['fields'] : $this->fields;

    foreach ($fields as $id => $entity) {
      // Some table columns containing raw markup.
      $form['fields']['fields'][$id]['label_default'] = [
        '#plain_text' => $entity['title'],
      ];

      $form['fields']['fields'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];

      $form['fields']['fields'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
      ];

      $form['fields']['fields'][$id]['title'] = [
        '#type' => 'hidden',
        '#value' => $entity['title'],
      ];
      if (isset($entity['value'])) {
        $form['fields']['fields'][$id]['value'] = [
          '#type' => 'textfield',
          '#value' => $entity['value'],
          '#size' => 20,
        ];
      }
    }

    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Subtítulos'),
      '#open' => TRUE,
    ];
    $form['config']['config'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Campo'), $this->t('Mostrar'), $this->t('Etiqueta'), $this->t('Value'), '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    $config = isset($this->configuration['config']) ? $this->configuration['config'] : $this->config;

    foreach ($config as $id => $entity) {
      // Some table columns containing raw markup.
      $form['config']['config'][$id]['label_default'] = [
        '#plain_text' => $entity['title'],
      ];

      $form['config']['config'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $entity['show'],
      ];

      $form['config']['config'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $entity['label'],
      ];

      $form['config']['config'][$id]['title'] = [
        '#type' => 'hidden',
        '#value' => $entity['title'],
      ];
      if (isset($entity['type'])) {
        $templates_otp = \Drupal::config('oneapp_mobile.otp.config')->get('templates');
        $template_list = [];
        foreach ($templates_otp as $template) {
          $template_list[] = $template['templateId'];
        }
        $form['config']['config'][$id]['type'] = [
          '#type' => 'select',
          '#title' => $this->t('Selecciona el Id de Plantilla'),
          '#default_value' => $entity['type'],
          '#options' => $template_list,
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Botones y enlaces Resumen'),
      '#open' => TRUE,
    ];

    $form['actions']['actions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Campo'), $this->t('Etiqueta'), $this->t('Mostrar'), $this->t('Tipo'), $this->t('Url'), '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    $actions = isset($this->configuration['actions']) ? $this->configuration['actions'] :
    $this->actions;

    foreach ($actions as $id => $action) {

      if (isset($action['title'])) {
        $form['actions']['actions'][$id]['title'] = [
          '#plain_text' => $action['title'],
        ];
      }
      else {
        $form['actions']['actions'][$id]['title'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }
      if (isset($action['label'])) {
        $form['actions']['actions'][$id]['label'] = [
          '#type' => 'textfield',
          '#default_value' => $action['label'],
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

    $form['tigoMoneyFormActions'] = [
      '#type' => 'details',
      '#title' => $this->t('Botones y enlaces TigoMoney Form'),
      '#open' => TRUE,
    ];

    $form['tigoMoneyFormActions']['actions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Campo'), $this->t('Etiqueta'), $this->t('Mostrar'), $this->t('Tipo'),
        $this->t('Url'), '',
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    $tigomoney_form_actions = isset($this->configuration['tigoMoneyFormActions'])
    ? $this->configuration['tigoMoneyFormActions'] : $this->tigoMoneyFormActions;

    foreach ($tigomoney_form_actions as $id => $action) {

      if (isset($action['title'])) {
        $form['tigoMoneyFormActions']['actions'][$id]['title'] = [
          '#plain_text' => $action['title'],
        ];
      }
      else {
        $form['tigoMoneyFormActions']['actions'][$id]['title'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }
      if (isset($action['label'])) {
        $form['tigoMoneyFormActions']['actions'][$id]['label'] = [
          '#type' => 'textfield',
          '#default_value' => $action['label'],
        ];
      }
      else {
        $form['tigoMoneyFormActions']['actions'][$id]['label'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['show'])) {
        $form['tigoMoneyFormActions']['actions'][$id]['show'] = [
          '#type' => 'checkbox',
          '#default_value' => $action['show'],
        ];
      }
      else {
        $form['tigoMoneyFormActions']['actions'][$id]['show'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['type'])) {
        $form['tigoMoneyFormActions']['actions'][$id]['type'] = [
          '#type' => 'select',
          '#options' => [
            'button' => $this->t('Boton'),
            'link' => $this->t('Link'),
          ],
          '#default_value' => $action['type'],
        ];
      }
      else {
        $form['tigoMoneyFormActions']['actions'][$id]['type'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['url'])) {
        $form['tigoMoneyFormActions']['actions'][$id]['url'] = [
          '#type' => 'textfield',
          '#default_value' => $action['url'],
        ];
      }
      else {
        $form['tigoMoneyFormActions']['actions'][$id]['url'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

    }

    // Error messages.
    $form['errors'] = [
      '#type' => 'details',
      '#title' => $this->t('Mensaje de errores'),
      '#open' => TRUE,
    ];
    $errors = isset($this->configuration['errors']) ? $this->configuration['errors'] :
     $this->errors;
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

    $form['he_otp'] = [
      '#type' => 'details',
      '#title' => $this->t('HE/OTP'),
      '#open' => FALSE,
    ];
    $form['he_otp']['disable'] = [
      '#type' => 'details',
      '#title' => $this->t('Bloqueo para usuarios HE/OTP'),
      '#open' => TRUE,
    ];
    $form['he_otp']['disable']['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Bloquear pagos para usuarios HE/OTP"),
      '#default_value' => isset($this->configuration['he_otp']['disable']['value'])
      ? $this->configuration['he_otp']['disable']['value'] : 0,
    ];
    $form['he_otp']['disable']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Mensaje bloqueo HE/OTP"),
      '#default_value' => isset($this->configuration['he_otp']['disable']['message'])
      ? $this->configuration['he_otp']['disable']['message'] : "Debes Iniciar sesión para realizar tus Recargas.",
      '#states' => [
        'visible' => [
          ':input[name="settings[he_otp][disable][value]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['he_otp']['disable']['button'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Label botón bloqueo HE/OTP"),
      '#default_value' => isset($this->configuration['he_otp']['disable']['button'])
      ? $this->configuration['he_otp']['disable']['button'] : 'Iniciar sesión',
      '#states' => [
        'visible' => [
          ':input[name="settings[he_otp][disable][value]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['he_otp']['flow'] = [
      '#type' => 'details',
      '#title' => $this->t('Mensaje para usuarios HE/OTP'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="settings[he_otp][disable][value]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['he_otp']['flow']['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Mensaje por defecto para usuarios HE/OTP"),
      '#default_value' => isset($this->configuration['he_otp']['flow']['message'])
      ? $this->configuration['he_otp']['flow']['message'] : "Inicia sesión para ver y administrar tus tarjetas de crédito.",
      '#states' => [
        'visible' => [
          ':input[name="settings[he_otp][disable][value]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['he_otp']['flow']['button'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Label botón en mensaje para usuarios HE/OTP"),
      '#default_value' => isset($this->configuration['he_otp']['flow']['button']) ?
      $this->configuration['he_otp']['flow']['button'] : 'Iniciar sesión',
      '#states' => [
        'visible' => [
          ':input[name="settings[he_otp][disable][value]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Formulario'),
      '#open' => FALSE,
      '#weight' => 3,
    ];
    $form['form']['error_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje de error por defecto"),
      '#default_value' => isset($this->configuration['form']['error_default']) ? $this->configuration['form']['error_default'] : '',
    ];
    $form['form']['error_mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Mapeo de errores"),
      '#default_value' => isset($this->configuration['form']['error_mapping']) ? $this->configuration['form']['error_mapping'] : '',
      '#rows' => 3,
      '#cols' => 5,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['fields'] = $form_state->getValue(['fields', 'fields']);
    $this->configuration['actions'] = $form_state->getValue(
      ['actions', 'actions']
    );
    $this->configuration['tigoMoneyFormActions'] = $form_state->getValue(
      ['tigoMoneyFormActions', 'actions']
    );
    $this->configuration['config'] = $form_state->getValue(['config', 'config']);
    $this->configuration['errors'] = $form_state->getValue('errors');
    $this->configuration['he_otp'] = $form_state->getValue('he_otp');
    $this->configuration['form'] = $form_state->getValue('form');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
