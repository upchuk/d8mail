<?php

namespace Drupal\d8mail\Tests;

use Drupal\d8mail\Plugin\Mail\MandrillMail;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MandrillMail class.
 *
 * @coversDefaultClass \Drupal\d8mail\Plugin\Mail\MandrillMail
 * @group d8mail
 */
class MandrillMailPluginTest extends UnitTestCase {

  public function testMandrillMailPlugin() {

    // Stub out the Mandrill API
    $mandrill_stub = $this->getMockBuilder('Mandrill')
      ->disableOriginalConstructor()
      ->getMock();
    $messages_stub = $this->getMockBuilder('Messages')
      ->disableOriginalConstructor()
      ->setMethods(array('send'))
      ->getMock();
    $messages_stub
      ->expects($this->at(0))
      ->method('send')
      // First time it will send the email
      ->willReturn(array(array('status' => 'sent')));
    $messages_stub
      ->expects($this->at(1))
      ->method('send')
      // Second time it won't send the email.
      ->willReturn(false);
    $mandrill_stub->messages = $messages_stub;

    $mailer = new MandrillMail($mandrill_stub);
    $message = array(
      'to' => 'my_email',
      'from' => 'your_email',
      'body' => 'body',
      'subject' => 'subject',
    );
    // Mock a successful email sending with the plugin
    $result = $mailer->mail($message);
    $this->assertTrue(isset($result[0]['status']));

    // Mock an unsuccessful email sending with the plugin
    $result = $mailer->mail($message);
    $this->assertFalse($result);
  }
}