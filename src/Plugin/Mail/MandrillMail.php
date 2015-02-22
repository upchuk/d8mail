<?php

namespace Drupal\d8mail\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mandrill;
use Mandrill_Error;

/**
 * Defines the Mandrill mail backend.
 *
 * @Mail(
 *   id = "mandrill_mail",
 *   label = @Translation("Mandrill mailer"),
 *   description = @Translation("Sends an email using Mandrill.")
 * )
 */
class MandrillMail implements MailInterface, ContainerFactoryPluginInterface {

  /**
   * @var Mandrill
   */
  private $mandrill;

  /**
   * @param Mandrill $mandrill
   */
  public function __construct(Mandrill $mandrill) {
    $this->mandrill = $mandrill;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('d8mail.mandrill')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);
    // Convert any HTML to plain-text.
    $message['body'] = MailFormatHelper::htmlToText($message['body']);
    // Wrap the mail body for sending.
    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {

    try {
      $vars = [
        'html' => $message['body'],
        'subject' => $message['subject'],
        'from_email' => $message['from'],
        'to' => array(
          array('email' => $message['to'])
        ),
      ];

      $result = $this->mandrill->messages->send($vars);
      if ($result[0]['status'] !== 'sent') {
        return false;
      }

      return $result;
    }
    catch (Mandrill_Error $e) {
      return false;
    }
  }
}