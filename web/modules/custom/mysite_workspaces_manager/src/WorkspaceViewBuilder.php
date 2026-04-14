<?php

namespace Drupal\mysite_workspaces_manager;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\Element;
use Drupal\workspaces\WorkspaceViewBuilder as CoreWorkspaceViewBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filterable Workspace view builder.
 *
 * This builder filters the changes list based on URL query values. If those are
 * set, the user was (probably) sent here by
 * \Drupal\mysite_workspaces_manager\Form\WorkspaceItemsFilterForm::submitForm.
 *
 * @see \Drupal\mysite_workspaces_manager\Form\WorkspaceItemsFilterForm
 */
class WorkspaceViewBuilder extends CoreWorkspaceViewBuilder {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Drupal form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritDoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->requestStack = $container->get('request_stack');
    $instance->formBuilder = $container->get('form_builder');

    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * Allows to show all changed entities in this workspace.
   */
  protected int|false $limit = FALSE;

  /**
   * {@inheritdoc}
   *
   * Adds basic form filtering to the entities, to filter by entity type+bundle
   * and entity label.
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    $search_title = $this->requestStack->getCurrentRequest()->query->get('search_title', '');
    $build['#cache']['contexts'][] = 'url.query_args:search_title';
    $search_type = $this->requestStack->getCurrentRequest()->query->get('search_type', '');
    $build['#cache']['contexts'][] = 'url.query_args:search_type';

    // If a filter has been selected, this code filters non-matching items, by
    // removing them entirely (setting #access=false) does not seem to work.
    foreach (Element::children($build) as $workspace_id) {
      // Counts the number of items, before filtering. We will count after
      // filtering, also, to present a message indicating how many items match
      // the filter(s).
      $total_initial = count(Element::children($build[$workspace_id]['changes']['list']));
      $search_type_options = [];

      // Adds "Publish status" column to the table, as the third column.
      // Unfortunately, there isn't a simple way to add a new item at an
      // arbitrary location in an associative array, hence we overwrite the
      // array entirely.
      // See corresponding code below that re-orders the data columns to match.
      $build[$workspace_id]['changes']['list']['#header'] = [
        'title' => $this->t('Title'),
        'type' => $this->t('Type'),
        'publish_status' => $this->t('Publish status'),
        'changed' => $this->t('Last changed'),
        'owner' => $this->t('Author'),
        'operations' => $this->t('Operations'),
      ];

      foreach (Element::children($build[$workspace_id]['changes']['list']) as $row_id) {
        $row = $build[$workspace_id]['changes']['list'][$row_id];
        /** @var \Drupal\Core\Entity\RevisionableContentEntityBase $entity */
        $entity = $row['#entity'];

        $title_label = '';
        $type_label = '';

        // Gets the item's title.
        if (isset($row['title']['#type'])) {
          $title_label = match ($row['title']['#type']) {
            'link' => $row['title']['#title'],
            default => '',
          };
        }
        elseif (isset($row['title']['#markup'])) {
          $title_label = $row['title']['#markup'];
        }

        // Gets the item's entity type+bundle.
        if (isset($row['type']['#markup'])) {
          $type_label = (string) $row['type']['#markup'];
          $search_type_options[$type_label] = $type_label;
        }

        if (!empty($search_title)) {
          if (!empty($title_label) && stripos($title_label, $search_title) === FALSE) {
            unset($build[$workspace_id]['changes']['list'][$row_id]);
            continue;
          }
        }
        if (!empty($search_type)) {
          if (!empty($type_label) && stripos($type_label, $search_type) === FALSE) {
            unset($build[$workspace_id]['changes']['list'][$row_id]);
            continue;
          }
        }

        // Adds data for the "Publish status" column.
        if ($entity->hasField('moderation_state') && !$entity->get('moderation_state')->isEmpty()) {
          // Workflow Moderation is more complex than I anticipated.
          $state_id = $entity->get('moderation_state')->getValue()[0]['value'];
          $workflow_id = $this->getWorkflowIdForBundle($entity->bundle());
          $publish_status_label = $workflow_id ? $this->getWorkflowStateLabel($state_id, $workflow_id) : $state_id;
        }
        elseif (method_exists($entity, 'isPublished')) {
          $publish_status_label = $entity->isPublished() ? $this->t('Published') : $this->t('Not published');
        }
        else {
          $publish_status_label = $this->t('Unknown');
        }
        $build[$workspace_id]['changes']['list'][$row_id]['publish_status']['#markup'] = $publish_status_label;

        // Re-orders the data columns to match the header, after we injected
        // "Publish status" as the third column.
        // This code is ugly. I tried using #weight, but that didn't seem to
        // work. There must be a better way!
        $intended_order = [
          'title',
          'type',
          'publish_status',
          'changed',
          'owner',
          'operations',
        ];
        $row_sorted = $build[$workspace_id]['changes']['list'][$row_id];
        foreach ($intended_order as $key) {
          unset($row_sorted[$key]);
          $row_sorted[$key] = $build[$workspace_id]['changes']['list'][$row_id][$key];
        }
        $build[$workspace_id]['changes']['list'][$row_id] = $row_sorted;
      }
      // Counts the number of items, after filtering.
      $total_after = count(Element::children($build[$workspace_id]['changes']['list']));

      // If filters have changed the result count, this prints a message to
      // summarize that.
      if ($total_after < $total_initial) {
        $build[$workspace_id]['changes']['filtered_alert'] = [
          '#type' => 'item',
          '#title' => $this->t('Filtered results'),
          'message' => [
            '#type' => 'markup',
            '#markup' => $this->t('Showing @filtered of @total',
              ['@filtered' => $total_after, '@total' => $total_initial]
            ),
          ],
        ];
        // We want to show this message after the overview but before the list
        // of results.
        $build[$workspace_id]['changes']['overview']['#weight'] = 10;
        $build[$workspace_id]['changes']['filtered_alert']['#weight'] = 20;
        $build[$workspace_id]['changes']['list']['#weight'] = 30;
      }
      $search_type_options = array_unique($search_type_options);

      $form = $this->formBuilder->getForm('\Drupal\mysite_workspaces_manager\Form\WorkspaceItemsFilterForm', $search_type_options);
      $build[$workspace_id]['form_container'] = [
        '#type' => 'container',
        '#weight' => -50,
        'form' => $form,
      ];
    }
  }

  /**
   * Gets the label for a workflow state.
   *
   * @param string $state_id
   *   The workflow state machine name.
   * @param string $workflow_id
   *   The workflow ID.
   *
   * @return string
   *   The workflow state label.
   */
  protected function getWorkflowStateLabel(string $state_id, string $workflow_id): string {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $this->entityTypeManager->getStorage('workflow')->load($workflow_id);
    $state = $workflow->getTypePlugin()->getState($state_id);
    return $state ? $state->label() : $state_id;
  }

  /**
   * Gets the workflow ID for a node type.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return string|null
   *   The workflow ID or NULL if no workflow is configured.
   */
  protected function getWorkflowIdForBundle(string $bundle): ?string {
    $workflows = $this->entityTypeManager->getStorage('workflow')->loadMultiple();
    foreach ($workflows as $workflow) {
      $type_settings = $workflow->getTypePlugin()->getConfiguration();
      if (isset($type_settings['entity_types']['node']) && in_array($bundle, $type_settings['entity_types']['node'])) {
        return $workflow->id();
      }
    }
    return NULL;
  }

}
