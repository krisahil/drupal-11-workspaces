<?php

declare(strict_types=1);

namespace Drupal\mysite_workspaces_preview_indicator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Workspace status indicator block for non-admins.
 *
 * Inspired by wse.module's Workspace indicator, which is implemented as an
 * "extra" field. Why not use that? For these reasons:
 * 1) As an "extra" field, we must place it on an entity's view display. Our
 * most prominent page is Flexible page node, which uses a unique Layout
 * Builder config for each node. We would have to somehow add it to every one of
 * those LB configs, which is not feasible.
 * 2) That field's business logic is not conducive to our needs.
 * So, instead of extending and overwriting this field, it's simpler to write
 * this new block, which copies some of that field's logic.
 *
 * @Block(
 *   id = "mysite_workspaces_preview_indicator",
 *   admin_label = @Translation("MySite Workspace preview indicator"),
 *   category = @Translation("Custom"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node")
 *   }
 * )
 *
 * @see \wse_entity_extra_field_info()
 * @see \wse_entity_view()
 */
final class WorkspacePreviewIndicatorBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the plugin instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WorkspaceManagerInterface $workspaceManager,
    protected Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('workspaces.manager'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];

    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getContextValue('node');

    if (!$this->workspaceManager->hasActiveWorkspace()) {
      return $build;
    }

    $warning = FALSE;
    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    $active_workspace_label = $active_workspace->label();
    $active_workspace_has_the_latest_revision = FALSE;
    $latest_revision_workspace_id = NULL;

    // Gets this node's latest revision ID, then sees if that revision is being
    // managed in a workspace.
    // We previously used
    // \Drupal\workspaces\WorkspaceManager::executeOutsideWorkspace to perform
    // these look-ups outside the current workspace, but that caused an
    // unfortunate and intermittent side effect: the workspace preview cookie
    // (wse_preview) got deleted because the workspace was, during this
    // operation, switched. Learn more in
    // \Drupal\wse_preview\Negotiator\CookieWorkspaceNegotiator.
    // See https://www.drupal.org/project/wse/issues/3571367.
    $latest_revision_id = $this->database->select('node_revision', 'nr')
      ->fields('nr', ['vid'])
      ->condition('nr.nid', $node->id())
      ->orderBy('nr.vid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if ($latest_revision_id) {
      $latest_revision_workspace_id = $this->database->select('workspace_association', 'wa')
        ->fields('wa', ['workspace'])
        ->condition('wa.target_entity_type_id', 'node')
        ->condition('wa.target_entity_revision_id', $latest_revision_id)
        ->execute()
        ->fetchField();
    }

    if ($latest_revision_workspace_id) {
      /** @var \Drupal\workspaces\WorkspaceInterface $latest_revision_workspace */
      $latest_revision_workspace = $this->entityTypeManager
        ->getStorage('workspace')
        ->load($latest_revision_workspace_id);

      if ($active_workspace->id() === $latest_revision_workspace_id) {
        $active_workspace_has_the_latest_revision = TRUE;
      }
      else {
        $warning = TRUE;
      }
    }

    $build['notice'] = [
      '#theme' => 'mysite_workspaces_preview_indicator',
      '#active_workspace' => $active_workspace_label,
      '#node_has_changes_in_a_workspace' => isset($latest_revision_workspace),
      '#workspace_with_latest_node_change' => isset($latest_revision_workspace) ? $latest_revision_workspace->label() : '',
      '#active_workspace_has_latest_node_change' => $active_workspace_has_the_latest_revision,
      '#warning' => $warning,
      '#attached' => [
        'library' => ['mysite_workspaces_preview_indicator/workspace_preview_indicator'],
      ],
    ];

    return $build;
  }

}
