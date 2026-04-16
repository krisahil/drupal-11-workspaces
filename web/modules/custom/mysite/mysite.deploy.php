<?php

/**
 * @file
 * Deploy hooks for mysite module.
 */

/**
 * Publishes the "April 2026" workspace.
 */
function mysite_deploy_001_publish_workspace_for_april_2026() {
  $storage = \Drupal::entityTypeManager()->getStorage('workspace');
  /** @var \Drupal\workspaces\Entity\Workspace[] $workspaces */
  $workspaces = $storage->loadByProperties(['label' => 'April 2026']);
  $workspace = reset($workspaces);
  $workspace->publish();

  return t('Successfully published workspace: @name', ['@name' => $workspace->label()]);
}
