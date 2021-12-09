<?php

namespace Drupal\search_api_pantheon_ltr\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api_pantheon_ltr\Entity\SearchApiPantheonLtr;
use Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrTrainerPluginManager;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Search API Pantheon LTR Settings form.
 *
 * @property \Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrInterface $entity
 */
class SearchApiPantheonLtrForm extends EntityForm {

  /**
   * Ltr Trainer plugin Manager.
   *
   * @var \Drupal\search_api_pantheon_ltr\LtrTrainerPluginManager
   */
  private $ltrTrainerPluginManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Search Api Ltr Config Entity Manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $searchApiLtrStorage;

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
   * {@inheritdoc}
   */
  public function __construct(SearchApiPantheonLtrTrainerPluginManager $ltr_trainer_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->ltrTrainerPluginManager = $ltr_trainer_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->searchApiLtrStorage = $this->entityTypeManager->getStorage('search_api_pantheon_ltr');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search_api_pantheon_ltr_trainer'),
      $container->get('entity_type.manager')
    );
  }

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
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['indexId'] = [
      '#type' => 'select',
      '#title' => $this->t('Solr Index'),
      '#default_value' => $this->entity->getIndexId(),
      '#options' => $this->getSolrIndexes(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [get_class($this), 'buildAjaxModel'],
        'wrapper' => 'search-api-ltr-ltr-model-wrapper',
        'method' => 'replace',
        'effect' => 'fade',
      ],
    ];

    if (!empty($form['indexId']['#default_value'])) {
      $this->index = $this->entityTypeManager->getStorage('search_api_index')->load($form['indexId']['#default_value']);
      $this->server = $this->index->getServerInstance();
    }

    // See if the backend implemented.
    // \Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrBackendInterface.
    $models = [];
    if ($this->server) {
      if (method_exists($this->server, 'getLtrModels')) {
        $models = $this->server->getLtrModels();
      }
      // Remove this as soon as this was added to Search Api Solr.
      elseif ($this->server->getBackend() instanceof SearchApiSolrBackend) {
        // Get Solr Backend.
        /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend $solrBackend */
        $solrBackend = $this->index->getServerInstance()->getBackend();

        // Check if Solr is available, do not show anything if it isn't.
        if ($solrBackend->isAvailable()) {
            $response = $solrBackend->getSolrConnector()->coreRestGet('schema/model-store');
            if (!empty($response['models'])) {
              foreach ($response['models'] as $model) {
                $models[] = $model['name'];
              }
            }
        }
      }
    }
    // Assume we have our models now, either by support of our own or by the
    // backend itself.
    $options = [];
    foreach ($models as $model) {
      $options[$model] = $model;
    }
    $form['model'] = [
      '#type' => 'select',
      '#title' => $this->t('Learn To Rank Model'),
      '#options' => $options,
      // Retrieve the per index setting.
      '#default_value' => $this->entity->getModel(),
      '#description' => $this->t('The model to use for this index'),
      '#required' => TRUE,
      '#prefix' => '<div id="search-api-ltr-ltr-model-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['docs'] = [
      '#type' => 'number',
      '#title' => $this->t('How many documents to rerank?'),
      // Retrieve the per index setting.
      '#default_value' => $this->entity->getDocs(),
      '#description' => $this->t('The amount of documents to rerank'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 1,
      '#required' => TRUE,
    ];

    $form['relevancyAnnotations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Relevancy Annotations'),
      '#description' => $this->t('The json representation of the training data for the LTR model.'),
      '#default_value' => $this->entity->getRelevancyAnnotations(),
    ];

    $form['ltrTrainerMethod'] = [
      '#type' => 'select',
      '#title' => $this->t('Ltr Trainer method'),
      '#description' => $this->t('Select the Ltr trainer method you want to use.'),
      '#empty_value' => '',
      '#options' => $this->getLtrTrainerPluginInformations(),
      '#default_value' => $this->entity->getLtrTrainerMethod(),
      '#required' => TRUE,
      /*'#ajax' => [
        'callback' => [get_class($this), 'buildAjaxLtrTrainerConfigForm'],
        'wrapper' => 'search-api-ltr-ltr-trainer-config-form',
        'method' => 'replace',
        'effect' => 'fade',
      ],*/
    ];

    $form['ltrTrainerConfiguration'] = [
      '#type' => 'textarea',
      '#title' => $this->t('LTR Trainer Configuration'),
      '#default_value' => $this->entity->getLtrTrainerConfiguration(),
    ];

    return $form;
  }

  /**
   * Return model via ajax.
   */
  public static function buildAjaxModel(array $form, FormStateInterface $form_state) {
    return $form['model'];
  }

  // @todo: Build, validate and submit trainer configuration.

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

  /**
   * Get definition of Ltr Trainer plugins from their annotation definition.
   *
   * @return array
   *   Array with 'labels' of plugins and ids.
   */
  public function getLtrTrainerPluginInformations() {
    $options = [];
    foreach ($this->ltrTrainerPluginManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = Html::escape($plugin_definition['label']);
    }
    return $options;
  }

  /**
   * Get list of existing solr indexes.
   *
   * @return array
   *   Array with 'labels' of solr indexes and ids.
   */
  public function getSolrIndexes() {
    $options = [];
    $items = $this->entityTypeManager->getStorage('search_api_index')->loadMultiple();
    foreach ($items as $item) {
      $options[$item->id()] = Html::escape($item->label());
    }
    return $options;
  }

}
