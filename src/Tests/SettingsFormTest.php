<?php

/**
 * @file
 * Definition of Drupal\securepages\Tests\SettingsFormTest.
 */

namespace Drupal\securepages\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests settings form.
 *
 * @group securepages
 */
class SettingsFormTest extends WebTestBase {

  public static $modules = array(
    'password_policy',
    'password_policy_length',
    'node'
  );

  /**
   * Test failing password and verify it fails.
   */
  function testOwnUserPasswords() {
    // Create password policy length.
    $pid = db_insert('password_policy_length_policies')
      ->fields(array('character_length' => '5'))
      ->execute();

    // Create user with permission to create policy.
    $user1 = $this->drupalCreateUser(array('enforce password_policy_length_constraint.' . $pid . ' constraint'));

    $this->drupalLogin($user1);

    // Try failing password on form submit.
    $edit = array();
    $edit['current_pass'] = $user1->pass_raw;
    $edit['pass'] = '111';
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));

    $this->assertText('The password does not satisfy the password policies');

    // Try failing password on AJAX.

    // Try passing password on form submit.
    $edit = array();
    $edit['current_pass'] = $user1->pass_raw;
    $edit['pass'] = '111111';
    $this->drupalPostForm("user/" . $user1->id() . "/edit", $edit, t('Save'));

    $this->assertNoText('The password does not satisfy the password policies');

    // Try passing password on AJAX.
  }
}