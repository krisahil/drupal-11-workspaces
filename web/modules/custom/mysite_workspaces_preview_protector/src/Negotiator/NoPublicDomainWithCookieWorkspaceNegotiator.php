<?php

namespace Drupal\mysite_workspaces_preview_protector\Negotiator;

use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\mysite_workspaces_preview_protector\CdnStatus;
use Drupal\wse_preview\Negotiator\CookieWorkspaceNegotiator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a cookie workspace negotiator that is not allowed on the CDN domain.
 */
class NoPublicDomainWithCookieWorkspaceNegotiator extends CookieWorkspaceNegotiator {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly RequestStack $requestStack,
    protected readonly KeyValueExpirableFactoryInterface $keyValueExpirableFactory,
    protected readonly CdnStatus $cdnStatus,
  ) {}

  /**
   * {@inheritdoc}
   *
   * See the docs on the related method.
   *
   * @see \Drupal\mysite_workspaces_preview_protector\WorkspacePreviewAccess::access
   */
  public function applies(Request $request) {
    if ($this->cdnStatus->currentRequestIsOnCdnHostname()) {
      return FALSE;
    }

    return parent::applies($request);
  }

}
