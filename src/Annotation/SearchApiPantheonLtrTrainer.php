<?php

namespace Drupal\search_api_pantheon_ltr\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines search_api_pantheon_ltr_trainer annotation object.
 *
 * @Annotation
 */
class SearchApiPantheonLtrTrainer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
