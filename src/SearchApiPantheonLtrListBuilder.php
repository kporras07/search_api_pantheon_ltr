<?php

namespace Drupal\search_api_pantheon_ltr;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of search api pantheon ltr settingses.
 */
class SearchApiPantheonLtrListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['model'] = $this->t('Model');
    $header['docs'] = $this->t('Docs');
    $header['method'] = $this->t('Method');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['model'] = $entity->getModel();
    $row['docs'] = $entity->getDocs();
    $row['method'] = $entity->getLtrTrainerMethod();
    return $row + parent::buildRow($entity);
  }

}
