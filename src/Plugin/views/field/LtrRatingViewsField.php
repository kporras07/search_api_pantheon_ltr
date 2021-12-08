<?php

namespace Drupal\search_api_pantheon_ltr\Plugin\views\field;

use Drupal\search_api_pantheon_ltr\Form\SearchApiPantheonLtrSelectForm;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("ltr_rating_views_field")
 */
class LtrRatingViewsField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // If the result did not come from Solr, do not render a button.
    if (empty($values->_item->getExtraData('search_api_solr_document'))) {
      return;
    }

    // Get Search Keys.
    $originalKeys = $this->query->getOriginalKeys();
    // Do not do anything if there are no keys.
    if (empty($originalKeys)) {
      return;
    }

    // Get Search API Index.
    $index = $values->_item->getIndex();

    // Get Search API Solr document id.
    /** @var \Solarium\QueryType\Select\Result\Document $solrDocument */
    $solrDocument = $values->_item->getExtraData('search_api_solr_document');
    $documentId = $solrDocument->getFields()['id'];

    return \Drupal::formBuilder()
      ->getForm(SearchApiPantheonLtrSelectForm::class, $index, $documentId, $originalKeys);

  }

}
