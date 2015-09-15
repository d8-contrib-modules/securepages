<?php

/**
 * @file
 * Contains \Drupal\securepages\SecurePagesService.
 */

namespace Drupal\securepages;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;

class SecurePagesService {

  const SECURE_PAGES_TEST_HTTPS_CONNECTION_ROUTE = 'securepages.secure_pages_test_https_connection';

  protected $config;
  protected $is_https;
  protected $path;
  protected $securepages_switch;
  protected $securepages_entire_site;
  protected $securepages_basepath;
  protected $securepages_basepath_ssl;
  protected $request_method;
  protected $securepages_secure;
  protected $securepages_pages;
  protected $securepages_ignore;
  protected $securepages_roles;
  protected $securepages_debug;
  protected $langcode;

  public function __construct() {
    $this->initializeValuesFromConfig();
    $this->is_https = \Drupal::request()->isSecure();
    $this->request_method = \Drupal::request()->getMethod();
    $this->path = \Drupal::service('path.current')
      ->getPath() ? \Drupal::service('path.current')->getPath() : '';
    $this->langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  public function initializeValuesFromConfig(){
    $this->config = \Drupal::config('securepages.settings');
    $this->securepages_switch = $this->config->get('securepages_switch');
    $this->securepages_entire_site = $this->config->get('securepages_entire_site');
    $this->securepages_basepath = $this->config->get('securepages_basepath');
    $this->securepages_basepath_ssl = $this->config->get('securepages_basepath_ssl');
    $this->securepages_secure = $this->config->get('securepages_secure');
    $this->securepages_pages = $this->config->get('securepages_pages');
    $this->securepages_ignore = $this->config->get('securepages_ignore');
    $this->securepages_roles = $this->config->get('securepages_roles');
    $this->securepages_debug = $this->config->get('securepages_debug');
  }

  public function securePagesRedirect() {

    $current_path = \Drupal::service('path.current')->getPath();

    $current_route_name = \Drupal::routeMatch()->getRouteName();

    //If user selected to force SSL for the entire site or
    //current page is the HTTPS test page,
    //there's no need to check pages and roles.
    if (!$this->securepages_entire_site && $current_route_name !== $this::SECURE_PAGES_TEST_HTTPS_CONNECTION_ROUTE) {
      $account = \Drupal::currentUser();

      $page_match = $this->securePagesMatch($current_path);
      $role_match = $this->securePagesRoles($account);
    }
    else {
      $page_match = TRUE;
      $role_match = TRUE;
    }

    if ($this->request_method == 'POST') {
      $this->securePagesLog('POST request skipped in service', $this->path);

    }
    elseif ($this->is_https) {
      //Conditions for when current page is using HTTPS.

      if ($page_match === 0 && $this->securepages_switch && !$role_match) {
        $this->securePagesLog('Switch Path to insecure (Path: "@path")', $this->path);
        return FALSE;
      }
    }
    else {
      //Conditions for when current page is NOT using HTTPS

      if ($this->securepages_entire_site) {
        $this->securePagesLog('Switch to secure(force SSL for entire site)', $this->path);
        return TRUE;
      }
      elseif ($role_match) {
        $this->securePagesLog('Switch User to secure', $this->path);
        return TRUE;
      }
      elseif ($page_match) {
        $this->securePagesLog('Switch Path to secure', $this->path);
        return TRUE;
      }

    }

    return NULL;

  }

  /**
   * Checks the page past and see if it should be secure or insecure.
   *
   * @param $path
   *   The page of the page to check.
   *
   * @return
   *   - 0: Page should be insecure.
   *   - 1: Page should be secure.
   *   - NULL: Do not change page.
   */
  public function securePagesMatch($path) {

    $path = Unicode::strtolower(trim($path, '/'));
    $path_alias = NULL;
    // Checks to see if the page matches the current settings.
    /**
     * @TODO: Look into this logic
     * This logic doesn't take into account some edge cases - UI is not clear
     * E.G. A user selects 'Make secure only the listed pages' and
     * enters 'admin/*' in the ignore pages, but enters 'admin/content' in
     * the allowed pages - the following logic will disable all admin paths and not allow
     * any exceptions.
     */

    if ($this->securepages_ignore) {

      $result = \Drupal::service('path.matcher')
        ->matchPath($path, $this->securepages_ignore);

      if (!$result) {

        $path_alias = \Drupal::service('path.alias_manager')
          ->getAliasByPath('/' . $path, $this->langcode);
        // @TODO: https://www.drupal.org/node/2531732
        if (Unicode::substr($path_alias, 0, 1) == "/") {
          $path_alias = Unicode::substr($path_alias, 1);
        }
        $result = \Drupal::service('path.matcher')
          ->matchPath($path_alias, $this->securepages_ignore);


      }
      if ($result) {
        //$this->securePagesLog('Ignored path (Path: "@path", Line: @line, Pattern: "@pattern")', $path_alias, $this->securepages_ignore);
        return $this->is_https ? 1 : 0;
      }
    }

    if ($this->securepages_pages) {
      $result = \Drupal::service('path.matcher')
        ->matchPath($path, $this->securepages_pages);
      if (!$result) {
        $path_alias = \Drupal::service('path.alias_manager')
          ->getAliasByPath('/' . $path, $this->langcode);
        if (Unicode::substr($path_alias, 0, 1) == "/") {
          $path_alias = Unicode::substr($path_alias, 1);
        }
        $result = \Drupal::service('path.matcher')
          ->matchPath($path_alias, $this->securepages_pages);
      }

      if (!($this->securepages_secure xor $result)) {
        //$this->securePagesLog('Secure path (Path: "@path", Line: @line, Pattern: "@pattern")', $path_alias, $this->securepages_pages);
      }

      return !($this->securepages_secure xor $result) ? 1 : 0;
    }
    else {
      return;
    }


  }

  /**
   * Checks if the user is in a role that is always forced onto HTTPS.
   *
   *   A valid user object.
   *
   *   The number of roles set on the user that require HTTPS enforcing.
   */
  public function securePagesRoles($account) {

    if (!$account) {
      $account = \Drupal::currentUser();
    }
    $account_roles = $account->getRoles();

    // All rids are in the settings, so first we need to filter out the ones
    // that aren't enabled. Otherwise this would match positive against all
    // roles a user has set.
    $keyed_account_roles = [];
    foreach ($account_roles as $role) {
      $keyed_account_roles[Unicode::strtolower($role)] = $role;
    }

    $this->securepages_roles = array_filter($this->securepages_roles);
    $matches = array_intersect_key($keyed_account_roles, $this->securepages_roles);
    return count($matches);
  }


  /**
   * Returns the secure base path.
   */
  public function securePagesBaseUrl($secure = TRUE) {
    global $base_url;

    if ($secure) {
      $url = $this->securepages_basepath_ssl;
    }
    else {
      $url = $this->securepages_basepath;
    }

    if (empty($url)) {
      $url = $base_url;
    }
    // No url has been set, so convert the base_url from 1 to the other
    return preg_replace('/http[s]?:\/\//i', ($secure ? 'https://' : 'http://'), $url, 1);
  }

  /**
   * Checks the URL to make sure it is a URL that can be altered.
   *
   * @param $url
   *   URL to check.
   *
   * @return boolean
   */
  public function securePagesCanAlterUrl($url) {
    global $base_path, $base_url;

    $url = @parse_url($url);

    // If there is no scheme then it is a relative url and can be altered
    if (!isset($url['scheme']) && $base_path == '/') {
      return TRUE;
    }

    // If the host names are not the same then don't allow altering of the path.
    $http_host = \Drupal::request()->server->get('HTTP_HOST');
    if (isset($url['host']) && strtolower($url['host']) != strtolower($http_host)) {
      return FALSE;
    }

    if (strlen($base_path) > 1 && substr($base_url, -1) != substr($url['path'], 1, strlen($base_path))) {
      return FALSE;
    }

    return TRUE;
  }

  public function securePagesLog($text, $path, $pattern = NULL) {

    if ($this->securepages_debug) {
      $options = array(
        '@path' => $path,
        '@line' => t('NF'),
        '@pattern' => '',
      );
      if ($pattern) {
        // @todo: check to make sure the path doesn't have a preceding slash
        if (Unicode::substr($path, 0, 1) == "/") {
          //$path = Unicode::substr($path, 1);
        }
        if (!\Drupal::service('path.matcher')->matchPath($path, $pattern)) {
          $path = \Drupal::service('path.alias_manager')
            ->getAliasByPath($path, 'en');
        }

        $pattern_parts = explode("\n", $pattern);
        foreach ($pattern_parts as $line => $part) {

          if (\Drupal::service('path.matcher')->matchPath($path, $part)) {
            $options['@line'] = $line + 1;
            $options['@pattern'] = $part;
            break;
          }
        }
      }
    }
  }

  /**
   * Generates the proper URL for the current page based on the settings for this module.
   * @param $schemeAndHost The scheme and host to be replaced.
   * @param $uri The uri to be changed.
   * @return string The URL formatted with the module configurations.
   */
  public function securePagesGenerateUrl($schemeAndHost, $uri, $secure){
    return str_replace($schemeAndHost, $this->securePagesBaseUrl($secure), $uri);
  }

  /**
   * @return bool TRUE if test HTPPS connection returns 200, FALSE otherwise.
   */
  public function securePagesTestHttpsConnection() {

    global $base_url;

    //Makes a request for the test HTTPS Connection route.
    $client = \Drupal::httpClient();
    try {
      //Ignores SSL certificate validation.
      //@TODO Find a better way to handle that. Full discussion at: https://github.com/d8-contrib-modules/securepages/issues/6
      $options = array('verify' => FALSE);

      $testHttpsConnectionUrl = Url::fromRoute($this::SECURE_PAGES_TEST_HTTPS_CONNECTION_ROUTE, array(), array('absolute' => TRUE))->toString();
      $request = $client->request('GET', $this->securePagesGenerateUrl($base_url, $testHttpsConnectionUrl, TRUE), $options);
    }
    catch (RequestException $e) {
      return FALSE;
    }
    return method_exists($request, 'getStatusCode') && $request->getStatusCode() == 200;
  }

}