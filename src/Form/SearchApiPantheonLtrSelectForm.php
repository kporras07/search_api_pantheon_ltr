<?php

namespace Drupal\search_api_pantheon_ltr\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SearchApiLtrSettingsForm.
 */
class SearchApiPantheonLtrSelectForm extends FormBase {

  /**
   * The search api ltr settings storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $searchApiPantheonLtrStorage;

  /**
   * The index these search api ltr settings apply to.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The index these search api ltr settings apply to.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The form instance id.
   *
   * @var instanceId
   */
  protected static $instanceId;

  /**
   * Constructs the DisplaySortsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   *
   * @throws \Exception
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->searchApiPantheonLtrStorage = $entity_type_manager->getStorage('search_api_pantheon_ltr');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (empty(self::$instanceId)) {
      self::$instanceId = 1;
    }
    else {
      self::$instanceId++;
    }

    return 'search_api_pantheon_ltr_select_' . self::$instanceId;
  }

  /**
   * Defines the settings form for Search elevate entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The search index in question.
   * @param string $documentId
   *   The Document ID from Solr for which we are trying to learn to rank.
   * @param string $originalKeys
   *   The search keys given to the form.
   *
   * @return array
   *   Form definition array.
   *
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state, IndexInterface $search_api_index = NULL, $documentId = NULL, $originalKeys = NULL) {
    $this->index = $search_api_index;
    $this->server = $search_api_index->getServerInstance();
    // Check if we support this feature.
    if (!$this->server->supportsFeature('search_api_pantheon_ltr')) {
      return [];
    }

    $searchApiPantheonLtrStorage = \Drupal::service('entity_type.manager')->getStorage('search_api_pantheon_ltr');

    /** @var \Drupal\search_api_pantheon_ltr\Entity\SearchApiLtr $searchApiLtrSetting */
    $searchApiLtrSetting = $searchApiPantheonLtrStorage->load($search_api_index->id());
    $annotations = $searchApiLtrSetting->getRelevancyAnnotations();

    if (!empty($annotations)) {
      $annotations = \GuzzleHttp\json_decode($annotations);
    }
    else {
      $annotations = new \stdClass();
    }

    $default = 0;
    if (!empty($annotations->{$originalKeys}->{$documentId})) {
      $default = $annotations->{$originalKeys}->{$documentId};
    }

    $form['#attributes']['id'] = 'search_api_pantheon_ltr_select-' . $documentId;

    // Make Renderable Array with custom template twig.
    // Wrapped in form type radios so that we can remove the colon.
    $form['ranking'] = [
      '#prefix' => '<div class="form-type-radios"><div class="container-inline">',
      '#type' => 'radios',
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => '',
        ],
      ],
      '#default_value' => $default,
      '#options' => [
        0 => t('Not Relevant'),
        1 => t('Somewhat Relevant'),
        2 => t('Relevant'),
        3 => t('Highly Relevant'),
      ],
      '#suffix' => '</div></div>',
    ];

    $form['document_id'] = [
      '#type' => 'hidden',
      '#value' => $documentId,
    ];

    $form['original_keys'] = [
      '#type' => 'hidden',
      '#value' => $originalKeys,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#access' => FALSE,
    ];

    return $form;
  }

  /**
   * Submitting the option form through ajax.
   *
   * @param array $form
   *   Form array configuration.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state values.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Validation fix for ajax submissions.
    if ($form_state->getErrors()) {
      return $response;
    }

    $searchApiPantheonLtrStorage = \Drupal::service('entity_type.manager')->getStorage('search_api_pantheon_ltr');
    /** @var \Drupal\search_api_pantheon_ltr\Entity\SearchApiLtr $searchApiLtrSetting */
    $config = $searchApiPantheonLtrStorage->load($this->index->id());
    $annotations = $config->getRelevancyAnnotations();

    if (!empty($annotations)) {
      $annotations = \GuzzleHttp\json_decode($annotations);
    }
    else {
      $annotations = new \stdClass();
    }

    $annotations->{$form_state->getValues()['original_keys']}->{$form_state->getValues()['document_id']} = (int) $form_state->getValues()['ranking'];

    // Change the annotations and save the config entity here.
    $config->setRelevancyAnnotations(\GuzzleHttp\json_encode($annotations));
    $config->save();

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Is not used but necessary for the interface.
  }

  /**
   * Checks access for the LTR config form.
   *
   * @param \Drupal\search_api\IndexInterface $search_api_index
   *   The index for which access should be tested.
   *   For now we only support Solr, as soon as the feature is added to
   *   Search Api Solr we can remove this and get the functions from the
   *   backend.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Exception
   */
  public function access(IndexInterface $search_api_index) {
    $search_api_server = $search_api_index->getServerInstance();

    return AccessResult::allowedIf(
      $search_api_server->hasValidBackend()
      && $search_api_server->supportsFeature('search_api_pantheon_ltr')
    )->addCacheableDependency($search_api_index);
  }

}
