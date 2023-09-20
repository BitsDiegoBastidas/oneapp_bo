<?php

namespace Drupal\oneapp_convergent_express_payment_postpaid_bo\Services\v2_0;

use Drupal\oneapp\Exception\HttpException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\oneapp\ApiResponse\ErrorBase;
use Drupal\oneapp\Exception\BadRequestHttpException;

/**
 * Class OtpService.
 */
class OtpService {

  /**
   * Default configuration manager.
   *
   * @var Drupal\oneapp_endpoints\Services\ManagerInterface
   */
  protected $manager;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $template;

  /**
   * Default configuration.
   *
   * @var mixed
   */
  protected $utils;
  /**
   * ApiHandler constructor.
   *
   * @param \Drupal\oneapp_endpoints\EndpointManagerInterface $manager
   *   Endpoint manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct($manager, $utils) {
    $this->manager = $manager;
    $this->utils = $utils;
    $this->template = [
      'templateId' => 'default',
      'settings' => [
        'sms' => t('El codigo de confirmacion que solicitaste es: {token}.
         POR TU SEGURIDAD NO LO COMPARTAS CON NADIE.'),
      ],
      'popup_box' => [
        'title' => t('Te hemos enviado un codigo SMS al @msisdn'),
        'message' => t('Necesitamos confirmar que eres el dueño de esta línea.'),
      ],
    ];
  }

  /**
   * Assigns a value to template.
   *
   * @param $template
   *
   * @return void
   */
  private function setTemplate($template) {
    $this->template = $template;
  }

