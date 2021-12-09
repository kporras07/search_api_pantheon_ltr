<?php

namespace Drupal\search_api_pantheon_ltr;

use Drupal\search_api\Backend\BackendInterface;

/**
 * Describes the method a backend plugin has to add to support Learn To Rank.
 *
 * In addition, the backend has to include "search_api_pantheon_ltr" in the
 * return value of its getSupportedFeatures() implementation.
 *
 * Please note that this interface is purely documentational. You shouldn't, and
 * can't, implement it explicitly (unless your module is depending on this one).
 */
interface SearchApiPantheonLtrBackendInterface extends BackendInterface {

  /**
   * Retrieves LTR Models from the backend.
   *
   * @return array
   *   An array of LTR models that can be selected from your backend.
   */
  public function getLtrModels();

}
