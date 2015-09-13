<?php /**
 * @file
 * Contains \Drupal\securepages\Controller\SecurePagesController.
 */

namespace Drupal\securepages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the Secure Pages module.
 */
class SecurePagesController extends ControllerBase {

  /**
   * Returns success only if this page is being accessed by HTTPS.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   A \Symfony\Component\HttpFoundation\Response object or render array.
   */
  public function testHttpsConnection(Request $request) {
    if (\Drupal::request()->isSecure()) {
      return new Response($this->t('HTTPS works!'), 200);
    }
    return new Response($this->t('HTTPS doesn\'t work'), 404);
  }
}
