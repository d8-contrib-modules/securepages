<?php

/**
 * @file
 * Contains \Drupal\blindd8\BlindD8ingService.
 */

namespace Drupal\securepages;

use Drupal\Component\Utility\Unicode;
use \Drupal\Component\Utility\UrlHelper;
//use Drupal\Core\Session\AccountInterface;

class SecurePagesService {
  protected $config;
  protected $is_https;
  protected $path;
  protected $securepages_switch;
  protected $securepages_basepath;
  protected $securepages_basepath_ssl;
  protected $request_method;
  protected $base_url;

  public function __construct() {
    $this->config = \Drupal::config('securepages.securepagesconfig_config');
    $this->is_https = \Drupal::request()->isSecure();
    $this->request_method = \Drupal::request()->getMethod();
    $this->path = \Drupal::service('path.current')->getPath() ? \Drupal::service('path.current')->getPath() : '';
    $this->securepages_switch = $this->config->get('securepages_switch');
    $this->securepages_basepath = $this->config->get('securepages_basepath');
    $this->securepages_basepath_ssl = $this->config->get('securepages_basepath_ssl');

  }

  public function securePagesRedirect() {
    global $base_url;


    $current_path = \Drupal::service('path.current')->getPath();
    $account = \Drupal::currentUser();

    $page_match = $this->securePagesMatch($current_path);
    $role_match = $this->securePagesRoles($account);



    $return_value = NULL;

    if($this->request_method == 'POST') {
        $this->securepages_log('POST request skipped in service', $this->path);
    }elseif ($role_match && !$this->is_https) {
      $this->securepages_log('Switch User to secure', $this->path);
      return TRUE;

    }
    elseif ($page_match && !$this->is_https) {
      $this->securepages_log('Switch Path to secure', $this->path);
      return TRUE;

    }
    elseif ($page_match === 0 && $this->is_https && $this->securepages_switch && !$role_match) {
      $this->securepages_log('Switch Path to insecure (Path: "@path")', $this->path);
      return FALSE;

    }
    // @todo: figure this out
    // Correct the base_url so that everything comes from HTTPS.
    if ($this->is_https) {
      $this->base_url = $this->securepages_baseurl();
    }


  }
  public function securePagesMatch($path) {

    $is_secure = \Drupal::request()->isSecure();

    $config = \Drupal::config('securepages.securepagesconfig_config');
    $securepages_secure = $config->get('securepages_secure');
    $securepages_pages = $config->get('securepages_pages');
    $securepages_ignore = $config->get('securepages_ignore');

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

    if ($securepages_ignore) {

      $result = \Drupal::service('path.matcher')->matchPath($path, $securepages_ignore);

      if (!$result) {

         //@TODO use $langcode
        $path_alias =  \Drupal::service('path.alias_manager')->getAliasByPath('/'.$path, 'en') ;
         // @TODO: https://www.drupal.org/node/2531732
        if(Unicode::substr($path_alias, 0, 1) == "/") {
          $path_alias = Unicode::substr($path_alias, 1);
        }
        $result = \Drupal::service('path.matcher')->matchPath($path_alias, $securepages_ignore);


       }
       if ($result) {
         //$this->securepages_log('Ignored path (Path: "@path", Line: @line, Pattern: "@pattern")', $path_alias, $securepages_ignore);
         return $is_secure ? 1 : 0;
       }
    }

    if ($securepages_pages) {
      $result = \Drupal::service('path.matcher')->matchPath($path, $securepages_pages);
      if (!$result) {
        $path_alias =  \Drupal::service('path.alias_manager')->getAliasByPath('/'.$path, 'en') ;
        if(Unicode::substr($path_alias, 0, 1) == "/") {
          $path_alias = Unicode::substr($path_alias, 1);
        }
        $result = \Drupal::service('path.matcher')->matchPath($path_alias, $securepages_pages);
      }

      if (!($securepages_secure xor $result)) {
        //$this->securepages_log('Secure path (Path: "@path", Line: @line, Pattern: "@pattern")', $path_alias, $securepages_pages);
      }

      return !($securepages_secure xor $result) ? 1 : 0;
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

    if(!$account){
      $account = \Drupal::currentUser();
    }
    $account_roles = $account->getRoles();


    $config = \Drupal::config('securepages.securepagesconfig_config');
    $securepages_roles = $config->get('securepages_roles');
    // All rids are in the settings, so first we need to filter out the ones
    // that aren't enabled. Otherwise this would match positive against all
    // roles a user has set.
    $keyed_account_roles = [];
    foreach($account_roles as $role) {
      $keyed_account_roles[Unicode::strtolower($role)] = $role;
    }

    $securepages_roles = array_filter($securepages_roles);
    $matches = array_intersect_key($keyed_account_roles, $securepages_roles);
    return count($matches);
  }


  /**
   * Returns the secure base path.
   */
  public function securepages_baseurl($secure = TRUE) {
    global $base_url;

    if ($secure) {
      $url = $this->securepages_basepath_ssl;
    }
    else {
      $url = $this->securepages_basepath;
    }

    if (!empty($url)) {
      return $url;
    }
    // No url has been set, so convert the base_url from 1 to the other
    return preg_replace('/http[s]?:\/\//i', ($secure ? 'https://' : 'http://'), $base_url, 1);
  }



  /**
   * Checks the URL to make sure it is a URL that can be altered.
   *
   * @param $url
   *   URL to check.
   *
   * @return boolean
   */
  public function securepages_can_alter_url($url) {
    global $base_path, $base_url;
    // @todo: is there a better way to do this in symfony?
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

  public function securepages_log($text, $path, $pattern = NULL) {
    $config = \Drupal::config('securepages.securepagesconfig_config');
    $securepages_debug = $config->get('securepages_debug');

    if ($securepages_debug) {
      $options = array(
        '@path' => $path,
        '@line' => t('NF'),
        '@pattern' => '',
      );
      if ($pattern) {
        // @todo: check to make sure the path doesn't have a preceding slash
        if(Unicode::substr($path, 0, 1) == "/") {
          //$path = Unicode::substr($path, 1);
        }
        if (!\Drupal::service('path.matcher')->matchPath($path, $pattern)) {
          $path = \Drupal::service('path.alias_manager')->getAliasByPath($path, 'en');
        }

        $pattern_parts = explode("\n", $pattern);
        foreach ($pattern_parts as $line => $part) {

          if (\Drupal::service('path.matcher')->matchPath($path, $part)) {
            $options['@line'] = $line+1;
            $options['@pattern'] = $part;
            break;
          }
        }
      }
    }
  }


}