  /**
   * Create the request to send.
   *
   * @param $type
   *  type of request
   * @param $account_number
   *  msisdn or contrac
   * @param $params
   *  parameters
   *
   * @return array
   */
  public function createOtp($account_number, $params) {
    if (isset($params['templateId'])) {
      $this->getSettings($params['templateId']);
    }
    if (!isset($params['templateId']) || is_null($this->template)) {
      $this->sendException("El id del template no es valido.", Response::HTTP_BAD_REQUEST, NULL);
    }
    $token_data = \Drupal::config('oneapp_mobile.otp.config')->get('default');
    if (!isset($account_number) || strlen($account_number) < 5) {
      $this->sendException("El número de cuenta no esta valido.", Response::HTTP_BAD_REQUEST, NULL);
    }
    if (!is_numeric(intval($account_number))) {
      $this->sendException("El número de cuenta no esta valido.", Response::HTTP_BAD_REQUEST, NULL);
    }
    $body = $this->getBody($params);
    $response = $this->callCreateOtpApi($account_number, $body);
    if ($response->status === 1 && $response->action === 'Phone OTP Create') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns array for popup box.
   *
   * @param $msisdn
   *  msisdn
   *
   * @return object
   */
  public function validateOtp($number, $code) {
    $response = $this->callValidateOtp($number, $code);
    if ($response->status === 1 && $response->action === 'OTP Correcto') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns array for popup box.
   *
   * @param $msisdn
   *  msisdn
   *
   * @return object
   */
  public function getPopupBox($msisdn) {
    $title = trim(str_replace("@msisdn", $msisdn, $this->template["popup_box"]["title"]));
    $token_data = \Drupal::config('oneapp_mobile.otp.config')->get('default');
    $actions_btn = isset($this->template["popup_box"]['actions']) ? $this->template["popup_box"]['actions'] : [];
    $actions = [];

    foreach ($actions_btn as $key => $value) {
      if (!strpos($key, 'Show')) {
        $actions[$key] = [
          "label" => $value,
          "type" => "button",
          "show" => ($actions_btn["{$key}Show"]) ? true : false,
        ];
      }
      $actions["cancel"] = [
        "label" => "Cancelar",
        "type" => "button",
        "show" => true
      ];

    }

    return [
      "title" => [
        "value" => $title,
        "show" => true,
      ],
      'description' => [
        "value" => $this->template["popup_box"]["message"],
        "show" => true,
      ],
      "forms" => [
        'time' => [
          "value" => $token_data['timeAttempt'],
          "show" => true,
          "format" => $this->secondToHMS(),
        ],
        "input_code" => [
          "label" => $token_data["input"]["label"],
          "value" => "",
          "show" => true,
          "placeholder" => "",
          "type" => "number",
          "error_message" => [
            "error_message_required" => "Este campo es obligatorio",
            "error_message_validation" => "Ingrese el código enviado"
          ],
          "validations" => [
            "required" => true,
            "maxLength" => $token_data["length"],
            "minLength" => $token_data["length"],
            "pattern" => "/^[0-9]{6,6}$/",
          ],
          "description" => ""
        ],
      ],
      'actions' => $actions
    ];

  }

  /**
   * call Api create otp.
   *
   * @param $msisdn
   *  msisdn
   * @param $body
   *  body send to api
   *
   * @return object
   */
  public function callCreateOtpApi($msisdn, $body) {
    $header_data = \Drupal::config('oneapp_mobile.otp.config')->get('headers');
    $headers = [
     'apikey' => $header_data['apikey'],
     'Content-Type'=> 'application/json',
    ];
   return $this->manager
      ->load('create_otp_endpoint')
      ->setParams(['id' => $msisdn])
      ->setHeaders($headers)
      ->setBody($body)
      ->setQuery([])
      ->sendRequest(FALSE);
  }

  /**
   * call Api validate OTP.
   *
   * @param $msisdn
   *  msisdn
   * @param $code
   *  code to validate
   *
   * @return object
   */
  public function callValidateOtp($msisdn, $code) {
    $params =[
      'id' => $msisdn,
      'code' => $code,
    ];
    $header_data = \Drupal::config('oneapp_mobile.otp.config')->get('headers');
    $headers = [
     'apikey' => $header_data['apikey'],
    ];
    return $this->manager
      ->load('validate_otp_endpoint')
      ->setParams($params)
      ->setHeaders($headers)
      ->setQuery([])
      ->sendRequest(FALSE);
  }

  /**
   * Create body the request to send.
   *
   * @param $configuration
   *  configuration
   *
   * @return array
   */
  public function getBody($params) {
    $token_data = \Drupal::config('oneapp_mobile.otp.config')->get('default');
    $body = [
      'text' => $this->getMessageToken($params),
      'token' => [
        'length' => $token_data["length"],
        'type' => $token_data["type"],
        'ttl' => $token_data["ttl"],
      ]
    ];
    return $body;
  }

  /**
   * Build message.
   *
   * @param $params
   *  parameter to replace in the message
   *
   * @return array
   */
  public function getMessageToken($params) {
    $message = $this->template["settings"]["sms"];
    if (isset($params["params"])) {
      foreach ($params["params"] as $key => $value) {
        $message = str_replace("@{$key}", $value, $message);
      }
    }
    return $message;
  }

  /**
   * Search otp configuration.
   *
   * @param $template_id
   *  template id
   *
   * @return array
   */
  public function getSettings($template_id) {
    $otp_configurations = \Drupal::config('oneapp_mobile.otp.config')->get('templates');
    foreach ($otp_configurations as $key => $value) {
      if (in_array(trim($template_id), $value)) {
        $this->setTemplate($value);
      break;
      }
    }
  }

  /**
   * create a Exception.
   *
   * @param $msg
   *  Error message.
   * @param $code
   *  code error
   * @param $exception
   *  exception
   *
   * @return BadRequestHttpException
   */
  public function sendException($msg, $code, $exception = NULL) {
    if (is_null($exception)) {
      $exception = new \Exception($msg, $code);
    }
    $error = new ErrorBase();
    $error->getError()->set('message', $msg);
    throw new BadRequestHttpException($error, $exception, $exception->getCode());
  }

  /**
   * Convert second to hours, minutes and seconds.
   *
   * @return string
   *   Mesagge with hours, minutes and seconds.
   */
  public function secondToHMS() {
    $token_data = \Drupal::config('oneapp_mobile.otp.config')->get('default');
    $time_second = $token_data["timeAttempt"];
    $hour = floor($time_second / 3600);
    $min = floor(($time_second - ($hour * 3600)) / 60);
    $sec = $time_second - ($hour * 3600) - ($min * 60);
    $text = "";
    $label_time_hour = explode(",", $token_data['time']["labelTimeHour"]);
    $label_time_hour = array_map('trim', $label_time_hour);
    if ($hour == 1) {
      $label_hour = (count($label_time_hour) > 1) ? $label_time_hour[0] : $token_data['time']["labelTimeHour"];
      $text .= "@hour {$label_hour}";
    }
    if ($hour > 1) {
      $label_hour = (count($label_time_hour) > 1) ? $label_time_hour[1] : $token_data['time']["labelTimeHour"];
      $text .= "@hour {$label_hour}";
    }

    $label_time_minute = explode(",", $token_data['time']["labelTimeMinute"]);
    $label_time_minute = array_map('trim', $label_time_minute);
    if ($min == 1) {
      $label_minute = (count($label_time_minute) > 1) ? $label_time_minute[0] : $token_data['time']["labelTimeMinute"];
      $text .= "@min {$label_minute}";
    }
    if ($min > 1) {
      $label_minute = (count($label_time_minute) > 1) ? $label_time_minute[1] : $token_data['time']["labelTimeMinute"];
      $text .= "@min {$label_minute}";
    }

    $label_second = explode(",", $token_data['time']["labelTimeSecond"]);
    $label_second = array_map('trim', $label_second);
    if ($sec == 1) {
      $label_second = (count($label_second) > 1) ? $label_second[0] : $token_data['time']["labelTimeSecond"];
      $text .= "@sec {$label_second}";
    }
    if ($sec > 1) {
      $label_second = (count($label_second) > 1) ? $label_second[1] : $token_data['time']["labelTimeSecond"];
      $text .= "@sec {$label_second}";
    }
    return t($text, ['@hour' => $hour, '@min' => $min, '@sec' => $sec]);
  }

}
