<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\oneapp_convergent_payment_gateway_tm_invoices\Form\OneappHomePaymentGatewayTmConfigForm;

/**
 * Add the billing form section to the tigo money config form.
 */
class OneappHomePaymentGatewayExpressTmConfigForm extends OneappHomePaymentGatewayTmConfigForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('oneapp.payment_gateway_tigomoney.home_invoices.config');
    /*
     * Formulario de datos de facturación.
     */
    $group = 'billing_form';

    $form[$group] = [
      '#type' => 'details',
      '#title' => $this->t('Formulario de datos de facturación'),
      '#open' => TRUE,
      '#group' => 'bootstrap',
      '#weight' => 4,
    ];

    $form[$group]['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Activar formulario de datos de facturación"),
      '#default_value' => $config->get($group)['show'] ?? TRUE,
    ];

    $form[$group]['overwrite_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Sobreescribir información de facturación de PG"),
      '#default_value' => $config->get($group)['overwrite_data'] ?? FALSE,
    ];
    $form[$group]['fullname'] = [
      '#type' => 'details',
      '#title' => $this->t('Campo nombre'),
      '#open' => FALSE,
    ];
    $form[$group]['fullname']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Mostar campo nombre"),
      '#default_value' => $config->get($group)['fullname']['show'] ?? TRUE,
    ];
    $form[$group]['fullname']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Label para campo fullname"),
      '#default_value' => $config->get($group)['fullname']['label'] ?? '',
    ];
    $form[$group]['fullname']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Placeholder para campo fullname"),
      '#default_value' => $config->get($group)['fullname']['placeholder'] ?? '',
    ];
    $form[$group]['fullname']['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Valor por defecto para campo fullname"),
      '#default_value' => $config->get($group)['fullname']['default'] ?? '',
    ];
    $form[$group]['fullname']['minlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número minimo de caracteres permitidos"),
      '#default_value' => $config->get($group)['fullname']['minlength'] ?? '0',
    ];
    $form[$group]['fullname']['maxlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número máximo de caracteres permitidos"),
      '#default_value' => $config->get($group)['fullname']['maxlength'] ?? '128',
    ];
    $form[$group]['fullname']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Marcar si es un campo obligatorio"),
      '#default_value' => $config->get($group)['fullname']['required'] ?? FALSE,
    ];
    $form[$group]['fullname']['error_message_required'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error requerido"),
      '#default_value' => isset($config->get($group)['fullname']['error_message_required']) ?
      $config->get($group)['fullname']['error_message_required'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['fullname']['error_message_validation'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error en caso de validación"),
      '#default_value' => isset($config->get($group)['fullname']['error_message_validation']) ?
      $config->get($group)['fullname']['error_message_validation'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['fullname']['pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Patrón de validación"),
      '#default_value' => isset($config->get($group)['fullname']['pattern']) ?
      $config->get($group)['fullname']['pattern'] : '',
      '#description' => $this->t('Expresión regular usada como patrón de validación.'),
      '#size' => 15,
    ];

    $form[$group]['nit'] = [
      '#type' => 'details',
      '#title' => $this->t('Nit o documento'),
      '#open' => FALSE,
    ];
    $form[$group]['nit']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Mostrar campo Nit"),
      '#default_value' => $config->get($group)['nit']['show'] ?? TRUE,
    ];
    $form[$group]['nit']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Label para campo nit"),
      '#default_value' => $config->get($group)['nit']['label'] ?? '',
    ];
    $form[$group]['nit']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Placeholder para campo nit"),
      '#default_value' => $config->get($group)['nit']['placeholder'] ?? '',
    ];
    $form[$group]['nit']['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Valor por defecto para campo nit"),
      '#default_value' => $config->get($group)['nit']['default'] ?? '',
    ];
    $form[$group]['nit']['minlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número minimo de caracteres permitidos"),
      '#default_value' => $config->get($group)['nit']['minlength'] ?? '0',
    ];
    $form[$group]['nit']['maxlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número máximo de caracteres permitidos"),
      '#default_value' => $config->get($group)['nit']['maxlength'] ?? '128',
    ];
    $form[$group]['nit']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Marcar si es un campo obligatorio"),
      '#default_value' => $config->get($group)['nit']['required'] ?? FALSE,
    ];
    $form[$group]['nit']['error_message_required'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error requerido"),
      '#default_value' => isset($config->get($group)['nit']['error_message_required']) ?
      $config->get($group)['nit']['error_message_required'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['nit']['error_message_validation'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error en caso de validación"),
      '#default_value' => isset($config->get($group)['nit']['error_message_validation']) ?
      $config->get($group)['nit']['error_message_validation'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['nit']['pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Patrón de validación"),
      '#default_value' => isset($config->get($group)['nit']['pattern']) ?
      $config->get($group)['nit']['pattern'] : '',
      '#description' => $this->t('Expresión regular usada como patrón de validación.'),
      '#size' => 15,
    ];

    $form[$group]['email'] = [
      '#type' => 'details',
      '#title' => $this->t('Email'),
      '#open' => FALSE,
    ];
    $form[$group]['email']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Mostrar campo email"),
      '#default_value' => $config->get($group)['email']['show'] ?? TRUE,
    ];
    $form[$group]['email']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Label para campo email"),
      '#default_value' => $config->get($group)['email']['label'] ?? '',
    ];
    $form[$group]['email']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Placeholder para campo email"),
      '#default_value' => $config->get($group)['email']['placeholder'] ?? '',
    ];
    $form[$group]['email']['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Valor por defecto para campo email"),
      '#default_value' => $config->get($group)['email']['default'] ?? '',
    ];
    $form[$group]['email']['minlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número minimo de caracteres permitidos"),
      '#default_value' => $config->get($group)['email']['minlength'] ?? '0',
    ];
    $form[$group]['email']['maxlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número máximo de caracteres permitidos"),
      '#default_value' => $config->get($group)['email']['maxlength'] ?? '128',
    ];
    $form[$group]['email']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Marcar si es un campo obligatorio"),
      '#default_value' => $config->get($group)['email']['required'] ?? FALSE,
    ];
    $form[$group]['email']['error_message_required'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error requerido"),
      '#default_value' => isset($config->get($group)['email']['error_message_required']) ?
      $config->get($group)['email']['error_message_required'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['email']['error_message_validation'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error en caso de validación"),
      '#default_value' => isset($config->get($group)['email']['error_message_validation']) ?
      $config->get($group)['email']['error_message_validation'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['email']['pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Patrón de validación"),
      '#default_value' => isset($config->get($group)['email']['pattern']) ?
      $config->get($group)['email']['pattern'] : '',
      '#description' => $this->t('Expresión regular usada como patrón de validación.'),
      '#size' => 15,
    ];

    $form[$group]['address'] = [
      '#type' => 'details',
      '#title' => $this->t('Dirección'),
      '#open' => FALSE,
    ];
    $form[$group]['address']['show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Mostrar campo email"),
      '#default_value' => $config->get($group)['address']['show'] ?? TRUE,
    ];
    $form[$group]['address']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Label para campo address"),
      '#default_value' => $config->get($group)['address']['label'] ?? '',
    ];
    $form[$group]['address']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Label para campo address"),
      '#default_value' => $config->get($group)['address']['placeholder'] ?? '',
    ];
    $form[$group]['address']['default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Valor por defecto para campo address"),
      '#default_value' => $config->get($group)['address']['default'] ?? '',
    ];
    $form[$group]['address']['minlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número minimo de caracteres permitidos"),
      '#default_value' => $config->get($group)['address']['minlength'] ?? '0',
    ];
    $form[$group]['address']['maxlength'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Número máximo de caracteres permitidos"),
      '#default_value' => $config->get($group)['address']['maxlength'] ?? '128',
    ];
    $form[$group]['address']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Marcar si es un campo obligatorio"),
      '#default_value' => $config->get($group)['address']['required'] ?? FALSE,
    ];
    $form[$group]['address']['error_message_required'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error requerido"),
      '#default_value' => isset($config->get($group)['address']['error_message_required']) ?
      $config->get($group)['address']['error_message_required'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['address']['error_message_validation'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Mensaje en caso de error en caso de validación"),
      '#default_value' => isset($config->get($group)['address']['error_message_validation']) ?
      $config->get($group)['address']['error_message_validation'] : '',
      '#description' => $this->t('Mensaje a mostrar en caso de que falle la validación del campo.'),
    ];
    $form[$group]['address']['pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Patrón de validación"),
      '#default_value' => isset($config->get($group)['address']['pattern']) ?
      $config->get($group)['address']['pattern'] : '',
      '#description' => $this->t('Expresión regular usada como patrón de validación.'),
      '#size' => 15,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('oneapp.payment_gateway_tigomoney.home_invoices.config')
      ->set('billing_form', $form_state->getValue('billing_form'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

}
