<?php

namespace Drupal\mysite_workspaces_preview_protector\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Overrides Workspaces-related routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Provides custom access check for WSE preview links.
    if ($route = $collection->get('wse_preview.workspace_preview')) {
      $route->setRequirement('_mysite_workspaces_preview_protector_access_check', TRUE);
    }
  }

}
