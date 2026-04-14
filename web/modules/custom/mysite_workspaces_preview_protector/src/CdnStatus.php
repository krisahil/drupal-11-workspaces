<?php

namespace Drupal\mysite_workspaces_preview_protector;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service class to determine whether a request came from the CDN.
 */
class CdnStatus {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly RequestStack $requestStack,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Determines whether a request came from the CDN.
   *
   * @return bool
   *   Returns true if the request came from the CDN.
   */
  public function currentRequestIsOnCdnHostname(): bool {
    $current_hostname = $this->requestStack->getCurrentRequest()->getHost();
    $config = $this->configFactory->get('mysite_workspaces_preview_protector.settings');
    $cdn_hostnames = $config->get('cdn_hostnames') ?? [];

    return in_array($current_hostname, $cdn_hostnames);
  }

  /**
   * Returns config items used to determine whether CDN is active.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   This module's config.
   */
  public function getCacheableDependency(): ImmutableConfig {
    return $this->configFactory->get('mysite_workspaces_preview_protector.settings');
  }

}
