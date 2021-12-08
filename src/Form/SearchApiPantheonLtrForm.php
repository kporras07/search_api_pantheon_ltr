<?php

namespace Drupal\search_api_pantheon_ltr\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Search API Pantheon LTR Settings form.
 *
 * @property \Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrInterface $entity
 */
class SearchApiPantheonLtrForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the search api pantheon ltr settings.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\search_api_pantheon_ltr\Entity\SearchApiPantheonLtr::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    // @todo: Replace with list of indexes.
    $form['indexId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index Id'),
      '#default_value' => $this->entity->getIndexId(),
    ];

    // @todo: Replace with list of models.
    $form['model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Model'),
      '#default_value' => $this->entity->getModel(),
    ];

    $form['docs'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Docs'),
      '#default_value' => $this->entity->getDocs(),
    ];

    $form['relevancyAnnotations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Relevancy Annotations'),
      '#default_value' => $this->entity->getRelevancyAnnotations(),
    ];

    // @todo: Replace with list of methods.
    $form['ltrTrainerMethod'] = [
      '#type' => 'textfield',
      '#title' => $this->t('LTR Trainer Method'),
      '#default_value' => $this->entity->getLtrTrainerMethod(),
    ];

    $form['ltrTrainerConfiguration'] = [
      '#type' => 'textarea',
      '#title' => $this->t('LTR Trainer Configuration'),
      '#default_value' => $this->entity->getLtrTrainerConfiguration(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new Search Api Pantheon LTR Setting: %label.', $message_args)
      : $this->t('Updated Search Api Pantheon LTR Setting: %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $result;
  }

}
