<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Plugin\Block\v2_0;

use Drupal\Core\Form\FormStateInterface;
use Drupal\adf_block_config\Block\BlockBase;
use Drupal\oneapp_convergent_payment_gateway_qr\Plugin\Block\v2_0\GenerateCodeQRBlock;
use Drupal\oneapp_mobile_payment_gateway_packets\Plugin\rest\resource\v2_0\GeneratePurchaseOrdersPacketsAsyncRestResource;

/**
 * Provides a 'GenerateCodeQRExpressBlock'.
 *
 * @Block(
 *   id = "oneapp_convergent_express_payment_pospaid_qr_generate_code_qr_v2_0_block",
 *   admin_label = @Translation("OneApp Convergent Express Payment Generate code QR (QR Block) v2.0"),
 * )
 */
class GenerateCodeQRExpressBlock extends GenerateCodeQRBlock {

  /**
   * {@inheritdoc}
   */
  protected $fields;

  /**
   * {@inheritdoc}
   */
  protected $actions;

  /**
   * {@inheritdoc}
   */
  protected $configs;

  /**
   * {@inheritdoc}
   */
  protected $messages;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $this->fields = [
      'purchaseorderId' => [
        'title' => $this->t('Purchase order id'),
        'label' => 'Purchase order id',
        'show' => FALSE,
      ],
      'transactionId' => [
        'title' => $this->t('Número de transacción'),
        'label' => 'Número de transacción',
        'show' => FALSE,
      ],
      'qr' => [
        'title' => $this->t('Usa el QR para pagar en otras aplicaciones'),
        'label' => 'Usa el QR para pagar en otras aplicaciones',
        'show' => TRUE,
      ],
      'codeExpiration' => [
        'title' => $this->t('Vencimiento del QR'),
        'label' => 'Tu código vence en 20 mins',
        'show' => TRUE,
      ],
      'url' => [
        'title' => $this->t('Link descargar código QR'),
        'label' => 'Descargar código QR',
        'show' => FALSE,
      ]
    ];

    $this->actions = [
      'actionOne' => [
        'key' => 'save',
        'label' => 'Guardar',
        'show' => TRUE,
        'type' => 'link',
        'url' => '/'
      ],
      'actionTwo' => [
        'key' => 'share',
        'label' => 'Compartir',
        'show' => TRUE,
        'type' => 'link',
        'url' => '/'
      ],
      'actionThree ' => [
        'key' => 'finish',
        'label' => 'Ya realicé el pago',
        'show' => TRUE,
        'type' => 'button',
        'url' => '/'
      ],
      'actionFour' => [
        'key' =>  'help',
        'label' => 'Volver a ver el código',
        'show' => TRUE,
        'type' => 'link',
        'url' => '/'
      ]
    ];

    $this->configs = [
      'hide' => [
        'actionTwo' => [
          'active' => true,
          'label' => $this->t('Boton Compartir'),
          'value' => 'chrome|firefox|opera|safari',
        ],
      ],
      'accountNumber' => [
        'label' => 'accountNumber',
        'description' => 'Enable: iguala el accountNumber al id </br> Disable: accountNumber se obtiene del metodo getAccountNumberForPaymentGatewayFromToken()',
        'value' => FALSE,
      ],
    ];

    $this->messages = [
      'success' => 'Tu código quedó guardado en tu galería de imágenes',
      'error' => 'Ya existe una transacción en proceso',
    ];

