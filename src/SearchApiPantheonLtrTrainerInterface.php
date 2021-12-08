<?php

namespace Drupal\search_api_pantheon_ltr;

/**
 * Interface for search_api_pantheon_ltr_trainer plugins.
 */
interface SearchApiPantheonLtrTrainerInterface {

  /**
   * Training function that uses the configuration provided and returns a model.
   *
   * @param string $data
   *   The training data.
   *
   * @return string
   *   The JSON model to upload to solr.
   */
  public function train($data);

}
