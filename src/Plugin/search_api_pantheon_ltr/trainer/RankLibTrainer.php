<?php

namespace Drupal\search_api_pantheon_ltr\Plugin\search_api_pantheon_ltr\trainer;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_pantheon_ltr\SearchApiPantheonLtrTrainerPluginBase;

/**
 * Provides ltr training with a local ranklib jar file.
 *
 * @SearchApiLtrLtrTrainer(
 *   id = "ranklib_trainer",
 *   label = @Translation("Direct Ranklib.jar LTR Trainer"),
 *   description = @Translation("Adds LTR Ranklib training support."),
 * )
 */
class RankLibTrainer extends SearchApiPantheonLtrTrainerPluginBase {

  /**
   * Ltr Training method.
   *
   * @param string $data
   *   The training data.
   *
   * @return string
   *   The JSON model to upload to solr.
   */
  public function train($data) {

    $fileInputPath = $this->fileSystem->saveData($data, 'temporary://', FileSystemInterface::EXISTS_RENAME);
    $fileOutputPath = $this->fileSystem->saveData('', 'temporary://', FileSystemInterface::EXISTS_RENAME);
    $ranklib = realpath($this->configuration['ranklibPath']);
    $java = $this->configuration['javaPath'];

    // Specify what kind of model we want to generate. For now we focus on
    // Lambdamart.
    // 0: MART (gradient boosted regression tree)
    // 1: RankNet
    // 2: RankBoost
    // 3: AdaRank
    // 4: Coordinate Ascent
    // 5: Cannot be used, is deprecated.
    // 6: LambdaMART
    // 7: ListNet
    // 8: Random Forests
    // 9: Linear regression (L2 regularization)
    $param = ' -ranker 6';
    // How deep should we look for connections. So far we've found good results
    // with 100.
    $param .= ' -tree 100';
    // The input data to train the model with.
    $param .= ' -train ' . escapeshellarg($this->fileSystem->realpath($fileInputPath));
    // The metric we want to train the model with. Could be one of
    // "P", "NDCG", "RR", "ERR", "DCG". We have found the best results with
    // NDCG.
    $param .= ' -metric2t NDCG@10';
    // Store the model to a tmp file.
    $param .= ' -save ' . escapeshellarg($this->fileSystem->realpath($fileOutputPath));
    // Do not output anything.
    $param .= ' -silent';
    $cmd = $java . ' -jar ' . $ranklib . ' ' . $param;
    shell_exec($cmd);
    // @todo, is this the best option?
    $realFileOutputPath = $this->fileSystem->realpath($fileOutputPath);
    $model = file_get_contents($realFileOutputPath);
    return $model;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['java_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to java executable'),
      '#description' => $this->t('Enter the path to java executable. Example: "java".'),
      '#default_value' => $this->configuration['javaPath'],
      '#required' => TRUE,
    ];
    $form['ranklib_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to Ranklib .jar file'),
      '#description' => $this->t('Enter the full path to Ranklib executable jar file. Example: "/var/ranklib/ranklib.jar".'),
      '#default_value' => $this->configuration['ranklibPath'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['ltr_trainer_config']);
    $java_path = $values['java_path'];
    $ranklib_path = $values['ranklib_path'];

    // Check java path.
    exec($java_path, $output, $return_code);
    // $return_code = 127 if it fails. 1 instead.
    if ($return_code != 1) {
      $form_state->setError($form['ltr_trainer_config']['java_path'], $this->t('Invalid path or filename %path for java executable.', ['%path' => $java_path]));
      return;
    }

    // Check tika path.
    if (!file_exists($ranklib_path)) {
      $form_state->setError($form['ltr_trainer_config']['ranklib_path'], $this->t('Invalid path or filename %path for ranklib application jar.', ['%path' => $ranklib_path]));
    }
    // Check return code.
    else {
      $cmd = $java_path . ' -jar ' . escapeshellarg($ranklib_path) . ' -V';
      exec($cmd, $output, $return_code);
      // $return_code = 1 if it fails. 0 instead.
      if ($return_code) {
        $form_state->setError($form['ltr_trainer_config']['ranklib_path'], $this->t('Ranklib could not be reached and executed.'));
      }
      else {
        $this->getMessenger()
          ->addStatus(t('Ranklib can be reached and be executed'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['javaPath'] = $form_state->getValue(['ltr_trainer_config', 'java_path']);
    $this->configuration['ranklibPath'] = $form_state->getValue(['ltr_trainer_config', 'ranklib_path']);
    parent::submitConfigurationForm($form, $form_state);
  }

}
