<?php

/**
 * @file
 * Contains Drupal\securepages\EventSubscriber\SecurePagesEventSubscriber.
 */

namespace Drupal\securepages\EventSubscriber;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class SecurePagesEventSubscriber implements EventSubscriberInterface {

  public function checkForHttp(GetResponseEvent $event) {

    $response = $event->getResponse();

    $current_path = \Drupal::service('path.current')->getPath();

    // Special path for verifying SSL status.
    if ($current_path == 'admin/config/system/securepages/test') {
      if (\Drupal::request()->isSecure()) {
        // @TODO: Update
        //header('HTTP/1.1 200 OK');
        $response->setStatusCode('200');
        $response->send();
      }
      else {
        // @TODO: Update
        //header('HTTP/1.1 404 Not Found');
        $response->setStatusCode('404');
        $response->send();
      }
    }

    $config = \Drupal::config('securepages.settings');
    $securepages_enable = $config->get('securepages_enable');

    if ($securepages_enable && basename($_SERVER['PHP_SELF']) == 'index.php' && php_sapi_name() != 'cli') {

      $securepagesservice = \Drupal::service('securepages.securepagesservice');
      $redirect = $securepagesservice->securePagesRedirect();
<<<<<<< HEAD
      $request = $event->getRequest();
=======
      $securepages_baseurl = $securepagesservice->securepages_baseurl($redirect);
      $request = $event->getRequest();
      //Replaces current URL with the one set by the user.
      $uri = str_replace($request->getSchemeAndHttpHost(), $securepages_baseurl, $request->getUri());
>>>>>>> 0e4abbec11f4cb5b2b9adffbb5bbe15169a286d5

        if(is_null($redirect)) {

        }elseif($redirect == TRUE) {
<<<<<<< HEAD
          //Unset destination parameter so this won't redirect in this request
          $request->query->remove('destination');
          $url = Url::fromUri($request->getUri(), array('absolute' => TRUE, 'https' => TRUE))->toString();
          $event->setResponse(new RedirectResponse($url, 302));
        }elseif($redirect == FALSE){
          $url = Url::fromUri($request->getUri(), array('absolute' => TRUE, 'https' => FALSE))->toString();
          $event->setResponse(new RedirectResponse($url, 302));
=======
          $url = Url::fromUri($uri, array('absolute' => TRUE, 'https' => TRUE))->toString();
          $event->setResponse(new TrustedRedirectResponse($url, 302));
        }elseif($redirect == FALSE){
          if(!empty($request->query->get('destination'))){
            return;
          }
          $url = Url::fromUri($uri, array('absolute' => TRUE, 'https' => FALSE))->toString();
          $event->setResponse(new TrustedRedirectResponse($url, 302));
>>>>>>> 0e4abbec11f4cb5b2b9adffbb5bbe15169a286d5
        }

        // Store the response in the page cache.
        // @TODO: port this code
/*
        if (variable_get('cache', 0) && ($cache = drupal_page_set_cache())) {
          drupal_serve_page_from_cache($cache);
        }
        else {
          ob_flush();
        }
*/

      //}

    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents(){
    $events[KernelEvents::REQUEST][] = array('checkForHttp');
    return $events;
  }

}