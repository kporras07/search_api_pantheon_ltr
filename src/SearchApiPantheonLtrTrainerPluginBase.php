<?php

namespace Drupal\search_api_pantheon_ltr;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for search_api_pantheon_ltr_trainer plugins.
 */
abstract class SearchApiPantheonLtrTrainerPluginBase extends PluginBase implements SearchApiPantheonLtrTrainerInterface {

  /**
   * Name of the config being edited.
   */
  const CONFIGNAME = 'search_api_pantheon_ltr.settings';

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition, $container->get('config.factory'), $container->get('file_system'), $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function train($data) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getmessenger() {
    return $this->messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration += $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do not do anything if the config is empty.
    if (empty($this->configuration)) {
      return;
    }

    $searchApiLtrStorage = \Drupal::service('entity_type.manager')->getStorage('search_api_pantheon_ltr');
    /** @var \Drupal\search_api_pantheon_ltr\Entity\SearchApiPantheonLtr $searchApiPantheonLtrSetting */
    $config_id = $form_state->getValue('configEntityId');
    $config = $searchApiLtrStorage->load($config_id);
    if (empty($config)) {
      return;
    }

    $ltr_trainer_plugin_id = $form_state->getValue('ltrTrainerMethod');
    $config->set($ltr_trainer_plugin_id . '_configuration', $this->configuration);
    $form['config'] = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
