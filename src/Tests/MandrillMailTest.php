<?php

/**
 * @file
 * Contains \Drupal\d8mail\Tests\MandrillMailTestTest.
 */

namespace Drupal\d8mail\Tests;

use Drupal\node\Entity\NodeType;
use Drupal\simpletest\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests if the Mandrill mailer works properly.
 * @group d8mail
 */
class MandrillMailTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'node', 'user', 'text', 'filter', 'entity_reference');

  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('system'));
    $this->installSchema('user', 'users_data');
    \Drupal::service('module_installer')->install(array('d8mail'));
  }

  /**
   * Checks whether the Mandrill mail plugin gets set in the config.
   */
  function testMandrillConfig() {
    $this->doMandrillConfig();
    $this->doMandrillNoConfig();
  }

  /**
   * Checks whether emails get sent when a user creates a new article node.
   */
  function testMandrillEmail() {
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    \Drupal::service('module_installer')->install(array('dblog', 'field'));

    // Use the state system collector mail backend.
    $config = \Drupal::configFactory()->getEditable('system.mail');
    $mail_plugins = array('default' => 'test_mail_collector');
    $config->set('interface', $mail_plugins)->save();

    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', array());

    // Create a user account and set it as the current user.
    $user = entity_create('user', array('uid' => 1, 'mail' => 'user@example.com'));
    $user->save();
    \Drupal::currentUser()->setAccount($user);

    // Set the site email address.
    \Drupal::configFactory()->getEditable('system.site')->set('mail', 'site@example.com')->save();

    // Create the article content type and add the body field to it.
    $type = array(
      'type' => 'article',
      'name' => 'Article',
    );
    $type = entity_create('node_type', $type);
    $type->save();
    $article = NodeType::load('article');
    node_add_body_field($article);

    // Create a random article node.
    $title = $this->randomMachineName();
    $values = array(
      'uid' => $user->id(),
      'title' => $title,
      'body' => [['value' => 'test_body']],
      'type' => 'article'
    );
    $node = entity_create('node', $values);
    $node->save();

    // Check the latest captured emails.
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);

    $this->assertTrue(!empty($sent_message));
    $this->assertEqual($sent_message['id'], 'd8mail_node_insert', 'Correct mail id.');
    $this->assertEqual($sent_message['to'], 'user@example.com', 'Correct mail to.');
    $this->assertEqual($sent_message['from'], 'site@example.com', 'Correct mail from.');
    $this->assertEqual($sent_message['subject'], sprintf("Node created: %s", $title), 'Correct mail subject.');
    $this->assertEqual($sent_message['body'], 'test_body' . PHP_EOL, 'Correct mail body.');

  }

  /**
   * Checks whether the Mandrill mail plugin gets set in the config.
   */
  function doMandrillConfig() {
    $config = \Drupal::configFactory()->getEditable('system.mail')->get('interface');
    $this->assertTrue(in_array('d8mail', array_keys($config)), 'The Mandrill mailer is present in the configuration.');
  }

  /**
   * Checks whether the Mandrill mail plugin gets removed from the config.
   */
  function doMandrillNoConfig() {
    \Drupal::service('module_installer')->uninstall(array('d8mail'));
    $config = \Drupal::configFactory()->getEditable('system.mail')->get('interface');
    $this->assertTrue(!in_array('d8mail', array_keys($config)), 'The Mandrill mailer is not present in the configuration.');
  }
}


