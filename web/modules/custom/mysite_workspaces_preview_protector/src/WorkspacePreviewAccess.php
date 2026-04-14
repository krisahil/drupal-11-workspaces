<?php

namespace Drupal\mysite_workspaces_preview_protector;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Alternate access checker for WSE preview links.
 */
class WorkspacePreviewAccess implements AccessInterface {

  /**
   * Constructor.
   */
  public function __construct(
    protected CdnStatus $cdnStatus,
  ) {}

  /**
   * Prevents access if the user is on CDN/public domain.
   *
   * We cannot allow preview access on the CDN/public domain, because we risk
   * poisoning the CDN's page cache with preview content.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The Drupal user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Prevents access if the current request is on the CDN/public domain, or
   *   if the user does not have access to access workspaces.
   *
   * @see \Drupal\mysite_workspaces_preview_protector\Negotiator\NoPublicDomainWithCookieWorkspaceNegotiator::applies
   */
  public function access(AccountInterface $account): AccessResultInterface {
    $cacheable_dependency = $this->cdnStatus->getCacheableDependency();

    if ($this->cdnStatus->currentRequestIsOnCdnHostname()) {
      return AccessResult::forbidden()->addCacheableDependency($cacheable_dependency);
    }

    return AccessResult::allowedIfHasPermission($account, 'access workspace previews')->addCacheableDependency($cacheable_dependency);
  }

}
