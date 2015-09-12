<?php

/**
 * @file
 * Contains Drupal\securepages\Form\SecurePagesConfigForm.
 */

namespace Drupal\securepages\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\securepages\SecurePagesService;

/**
 * Class SecurePagesConfigForm.
 *
 * @package Drupal\securepages\Form
 */
class SecurePagesConfigForm extends ConfigFormBase {

  /**
   * Drupal\securepages\SecurePagesService definition.
   *
   * @var Drupal\securepages\SecurePagesService
   */
  protected $securepages_securepagesservice;
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SecurePagesService $securepages_securepagesservice
  ) {
    parent::__construct($config_factory);
    $this->securepages_securepagesservice = $securepages_securepagesservice;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('securepages.securepagesservice')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'securepages.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'secure_pages_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('securepages.settings');
    $form['securepages_enable'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Enable Secure Pages'),
      '#description' => $this->t('To start using secure pages this setting must be enabled. This setting will only be able to changed when the web server has been configured for SSL.<br />If this test has failed then these options will not be available. Check your web server settings.'),
      '#options' => array(1 => $this->t('Yes'), 0 => $this->t('No')),
      '#default_value' => $config->get('securepages_enable'),
    );
    $form['securepages_basepath'] = array(
      '#type' => 'url',
      '#title' => $this->t('Non-secure Base URL'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('securepages_basepath'),
    );
    $form['securepages_basepath_ssl'] = array(
      '#type' => 'url',
      '#title' => $this->t('Secure Base URL'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('securepages_basepath_ssl'),
    );
    $form['securepages_entire_site'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Force SSL to the entire site'),
      '#description' => $this->t('Select if SSL should be forced for the entire site or granular to specific contexts.'),
      '#options' => array(1 => $this->t('Yes'), 0 => $this->t('No')),
      '#default_value' => $config->get('securepages_entire_site'),
    );

    $form['securepages_granular_settings'] =  array(
      '#type' => 'container',
      '#states' => array(
        // Hide the granular settings if user selected to force SSL for entire site.
        'invisible' => array(
          'input[name="securepages_entire_site"]' => array('value' => 1),
        ),
      ),
    );
    $form['securepages_granular_settings']['securepages_pages'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Pages'),
      '#description' => $this->t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are '<em>blog</em>' for the main blog page and '<em>blog/*</em>' for every personal blog. '<em>&lt;front&gt;</em>' is the front page."),
      '#default_value' => $config->get('securepages_pages'),
    );
    $form['securepages_granular_settings']['securepages_secure'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Pages which will be be secure'),
      '#description' => $this->t(''),
      '#options' => array($this->t('Make secure every page except the listed pages above') ,  $this->t('Make secure only the listed pages above')),
      '#default_value' => $config->get('securepages_secure'),
    );
    $form['securepages_granular_settings']['securepages_switch'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Switch back to http pages when there are no matches to list of pages above.'),
      '#description' => $this->t(''),
      '#options' => array(1 => $this->t('Yes'), 0 => $this->t('No')),
      '#default_value' => $config->get('securepages_switch'),
    );
    $form['securepages_granular_settings']['securepages_ignore'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Ignore pages'),
      '#description' => $this->t("The pages listed here will be ignored and be either returned in http or https. Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are '<em>blog</em>' for the blog page and '<em>blog/*</em>' for every personal blog. '<em>&lt;front&gt;</em>' is the front page."),
      '#default_value' => $config->get('securepages_ignore'),
    );
    $role_options = array();
    // Call with FALSE to provide all the roles, not just
    // what the user has access to
    $roles = user_roles(FALSE);
    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }
    $form['securepages_granular_settings']['securepages_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => 'User roles',
      '#description' => t('Users with the chosen role(s) are always redirected to https, regardless of path rules.'),
      '#options' => $role_options, //array_map('\Drupal\Core\Utility\String::checkPlain', $role_options),
      '#default_value' => $config->get('securepages_roles'),
    );
    $form['securepages_granular_settings']['securepages_forms'] = array(
      '#type' => 'textarea',
      '#title' => t('Secure forms'),
      '#default_value' => $config->get('securepages_forms'),
      '#cols' => 40,
      '#rows' => 5,
      '#description' => t('List of form ids which will have the https flag set to TRUE.'),
    );
    $form['securepages_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Debugging'),
      '#default_value' => $config->get('securepages_debug'),
      '#description' => t('Turn on debugging to allow easier testing of settings'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('securepages.settings')
      ->set('securepages_enable', $form_state->getValue('securepages_enable'))
      ->set('securepages_switch', $form_state->getValue('securepages_switch'))
      ->set('securepages_entire_site', $form_state->getValue('securepages_entire_site'))
      ->set('securepages_basepath', $form_state->getValue('securepages_basepath'))
      ->set('securepages_basepath_ssl', $form_state->getValue('securepages_basepath_ssl'))
      ->set('securepages_secure', $form_state->getValue('securepages_secure'))
      ->set('securepages_pages', $form_state->getValue('securepages_pages'))
      ->set('securepages_ignore', $form_state->getValue('securepages_ignore'))
      ->set('securepages_roles', $form_state->getValue('securepages_roles'))
      ->set('securepages_forms', $form_state->getValue('securepages_forms'))
      ->set('securepages_debug', $form_state->getValue('securepages_debug'))
      ->save();
  }

}
