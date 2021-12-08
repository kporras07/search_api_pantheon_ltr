<?php

namespace Drupal\search_api_pantheon_ltr\Commands;

use DateTime;
use Drupal\views\Views;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class LearnToRankCommands extends DrushCommands {

  /**
   * Echos back hello with the argument provided.
   *
   * @param string $search_api_view_id
   *   Search Api View that we can execute to get the training data.
   * @param string $search_api_view_display_id
   *   Search Api View Display Id that we can execute to get the training data.
   *
   * @command search_api_pantheon_ltr:train
   * @aliases ltr-train
   * @options arr An option that takes multiple values.
   * @options msg Based on the training data, make all the necessary requests to
   *   solr so that we can train a lambdarank model from the feedback.
   *
   * @usage search_api_pantheon_ltr:train search_api_view_id search_api_view_display_id
   *
   * @return string
   *   Shows the status of the Solr Response.
   *
   * @throws \Exception
   */
  public function train($search_api_view_id, $search_api_view_display_id) {
    $view = Views::getView($search_api_view_id);
    $view->setDisplay($search_api_view_display_id);

    /** @var \Drupal\search_api\Plugin\views\query\SearchApiQuery $query */
    $query = $view->getQuery()->query();
    $search_id = $query->getDisplayPlugin()->getPluginId();

    $searchApiLtrStorage = \Drupal::service('entity_type.manager')->getStorage('search_api_pantheon_ltr');
    /** @var \Drupal\search_api_pantheon_ltr\Entity\SearchApiLtr $config */
    $config = $searchApiLtrStorage->load($query->getIndex()->id());
    $annotations = $config->getRelevancyAnnotations();
    if (empty($annotations)) {
      return FALSE;
    }

    $annotations = \GuzzleHttp\json_decode($annotations);
    $qid = 0;
    $trainingSet = [];
    $featureMap = [];
    foreach ($annotations as $key => $annotation) {
      // Reinitialize the view so we do not get rendered/executed results.
      $view = Views::getView($search_api_view_id);
      $view->setDisplay($search_api_view_display_id);
      $view->setExposedInput(['query' => $key]);
      // Set to 50 pages, in most cases this means we will go 5 pages deep
      // instead of the default.
      $view->setItemsPerPage(10);
      $view->execute();

      /** @var \Drupal\search_api\Utility\QueryHelperInterface $queryHelper */
      $queryHelper = \Drupal::service('search_api.query_helper');
      $results = $queryHelper->getResults($search_id);
      foreach ($results->getResultItems() as $item) {
        /** @var \Solarium\QueryType\Select\Result\Document $document */
        $document = $item->getExtraData('search_api_solr_document');
        $fields = $document->getFields();
        // Check if the document appears in our annotations. Set this bit to
        // 1 if it does AND it was not set to irrelevant by checking the value
        $relevant = 0;
        if (isset($annotation->{$fields['id']}) && $annotation->{$fields['id']} !== 0) {
          $relevant = 1;
        }

        $features = explode(',', $fields['features']);
        $trainingRow = $relevant . ' qid:' . $qid;
        $featureRow = [];

        foreach ($features as $id => $feature) {
          // ids start at number 1 in this model. Important!
          $id++;
          $featureData = explode('=', $feature);
          // Store our featuremap for our training later.
          $featureMap[$id] = $featureData[0];
          $featureRow[] = $id . ':' . $featureData[1];
        }
        $trainingSet[] = $trainingRow . ' ' . implode(' ', $featureRow);
      }
      $qid++;
    }

    $trainingData = implode("\n", $trainingSet);

    $ltrTrainerPluginId = $config->getLtrTrainerMethod();
    $ltrTrainerPlugin = NULL;
    /** @var \Drupal\search_api_pantheon_ltr\LtrTrainerPluginManager $ltrTrainerPluginManager */
    $ltrTrainerPluginManager = \Drupal::service('plugin.manager.search_api_pantheon_ltr.ltr_trainer');
    if ($ltrTrainerPluginId) {
      $configuration = $config->get($ltrTrainerPluginId . '_configuration');
      if (empty($configuration)) {
        $configuration = [];
      }
      /** @var \Drupal\search_api_pantheon_ltr\LtrTrainerPluginInterface $ltrTrainerPlugin */
      $ltrTrainerPlugin = $ltrTrainerPluginManager
        ->createInstance($ltrTrainerPluginId, $configuration);
    }

    if (empty($ltrTrainerPlugin)) {
      return FALSE;
    }

    $model = $ltrTrainerPlugin->train($trainingData);
    // Generate name.
    $dateTime = new DateTime();
    $name = "lambdamart-" . $dateTime->format('Y-m-d-H-i-s');
    // Generate JSON Payload.
    $json = $this->toJson($model, $featureMap, $name);

    // Find our solr server.
    /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend $backend */
    $backend = $query->getIndex()->getServerInstance()->getBackend();
    $solrConnector = $backend->getSolrConnector();

    $output = $solrConnector->coreRestPost('/schema/model-store', $json);

    // @todo make this output nicer.
    if ($output['responseHeader']['status'] === 0) {
      return 'Succesfully trained & uploaded your Learning To Rank model and you can locate it with the name ' . $name . '.';
    }
    return 'Something went wrong. Please look at the following output' . print_r($output, TRUE);

  }

  /**
   * Convert the trained model to a json format understandable by Solr.
   *
   * @param string $model
   *   The trained model by lambdamart represented as XML.
   * @param array $featureMap
   *   The features as defined in Solr.
   *
   * @return false|string
   *   false if it failed, a json string if it succeeded.
   *
   * @throws \Exception
   */
  private function toJson(string $model, array $featureMap, string $name) {
    $model = preg_replace('/^#.*\\n/m', '', $model);
    $modelObject = new \SimpleXMLElement($model);
    $trees = [];
    foreach ($modelObject as $node) {
      $attributes = $node->attributes();
      if (isset($attributes['weight'])) {
        $t = [
          'weight' => trim((string) $attributes['weight']),
          'root' => $this->parseSplits($node->split, $featureMap),
        ];
        $trees[] = $t;
      }
    }

    // Create the json model.
    $map = [
      'class' => "org.apache.solr.ltr.model.MultipleAdditiveTreesModel",
      'name' => $name,
      'features' => [],
      'params' => [
        'trees' => $trees,
      ],
    ];
    foreach ($featureMap as $feature) {
      $map['features'][] = ['name' => $feature];
    }

    // Return the JSON data.
    return json_encode($map);

  }

  /**
   * Parse the splits given by the model.
   *
   * @param \SimpleXMLElement $node
   *   The xml node from the model.
   * @param array $featureMap
   *   The features as provided by Solr.
   *
   * @return array
   *   The convert xml node into an array.
   */
  private function parseSplits(\SimpleXMLElement $node, array $featureMap) {
    $splitData = [];
    if ($node->feature[0] !== NULL) {
      $feature = $featureMap[(int) $node->feature];
      if (!empty($feature)) {
        $splitData['feature'] = $feature;
      }
    }

    $threshold = trim((string) $node->threshold);
    if (!empty($threshold)) {
      $splitData['threshold'] = $threshold;
    }
    $splits = $node->xpath('split');

    foreach ($splits as $split) {
      $attributes = $split->attributes();
      $pos = trim(((string) $attributes['pos']));
      $splitData[$pos] = $this->parseSplits($split, $featureMap);
    }
    $output = trim((string) $node->output);
    if (!empty($output)) {
      $splitData['value'] = $output;
    }

    return $splitData;

  }

}
