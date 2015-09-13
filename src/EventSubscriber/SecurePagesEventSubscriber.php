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

    $config = \Drupal::config('securepages.settings');
    $securepages_enable = $config->get('securepages_enable');

    if ($securepages_enable && basename($_SERVER['PHP_SELF']) == 'index.php' && php_sapi_name() != 'cli') {

      $securepagesservice = \Drupal::service('securepages.securepagesservice');
      $redirect = $securepagesservice->securePagesRedirect();
      $request = $event->getRequest();
      //Replaces current URL with the one defined in the module's settings page.
      $uri = $securepagesservice->securePagesGenerateUrl($request->getSchemeAndHttpHost(), $request->getUri(), $redirect);

        if(is_null($redirect)) {

        }elseif($redirect == TRUE) {
          //Unset destination parameter so this won't redirect in this request
          $request->query->remove('destination');

          $url = Url::fromUri($uri, array('absolute' => TRUE, 'https' => TRUE))->toString();
          $event->setResponse(new TrustedRedirectResponse($url, 302));
        }elseif($redirect == FALSE){

          //If parameter "destination" is set, don't force redirect to HTTP.
          //This prevents a loop when the user is logged in without SSL and then needs to login with SSL.
          if(!empty($request->query->get('destination'))){
            return;
          }
          $url = Url::fromUri($uri, array('absolute' => TRUE, 'https' => FALSE))->toString();
          $event->setResponse(new TrustedRedirectResponse($url, 302));
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