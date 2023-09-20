<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\Block\v2_0;

use Drupal\adf_block_config\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use PSpell\Config;

/**
 * Provides a 'GeneratePurchaseOrdersExpressInvoicesBlock' block.
 *
 * @Block(
 *  id =
 *  "oneapp_convergent_express_payment_postpaid_bo_v2_0_generate_purchase_orders_express_invoices_block",
 *  admin_label = @Translation("OneApp Convergent Payment Gateway Express Invoices Purchaseorders v2.0 - (Inicializar Pagos)"),
 *  group = "oneapp_convergent"
 * )
 */
class GeneratePurchaseOrdersExpressInvoicesBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  protected $fields;

  /**
   * {@inheritdoc}
   */
  protected $errorsMessages;

  /**
   * {@inheritdoc}
   */
  protected $actions;

  /**
   * Popup default config.
   *
   * @var array
   */
  protected $popUpHelpCard;
/**
   * billingForm default config.
   *
   * @var array
   */
  protected $billingForm;
  /**
   * Messages default config.
   *
   * @var array
   */
  protected $viewsMessage;
   /**
   * Categories for term and conditions.
   *
   * @var array
   */
 /**
   * labelBillingForm default config.
   *
   * @var array
   */
  protected $labelBillingForm;
   /**
   * Categories for term and conditions.
   *
   * @var array
   */

  protected $termsAndCond;

  /**
   * notifications attempts payment.
   *
   * @var array
   */
  protected $notificationAttempts;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $regex_email = '[a-z0-9]+@[a-z]+\.[a-z]{2,3}';
    $this->labelBillingForm = [
      'titleBillingForm' => [
        'title' => $this->t('Datos de facturación'),
        'label' => $this->t('Datos de facturación'),
        'show' => TRUE,
      ],
      'detailSummaryBillingForm' => [
        'title' => $this->t('Detalle Formulario'),
        'show' => TRUE,
        'label' => 'Ingresa los datos para que tu factura sea enviada a tu correo electrónico',
      ],
    ];
    $this->billingForm = [
      'billingForm' => [
      'fullName' => [
        'title' => $this->t('Nombre y Apellido'),
        'label' => 'Nombre y Apellido (opcional)',
        'placeholder' => $this->t('Nombre y Apellido'),
        'value' => '',
        'error_message_required' => 'Este campo es obligatorio',
        'type' => 'text',
        'show' => TRUE,
        'required'  => FALSE,
        'minLength' => '',
        'maxLength' => '',
      ],
      'nit' => [
        'title' => $this->t('NIT'),
        'label' => 'NIT (opcional)',
        'placeholder' => $this->t('NIT'),
        'value' => '',
        'error_message_required' => 'Este campo es obligatorio',
        'type' => 'number',
        'show' => TRUE,
        'required'  => FALSE,
        'minLength' => '',
        'maxLength' => '',
      ],
      'mail' => [
        'title' => $this->t('Correo'),
        'label' => 'Correo electrónico (opcional)',
        'placeholder' => $this->t('Correo electrónico'),
        'value' => '',
        'error_message_required' => 'Este campo es obligatorio',
        'error_message_validation' => 'Ingrese un correo valido',
        'pattern' => $regex_email,
        'type' => 'email',
        'show' => TRUE,
        'required'  => FALSE,
        'minLength' => '',
        'maxLength' => '',
      ],
      ]
    ];
    $this->viewsMessage = [
      'title_card_payment' => [
        'title' => $this->t('Titulo pago con tarjeta'),
        'label' => $this->t('Pago con tarjeta'),
        'show' => TRUE,
      ],
      'tigo_acount' => [
        'title' => $this->t('Ya tienes cuenta Tigo'),
        'show' => TRUE,
        'label' => '¿Ya tienes una cuenta Tigo?',
      ],
      'login_tigo_account' => [
        'title' => $this->t('Inicia sesión cuenta tigo'),
        'label' => $this->t('Inicia Sesión con tu correo para ver o guardar tus tarjetas'),
        'show' => TRUE,
      ],
      'cards_accepted' => [
        'title' => $this->t('Tarjetas aceptadas'),
        'label' => $this->t('Tarjetas aceptadas:'),
        'show' => TRUE,
      ],
      'title_valid_info' => [
        'title' => $this->t('Verificar información'),
        'label' => $this->t('Verifica tu información'),
        'show' => TRUE,
      ],
      'text_valid_info' => [
        'title' => $this->t('Descripción verificar información'),
        'label' => $this->t('Revisa tus datos antes de que tu pago sea procesado.'),
        'show' => TRUE,
      ],
      'subtitle_payment_data' => [
        'title' => $this->t('subtítulo datos de pago'),
        'label' => $this->t('Datos de pago:'),
        'show' => TRUE,
      ],
      'field_product_type' => [
        'title' => $this->t('Tipo de producto'),
        'label' => $this->t('Tipo de producto'),
        'show' => TRUE,
      ],
      'field_validity' => [
        'title' => $this->t('Vigencia'),
        'label' => $this->t('Vigencia'),
        'show' => TRUE,
      ],
      'field_line_number' => [
        'title' => $this->t('Número de linea'),
        'label' => $this->t('Número de linea'),
        'show' => TRUE,
      ],
      'field_value' => [
        'title' => $this->t('Valor'),
        'label' => $this->t('Valor'),
        'show' => TRUE,
      ],
      'subtitle_payment_method' => [
        'title' => $this->t('subtítulo forma de pago'),
        'label' => $this->t('Forma de pago:'),
        'show' => TRUE,
      ],
      'field_method_payment' => [
        'title' => $this->t('Método de pago'),
        'label' => $this->t('Método de pago'),
        'show' => TRUE,
      ],
      'field_name' => [
        'title' => $this->t('Nombre'),
        'label' => $this->t('Nombre'),
        'show' => TRUE,
      ],
      'field_card' => [
        'title' => $this->t('Tarjeta'),
        'label' => $this->t('Tarjeta'),
        'show' => TRUE,
      ],
      'field_expiration' => [
        'title' => $this->t('Vencimiento'),
        'label' => $this->t('Vencimiento'),
        'show' => TRUE,
      ],
      'subtitle_billing_data' => [
        'title' => $this->t('subtítulo datos de factura'),
        'label' => $this->t('Datos de factura:'),
        'show' => TRUE,
      ],
      'field_name_surname' => [
        'title' => $this->t('Nombre y apellido'),
        'label' => $this->t('Nombre y apellido'),
        'show' => TRUE,
      ],
      'field_nit' => [
        'title' => $this->t('NIT'),
        'label' => $this->t('NIT'),
        'show' => TRUE,
      ],
      'field_email' => [
        'title' => $this->t('Correo Electrónico'),
        'label' => $this->t('Correo Electrónico'),
        'show' => TRUE,
      ],
      'field_address' => [
        'title' => $this->t('Dirección'),
        'label' => $this->t('Dirección'),
        'show' => FALSE,
      ],
      'cancel_purchase' => [
        'title' => $this->t('Cancelar compra'),
        'label' => $this->t('¿Está seguro de que desea cancelar esta transacción?'),
        'show' => TRUE,
      ],
      'title_billing_data_edit' => [
        'title' => $this->t('Titulo editar datos de facturación'),
        'label' => $this->t('Datos de facturación'),
        'show' => TRUE,
      ],
      'text_billing_data_edit' => [
        'title' => $this->t('Mensaje editar datos de facturación'),
        'label' => $this->t('Ingresa los datos solicitados para que tu factura sea enviada a tu correo electrónico.'),
        'show' => TRUE,
      ],
      'text_help_cvv' => [
        'title' => $this->t('Texto descriptivo de código'),
        'label' => $this->t('Búscalo al reverso'),
        'show' => TRUE,
      ],
      'text_help_expiration_date' => [
        'title' => $this->t('Texto descriptivo de fecha'),
        'label' => $this->t('Búscala en el frente'),
        'show' => TRUE,
      ],

    ];

    $this->actions = [
      'submit' => [
        'title' => 'Continuar',
        'show' => TRUE,
        'label' => 'Continuar',
        'type' => 'button',
      ],
      'cancel' => [
        'title' => 'Cancelar',
        'show' => TRUE,
        'label' => 'Cancelar',
        'type' => 'button',
      ],
      'active_help_pop_up' => [
        'title' => 'Cómo pagar con mi tarjeta',
        'show' => TRUE,
        'label' => '¿Cómo pagar con mi tarjeta?',
        'type' => 'button',
      ],
      'change' => [
        'title' => 'Cambiar',
        'show' => TRUE,
        'label' => 'Cambiar',
        'type' => 'button',
      ],
      'back' => [
        'title' => 'Regresar',
        'show' => TRUE,
        'label' => 'Regresar',
        'type' => 'button',
      ],
      'save' => [
        'title' => 'Guardar',
        'show' => TRUE,
        'label' => 'Guardar',
        'type' => 'button',
      ],
      'pay' => [
        'title' => 'Pagar',
        'show' => TRUE,
        'label' => 'Pagar',
        'type' => 'button',
      ],
      'enter' => [
        'title' => 'Ingresar',
        'show' => TRUE,
        'url' => '',
        'label' => 'Ingresar',
        'type' => 'link',
      ],

    ];

    $this->fields = [
      'productType' => [
        'title' => $this->t("Tipo de producto"),
        'show' => 0,
        'label' => "Tipo de producto",
        'weight' => 1,
      ],
      'accountId' => [
        'title' => $this->t("Número de línea"),
        'show' => 0,
        'label' => "Número de línea",
        'weight' => 1,
      ],
      'invoiceId' => [
        'title' => $this->t("Número a pagar"),
        'show' => 1,
        'label' => "Número a pagar",
        'weight' => 1,
      ],
      'dueAmount' => [
        'title' => $this->t("Monto a pagar"),
        'show' => 1,
        'label' => "Monto",
        'weight' => 1,
      ],
      'period' => [
        'title' => $this->t("Periodo"),
        'show' => 0,
        'label' => "Período",
        'weight' => 1,
      ],
    ];

    $this->errorsMessages = [
      'error_number_card_required' => [
        'title' => $this->t('Número de tarjeta requerido'),
        'label' => 'El número ingresado es incorrecto, asegúrate que sean 16 números',
        'show' => TRUE,
      ],
      'error_number_card_validation' => [
        'title' => $this->t('Número de tarjeta validación'),
        'label' => 'Número a tipo de tarjeta invalido',
        'show' => TRUE,
      ],
      'error_expiration_date_required' => [
        'title' => $this->t('Fecha de vencimiento tarjeta requerido'),
        'label' => 'La fecha es incorrecta',
        'show' => TRUE,
      ],
      'error_expiration_date_validation' => [
        'title' => $this->t('Fecha de vencimiento tarjeta validación'),
        'label' => 'La fecha es incorrecta',
        'show' => TRUE,
      ],
      'error_cvv_required' => [
        'title' => $this->t('Código de verificación tarjeta requerido'),
        'label' => 'El número incorrecto',
        'show' => TRUE,
      ],
      'error_cvv_validation' => [
        'title' => $this->t('Código de Verificación tarjeta validación'),
        'label' => 'El número es incorrecto',
        'show' => TRUE,
      ],
      'error_cardholder_required' => [
        'title' => $this->t('Nombre tarjeta habiente requerido'),
        'label' => 'El nombre ingresado es incorrecto, asegurate que sean al menos 6 caracteres',
        'show' => TRUE,
      ],
      'error_cardholder_validation' => [
        'title' => $this->t('Nombre tarjeta habiente validación'),
        'label' => 'El nombre ingresado es incorrecto, asegurate que sean al menos 6 caracteres',
        'show' => TRUE,
      ],

    ];

    $this->popUpHelpCard = [
      'help_pago_card' => [
        'title' => $this->t('Ayuda pago tarjeta:'),
        'label' => [
          'value' => '
            <ol>
                <li>' . $this->t('Valida que tu Tarjeta esté habilitada para compras por internet.') . '</li>
                <li>' . $this->t('Valida que tu Tarjeta no esté vencida.') . '</li>
                <li>' . $this->t('Llena los datos requeridos.') . '</li>
                <li>' . $this->t('Confirma los datos.') . '</li>
                <li>' . $this->t('Y ¡listo!, puedes disfrutar de tu compra.') . '</li>
            </ol>
            ',
        ],
        'show' => TRUE,
      ],
    ];

    $this->termsAndCond = [
      'mobile' => [
        'url' => '/',
        'prefix' => $this->t('Al presionar CONSULTAR éstas aceptando los'),
        'label' => $this->t('términos y condiciones'),
        'externalUrl' => '',
        'type' => '',
        'show' => TRUE,
      ],
      'home' => [
        'url' => '/',
        'prefix' => $this->t('Al presionar CONSULTAR éstas aceptando los'),
        'label' => $this->t('términos y condiciones'),
        'externalUrl' => '',
        'type' => '',
        'show' => TRUE,
      ],
    ];

    $this->notificationAttempts = [
      'timeout_transaction' => [
        'title' => $this->t("Tiempo maximo de espera para la transacción"),
        'value' => $config['timeout_transaction'] ?? 30,
        'description' => $this->t(
          "Este valor limita el tiempo maximo de espera antes de finalizar la transacción debe llenarse
          el numero de segundos, el valor por defecto es 30"
        ),
      ],
      'retries_transaction' => [
        'title' => $this->t("cantidad de reintentos dentro del tiempo de espera"),
        'value' => $config['retries_transaction'] ?? 6,
        'description' => $this->t(
          "Este valor limita la cantidad de reintentos dentro del tiempo de espera para finalizar la transacción
          reintenta obtener respuesta de la transaccion este número de veces, el valor defecto es 6"
        ),
      ]
    ];

    if (!empty($this->adfDefaultConfiguration())) {
      return $this->adfDefaultConfiguration();
    }
    else {
      return [
        'labelBillingForm' => $this->labelBillingForm,
        'billingForm' => $this->billingForm,
        'viewsMessage' => $this->viewsMessage,
        'actions' => $this->actions,
        'fields' => $this->fields,
        'errorsMessages' => $this->errorsMessages,
        'popUpHelpCard' => $this->popUpHelpCard,
        'termsAndCond' => $this->termsAndCond,
        'notificationAttempts' => $this->notificationAttempts,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {
    $this->fieldsToShow($form);
    $this->actions($form);
    $this->viewMessages($form);
    $this->errors($form);
    $this->popUphelpCardPayment($form);
    $this->termsAndConditions($form);
    $this->validOtp($form);
    $this->addFieldsForm($form);
    $this->notificationAttemptsForm($form);
    $this->labelBillingForm($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['fields'] = $form_state->getValue(['table', 'fields']);
    $this->configuration['billingForm'] = $form_state->getValue(['table', 'fields']);
    $this->configuration['billingForm'] = [
      'billingForm' => $form_state->getValue(['billingForm'])['properties'],
    ];
    $this->configuration['actions'] = $form_state->getValue([
      'details_config',
      'actions',
      'actions',
    ]);
    $this->configuration['errors'] = $form_state->getValue([
      'details_config',
      'errors',
      'fields',
    ]);

    $this->configuration['views_messages'] = $form_state->getValue([
      'details_config',
      'views_messages_payment',
      'fields',
    ]);
    $this->configuration['labelBillingForm'] = $form_state->getValue([
      'details_config',
      'labelBillingForm',
      'fields',
    ]);
    $this->configuration['he_otp'] = $form_state->getValue([
      'details_config',
      'he_otp',
    ]);
    $this->configuration['pop_up_card_payment'] = $form_state->getValue([
      'details_config',
      'pop_up_card_payment',
      'message',
    ]);
    $this->configuration['termsAndConditions'] = $form_state->getValue([
      'details_config',
      'termsAndConditions',
    ]);
    $this->configuration['notificationAttempts'] = $form_state->getValue([
      "details_config",
      "notificationAttempts"])['fields'];
  }

  /**
   * Add the section data to the form.
   *
   * @param array $form
   *   The form array to whom the fields will be added.
   */
  private function fieldsToShow(array &$form) {
    $form['table'] = [
      '#type' => 'details',
      '#title' => $this->t('DATOS'),
      '#open' => FALSE,
    ];
    $form['table']['fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('label'),
        $this->t('Show'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];

    $fields = $this->configuration["fields"];
    $fields = isset($fields) ? $fields : $this->fields;

    foreach ($fields as $id => $field) {
      $form['table']['fields'][$id]['#attributes']['class'][] = 'draggable';
      $form['table']['fields'][$id]['field'] = [
        '#plain_text' => $field['title'],
      ];

      $form['table']['fields'][$id]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $field['label'],
      ];

      $form['table']['fields'][$id]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $field['show'],
      ];

      $form['table']['fields'][$id]['title'] = [
        '#type' => 'hidden',
        '#value' => $field['title'],
      ];
    }
  }

  /**
   * Add the section 'form' to the form.
   *
   * @param array $form
   *   The form array to whom the fields will be added.
   */
  public function addFieldsExtraForm(array &$form) {
    $size = [
      '#size' => 50,
    ];
    $options = [
      '#rows' => 3,
      '#cols' => 5,
    ];
    $form['config']['form'] = $this->getItemDetail($this->t('Formulario'));
    $value = isset($this->configuration['config']['form']['error_default']) ?
    $this->configuration['config']['form']['error_default'] : '';
    $form['config']['form']['error_default'] = $this->getItemTextField($this->t('Mensaje de error por defecto'), $value, $size);
    $value = isset($this->configuration['config']['form']['error_mapping']) ?
    $this->configuration['config']['form']['error_mapping'] : '';
    $form['config']['form']['error_mapping'] = $this->getItemTextArea($this->t('Mapeo de errores'), $value, $options);
  }

  /**
   * Add the section 'Actions' to the form.
   *
   * @param array $form
   *   The form array to whom the fields will be added.
   */
  private function actions(array &$form) {
    $form['details_config']['actions'] = $this->getItemDetail($this->t('Actions'));
    $options_header = [
      $this->t('title'),
      $this->t('Label'),
      $this->t('Show'),
      $this->t('Tipo'),
      $this->t('Url'),
      '',
    ];
    $form['details_config']['actions']['actions'] = $this->getItemTable($options_header);

    $actions = $this->configuration['actions'] ?? $this->actions;
    $type = [
      'button' => $this->t('Button'),
      'link' => $this->t('Link'),
    ];

    foreach ($actions as $id => $action) {
      $form['details_config']['actions']['actions'][$id]['title'] = [
        '#plain_text' => $this->actions[$id]['title'],
      ];
      if (isset($action['label'])) {
        $form['details_config']['actions']['actions'][$id]['label'] =
        $this->getItemTextField('', $action['label']);
      }
      else {
        $form['details_config']['actions']['actions'][$id]['label'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['show'])) {
        $form['details_config']['actions']['actions'][$id]['show'] =
        $this->getItemCheckBox('', $action['show']);
      }
      else {
        $form['details_config']['actions']['actions'][$id]['show'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['type'])) {
        $form['details_config']['actions']['actions'][$id]['type'] =
        $this->getItemSelect('', $action['type'], $type);
      }
      else {
        $form['details_config']['actions']['actions'][$id]['type'] = [
          '#plain_text' => $this->t('Dato no configurable'),
        ];
      }

      if (isset($action['url'])) {
        $form['details_config']['actions']['actions'][$id]['url'] =
        $this->getItemTextField('', $action['url']);
      }
      else {
        $form['details_config']['actions']['actions'][$id]['url'] = [
          '#plain_text' => '',
        ];
      }

    }
  }

  /**
   * Add messages for the form.
   *
   * @param array $form
   *   The form array to whom the fields will be added.
   */
  private function viewMessages(array &$form) {
    $configs = $this->configuration['views_messages'] ?? $this->viewsMessage;
    $options_header = [
      $this->t('Label'),
      $this->t('Show'),
    ];
    $form['details_config']['views_messages_payment'] =
    $this->getItemDetail($this->t('Mensajes vistas pago tarjeta crédito / debito'));

    $form['details_config']['views_messages_payment']['fields'] = $this->getItemTable($options_header);

    foreach ($configs as $id => $field) {
      $item = [];
      $item['title'] = [
        '#plain_text' => $this->viewsMessage[$id]['title'],
      ];
      $item['label'] = $this->getItemTextField('', $field['label']);
      $item['show'] = $this->getItemCheckBox('', $field['show']);
      $form['details_config']['views_messages_payment']['fields'][$id] = $item;
    }
  }

 /**
   * Add messages for the form.
   *
   * @param array $form
   *   The form array to whom the fields will be added.
   */
  private function labelBillingForm(array &$form) {
    $configs = $this->configuration['labelBillingForm'] ?? $this->labelBillingForm;

    $options_header = [
      $this->t('Label'),
      $this->t('Show'),
    ];
    $form['details_config']['labelBillingForm'] =
   $this->getItemDetail($this->t('Titulo y Sumario Formulario'));

   $form['details_config']['labelBillingForm']['fields'] = $this->getItemTable($options_header);

    foreach ($configs as $id => $field) {
      $item = [];

      $item['title'] = [
        '#plain_text' => $this->labelBillingForm[$id]['title'],
      ];
      $item['label'] = $this->getItemTextField('', $field['label']);
      $item['show'] = $this->getItemCheckBox('', $field['show']);

      $form['details_config']['labelBillingForm']['fields'][$id] = $item;
    }

  }

  public function notificationAttemptsForm(array &$form) {
    $notificationAttempts = $this->configuration['notificationAttempts'] ?? $this->notificationAttempts;

    $options_header = [
      $this->t("Label"),
      $this->t("Descripcion"),
      $this->t('Valor'),
    ];

    $form['details_config']['notificationAttempts'] = $this->getItemDetail($this->t('Notificacion intentos payment'));

    $form['details_config']['notificationAttempts']['fields'] = $this->getItemTable($options_header);

    foreach ($notificationAttempts as $id => $field) {
      $item = [];

      $item['title'] = ['#plain_text' => $this->notificationAttempts[$id]['title']];
      $item['description'] = ['#plain_text' => $this->notificationAttempts[$id]['description']];
      $item['value'] = $this->getItemTextField('', $field['value']);

      $form['details_config']['notificationAttempts']['fields'][$id] = $item;
    }
  }



  /**
   * Add the section 'Actions' to the form.
   *
   * @param array $form
   *   The form array to whom the fields will be added.
   */
  private function errors(array &$form) {
    // Error messages.
    $form['details_config']['errors'] = $this->getItemDetail($this->t('Mensaje de errores'));
    $options_header = [
      $this->t('Field'),
      $this->t('Label'),
      $this->t('Show'),
    ];
    $form['details_config']['errors']['fields'] = $this->getItemTable($options_header);

    $errors = $this->configuration['errors'] ?? $this->errorsMessages;

    foreach ($errors as $id => $error) {
      $item = [];
      $item['title'] = [
        '#plain_text' => $this->errorsMessages[$id]['title'],
      ];
      $item['label'] = $this->getItemTextField('', $error['label']);
      $item['show'] = $this->getItemCheckBox('', $error['show']);
      $form['details_config']['errors']['fields'][$id] = $item;
    }
  }

  /**
   * Add pop-up section.
   *
   * @param array $form
   *   Form to add fields.
   */
  private function popUphelpCardPayment(array &$form) {
    $config = $this->configuration['pop_up_card_payment'] ?? $this->popUpHelpCard['help_pago_card'];
    $form['details_config']['pop_up_card_payment'] =
    $this->getItemDetail($this->t('Pop-up ayuda pago tarjeta crédito / debito'));

    $form['details_config']['pop_up_card_payment']['message']['label'] = [
      '#type' => 'text_format',
      '#default_value' => $config['label']['value'],
      '#format' => 'basic_html',
    ];
  }

  /**
   * Add terms and conditions section.
   *
   * @param array $form
   *   Form to add fields.
   */
  private function termsAndConditions(array &$form) {
    // Terms and Conditions Config.
    $categories = array_keys($this->configuration['termsAndConditions'] ?? $this->termsAndCond);
    $types = [
      'button' => $this->t('Button'),
      'link' => $this->t('Link'),
    ];
    foreach ($categories as $category) {
      if (isset($this->configuration)
      && $this->configuration['termsAndConditions'] != NULL) {
        $terms = $this->configuration['termsAndConditions'][$category];
      }
      else {
        $terms = $this->termsAndCond[$category];
      }

      $show_attribute = ':input[name="settings[details_config][termsAndConditions][' . $category . '][show]"]';
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
      $form['details_config']['termsAndConditions'][$category] = $this->getItemDetail($section_title);
      $form['details_config']['termsAndConditions'][$category]['prefix'] =
      $this->getItemTextField($this->t('Prefix'), $terms['prefix'], $state);
      $form['details_config']['termsAndConditions'][$category]['label'] =
      $this->getItemTextField($this->t('Label'), $terms['label'], $state);
      $form['details_config']['termsAndConditions'][$category]['url'] =
      $this->getItemTextField($this->t('Url'), $terms['url'], $state);
      $form['details_config']['termsAndConditions'][$category]['externalUrl'] =
      $this->getItemTextField($this->t('externalUrl'), $terms['externalUrl'], $state);
      $form['details_config']['termsAndConditions'][$category]['type'] =
      $this->getItemSelect($this->t('Tipo'), $terms['type'], $types);
      $form['details_config']['termsAndConditions'][$category]['show']
        = $this->getItemCheckBox($this->t('Mostrar información de términos y condiciones.'), $terms['show']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#cache']['max-age'] = 0;
    return $build;
  }
  /**
   * Fields form configurations.
   *
   * @param array $form
   *   Form to add configuration.
   */
  public function addFieldsForm(array &$form) {
   $fields = $this->configuration['billingForm']['billingForm'] ?? $this->billingForm['billingForm'];
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
      $this->t('mensaje error requerido'),
      $this->t('mensaje error validación'),
      $this->t('patrón'),
      $this->t('tipo'),
      $this->t('Show'),
      $this->t('Campo requerido'),
      $this->t('Cantidad mínima de caracteres'),
      $this->t('Cantidad máxima de caracteres'),
      '',
    ];
    $form['billingForm'] = [
      '#type' => 'details',
      '#title' => $this->t('Formulario'),
      '#open' => FALSE,
    ];
    $form['billingForm']['properties'] = $this->getItemTable($header);
    foreach ($fields as $id => $entity) {
      $item = [];
      $item['#attributes']['class'][] = 'draggable';
      $item['title'] = $this->getItemHidden('', $entity['title'], ['#suffix' => $entity['title']]);
      $item['label'] = $this->getItemTextField('', $entity['label'], $size_small);
      $item['placeholder'] = $this->getItemTextField('', $entity['placeholder'], $size_big);
      $item['value'] = $this->getItemTextField('', $entity['value'], $size_small);
      $item['error_message_required'] = $this->getItemTextField('', $entity['error_message_required'], $size_error_required);
      $item['error_message_validation'] = $this->getItemTextField('', $entity['error_message_validation'], $size_error_validation);
      $item['pattern'] = $this->getItemTextArea('', $entity['pattern'], $size_big);
      $item['type'] = $this->getItemSelect('', $entity['type'], $type);
      $item['show'] = $this->getItemCheckBox('', $entity['show']);
      $item['required'] = $this->getItemCheckBox('', $entity['required']);
      $item['minLength'] = $this->getItemTextField('', $entity['minLength'], $size_small);
      $item['maxLength'] = $this->getItemTextField('', $entity['maxLength'], $size_small);
      $form['billingForm']['properties'][$id] = $item;
    }

  }
  /**
   * Add the HE/OTP section.
   *
   * @param array $form
   *   Form to add fields.
   */
  private function validOtp(array &$form) {
    // Valid otp.
    $form['details_config']['he_otp'] = $this->getItemDetail($this->t('HE/OTP'));
    $form['details_config']['he_otp']['disable'] = $this->getItemDetail($this->t('Bloqueo para usuarios HE/OTP'));
    $default_value = $this->configuration['he_otp']['disable']['value'] ?? 0;
    $form['details_config']['he_otp']['disable']['value'] =
    $this->getItemCheckBox($this->t("Bloquear pagos para usuarios HE/OTP"), $default_value);
    $default_value = $this->configuration['he_otp']['disable']['message'] ?? "Debes Iniciar sesión para realizar tus compras.";
    $state = [
      '#states' => [
        'visible' => [
          ':input[name="settings[he_otp][disable][value]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['details_config']['he_otp']['disable']['message'] =
    $this->getItemTextArea($this->t("Mensaje bloqueo HE/OTP"), $default_value, $state);
    $default_value = $this->configuration['he_otp']['disable']['button'] ?? 'Iniciar sesión';
    $form['details_config']['he_otp']['disable']['button'] =
    $this->getItemTextField($this->t("Label botón bloqueo HE/OTP"), $default_value, $state);
    $state_false = [
      '#states' => [
        'visible' => [
          ':input[name="settings[he_otp][disable][value]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['details_config']['he_otp']['flow'] = $this->getItemDetail($this->t('Mensaje para usuarios HE/OTP'), $state_false);
    $default_value = $this->configuration['he_otp']['flow']['message'] ?? "Inicia sesión para administrar tus tarjetas de crédito.";
    $form['details_config']['he_otp']['flow']['message'] =
    $this->getItemTextArea($this->t("Mensaje por defecto para usuarios HE/OTP"), $default_value, $state_false);
    $default_value = $this->configuration['he_otp']['flow']['button'] ?? 'Iniciar sesión';
    $form['details_config']['he_otp']['flow']['button'] =
    $this->getItemTextField($this->t("Label botón en mensaje para usuarios HE/OTP"), $default_value, $state_false);
  }

}
