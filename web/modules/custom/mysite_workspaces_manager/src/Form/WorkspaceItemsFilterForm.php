<?php

namespace Drupal\mysite_workspaces_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;

/**
 * Provides a form to filter items in a Workspace changes list.
 *
 * Upon successful submission, this form redirects the user to a Workspace's
 * page, and appends the form's choices as URL query values. We expect the
 * Workspace page to read these query values and filter the changes list
 * accordingly.
 *
 * @see \Drupal\mysite_workspaces_manager\WorkspaceViewBuilder
 */
final class WorkspaceItemsFilterForm extends FormBase implements WorkspaceSafeFormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mysite_workspaces_manager_workspace_items_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $build_info = $form_state->getBuildInfo();
    [$search_type_options] = $build_info['args'];

    $form['search_title'] = [
      '#type' => 'textfield',
      '#attributes' => [
        'name' => 'search_title',
      ],
      '#title' => $this->t('Title'),
      '#default_value' => $form_state->getValue('search_title') ?: $this->getRequest()->query->get('search_title', NULL),
    ];
    $form['search_type'] = [
      '#type' => 'select',
      '#attributes' => [
        'name' => 'search_type',
      ],
      '#title' => $this->t('Type'),
      '#empty_value' => '',
      '#default_value' => $form_state->getValue('search_type') ?: $this->getRequest()->query->get('search_type', NULL),
      '#options' => [
        '' => $this->t('Choose type'),
      ] + $search_type_options,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Filter'),
      ],
      'reset' => [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
      ],
    ];

    // Adds styles that are similar to Views Exposed Filters styles.
    $form['#attached']['library'][] = 'mysite_workspaces_manager/workspace-filter-form';
    $form['#attributes']['class'][] = 'workspace-manager-filter-form';

    return $form;
  }

  /**
   * Resets the filter selections.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $current_route = $this->getRouteMatch()->getRouteName();
    $workspace = $this->getRouteMatch()->getParameters()->get('workspace');

    $form_state->setRedirect($current_route, ['workspace' => $workspace->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $search_title = $form_state->getValue('search_title');
    $search_type = $form_state->getValue('search_type');

    $current_route = $this->getRouteMatch()->getRouteName();
    $workspace = $this->getRouteMatch()->getParameters()->get('workspace');

    $form_state->setRedirect($current_route, ['workspace' => $workspace->id()],
      ['query' => ['search_title' => $search_title, 'search_type' => $search_type]]
    );
  }

}
