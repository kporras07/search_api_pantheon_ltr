<?php

namespace Drupal\search_api_pantheon_ltr\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrInterface;

/**
 * Defines the search api pantheon ltr settings entity type.
 *
 * @ConfigEntityType(
 *   id = "search_api_pantheon_ltr",
 *   label = @Translation("Search API Pantheon LTR Setting"),
 *   label_collection = @Translation("Search API Pantheon LTR Settings"),
 *   label_singular = @Translation("search api pantheon ltr setting"),
 *   label_plural = @Translation("search api pantheon ltr settings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count search api pantheon ltr setting",
 *     plural = "@count search api pantheon ltr settings",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrListBuilder",
 *     "form" = {
 *       "add" = "Drupal\search_api_pantheon_ltr\Form\SearchApiPantheonLtrForm",
 *       "edit" = "Drupal\search_api_pantheon_ltr\Form\SearchApiPantheonLtrForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "search_api_pantheon_ltr",
 *   admin_permission = "administer search_api_pantheon_ltr",
 *   links = {
 *     "collection" = "/admin/config/search/search-api/ltr",
 *     "add-form" = "/admin/config/search/search-api/ltr/add",
 *     "edit-form" = "/admin/config/search/search-api/ltr/{search_api_pantheon_ltr}",
 *     "delete-form" = "/admin/config/search/search-api/ltr/{search_api_pantheon_ltr}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "indexId",
 *     "model",
 *     "docs",
 *     "relevancyAnnotations",
 *     "ltrTrainerMethod",
 *     "ltrTrainerConfiguration",
 *   }
 * )
 */
class SearchApiPantheonLtr extends ConfigEntityBase implements SearchApiPantheonLtrInterface {

  /**
   * The Search Api Index Id.
   *
   * @var string
   */
  protected $indexId;

  /**
   * The Config Entity Id.
   *
   * @var string
   */
  protected $id;

  /**
   * The Config Entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The model that is used.
   *
   * @var string
   */
  protected $model;

  /**
   * The amount of documents to be reranked.
   *
   * @var int
   */
  protected $docs;

  /**
   * The json blob of the search result relevancy indicator.
   *
   * @var string
   */
  protected $relevancyAnnotations;

  /**
   * The training plugin id that is used.
   *
   * @var string
   */
  protected $ltrTrainerMethod;

  /**
   * The training plugin configuration.
   *
   * @var string
   */
  protected $ltrTrainerConfiguration;

  /**
   * Returns the ID of the sorts field.
   *
   * @return string
   *   The ID of the sorts field.
   */
  public function getIndexId() {
    return $this->indexId;
  }

  /**
   * Sets the index id.
   *
   * @@param string $indexId
   *   The search api index id.
   */
  public function setIndexId($indexId) {
    $this->indexId = $indexId;
  }

  /**
   * Returns the model in use.
   *
   * @return string
   *   The id of the model.
   */
  public function getModel() {
    return $this->model;
  }

  /**
   * Sets the model.
   *
   * @param string $model
   *   The model to be used.
   */
  public function setModel($model) {
    $this->model = $model;
  }

  /**
   * Returns the amount of docs to be reranked.
   *
   * @return int
   *   The amount of docs to be reranked.
   */
  public function getDocs() {
    return $this->docs;
  }

  /**
   * Sets the amount of docs to be reranked.
   *
   * @param int $docs
   *   The amount of docs to be reranked.
   */
  public function setDocs($docs) {
    $this->docs = $docs;
  }

  /**
   * Returns the blob of the relevancy annotations.
   *
   * Represented as json data blob.
   *
   * @return string
   *   The json blob.
   */
  public function getRelevancyAnnotations() {
    return $this->relevancyAnnotations;
  }

  /**
   * Set the Relevancy Annotations.
   *
   * @param string $relevancyAnnotations
   *   The json blob of the search result relevancy indicator.
   */
  public function setRelevancyAnnotations($relevancyAnnotations) {
    $this->relevancyAnnotations = $relevancyAnnotations;
  }

  /**
   * Get the LTR Trainer Method.
   *
   * @return string
   *   The LTR Trainer Method.
   */
  public function getLtrTrainerMethod() {
    return $this->ltrTrainerMethod;
  }

  /**
   * Set the LTR Trainer Method.
   *
   * @param string $ltrTrainerMethod
   *   The Trainer method used.
   */
  public function setLtrTrainerMethod(string $ltrTrainerMethod) {
    $this->ltrTrainerMethod = $ltrTrainerMethod;
  }

  /**
   * Get the LTR Trainer Configuration.
   *
   * @return string
   *   The LTR Trainer Method.
   */
  public function getLtrTrainerConfiguration() {
    return $this->ltrTrainerConfiguration;
  }

  /**
   * Set the LTR Trainer Configuration.
   *
   * @param string $ltrTrainerConfiguration
   *   The Trainer method configuration.
   */
  public function setLtrTrainerConfiguration(string $ltrTrainerConfiguration) {
    $this->ltrTrainerConfiguration = $ltrTrainerConfiguration;
  }

  /**
   * Get the Entity ID.
   *
   * @return string
   *   The Entity ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set the Entity ID.
   *
   * @param string $id
   *   The Entity ID.
   */
  public function setId(string $id) {
    $this->id = $id;
  }

  /**
   * Returns the label field.
   *
   * @return string
   *   The label field.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Sets the entity label.
   *
   * @@param string $label
   *   The entity label.
   */
  public function setLabel($label) {
    $this->label = $label;
  }

}