    if (!empty($this->adfDefaultConfiguration())) {
      return $this->adfDefaultConfiguration();
    }
    else {
      return [
        'fields' => $this->fields,
        'actions' => $this->actions,
        'configs' => $this->configs,
        'messages' => $this->messages,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockForm($form, FormStateInterface $form_state) {
    $this->configFields($form);
    $this->configActions($form);
    $this->configConfigs($form);
    $this->configMessages($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function adfBlockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['fields'] = $form_state->getValue(['table', 'fields']);
    $this->configuration['actions'] = $form_state->getValue(['container', 'actions']);
    $this->configuration['configs'] = $form_state->getValue('configs');
    $this->configuration['messages'] = $form_state->getValue('messages');
  }

  /**
   * {@inheritdoc}
   */
  public function configFields(&$form) {
    $form['table'] = [
      '#type' => 'details',
      '#title' => $this->t('Campos'),
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
   * {@inheritdoc}
   */
  public function configActions(&$form) {
    $form['container'] = [
      '#type' => 'details',
      '#title' => $this->t('Acciones'),
      '#open' => FALSE,
    ];
    $form['container']['actions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Key'),
        $this->t('Label'),
        $this->t('Url'),
        $this->t('Type'),
        $this->t('Show'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.'),
    ];
    $actions = $this->configuration["actions"];
    $actions = isset($actions) ? $actions :   $this->actions;
    foreach ($actions as $key => $action) {
      $form['container']['actions'][$key]['#attributes']['class'][] = 'draggable';


      $form['container']['actions'][$key]['key'] = [
        '#type' => 'textfield',
        '#default_value' => $action['key'],
      ];

      $form['container']['actions'][$key]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $action['label'],
      ];

      $form['container']['actions'][$key]['url'] = [
        '#type' => 'textfield',
        '#default_value' => $action['url'],
      ];

      $form['container']['actions'][$key]['type'] = [
        '#type' => 'select',
        '#options' => [
          'button' => $this->t('Button'),
          'link' => $this->t('Link'),
        ],
        '#default_value' => $action['type'],
      ];

      $form['container']['actions'][$key]['show'] = [
        '#type' => 'checkbox',
        '#default_value' => $action['show'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function configConfigs(&$form) {

    $configs = $this->configuration["configs"];
    $configs = isset($configs) ? $configs : $this->configs;
    $form['configs'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuraciones'),
      '#open' => FALSE,
    ];

    $form['configs']['hide'] = [
      '#type' => 'details',
      '#title' => $this->t('Ocultar botones'),
      '#open' => TRUE,
    ];

    foreach ($configs['hide'] as $key => $hide_button) {
      $form['configs']['hide'][$key] = [
        '#type' => 'details',
        '#title' => $hide_button['label'],
        '#open' => TRUE,
      ];

      $form['configs']['hide'][$key]['active'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Activar'),
        '#description' => $this->t('Al activar oculta el @browser para los navegadores que indique', ['@browser' => $hide_button['label']]),
        '#default_value' => $configs["hide"][$key]["active"],
      ];

      $form['configs']['hide'][$key]['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Inique en que navegadores ocultar el @browser', ['@browser' => $hide_button['label']]),
        '#description' => $this->t('coloque el nombre de los navegadores separados por (|) params1|params2 '),
        '#default_value' => $configs['hide'][$key]['value'],
        '#states' => [
          'visible' => [
            ':input[name="settings[configs][hide][' . $key . '][active]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $form['configs']['hide'][$key]['label'] = [
        '#type' => 'hidden',
        '#default_value' => $configs['hide'][$key]['label'],
      ];
    }

    $form['configs']['accountNumber'] = [
      '#type' => 'details',
      '#title' => $this->configs['accountNumber']['label'],
      '#open' => FALSE,
    ];

    $form['configs']['accountNumber']['active'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activar'),
      '#description' => $this->t($this->configs['accountNumber']['description']),
      '#default_value' => $configs['accountNumber']['active'] ?? $this->configs['accountNumber']['active'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function configMessages(&$form) {
    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Mensajes'),
      '#open' => FALSE,
    ];

    $messages = $this->configuration["messages"];
    $messages = isset($messages) ? $messages : $this->messages;

    $form['messages']['success'] = [
      '#type' => 'textfield',
      '#title' => t('mensaje de descarga del QR'),
      '#default_value' => $messages['success'],
    ];

    $form['messages']['error'] = [
      '#type' => 'textfield',
      '#title' => t('Mensaje cuando falla el api de generar el código QR'),
      '#default_value' => $messages['error'],
    ];
  }

}

