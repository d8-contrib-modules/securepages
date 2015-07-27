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

  public static $modules = array('securepages', 'comment', 'locale');

  /**
   * Test manual password reset.
   */
  function testSettingsForm() {
    // Undo the setUp() function.
    //variable_del('securepages_enable');
    $config = \Drupal::config('securepages.securepagesconfig_config');
    $config->clear('securepages_enable')->save();

    // Enable securepages.
    $this->web_user = $this->drupalCreateUser(array('administer site configuration', 'access administration pages'));
    $this->loginHTTPS($this->web_user);
    $edit = array('securepages_enable' => 1);
    $this->drupalPost('admin/config/system/securepages', $edit, t('Save configuration'), array('https' => TRUE));
    $this->assertRaw(t('The configuration options have been saved.'));

    // Clean up
    $this->drupalLogout();
  }

}