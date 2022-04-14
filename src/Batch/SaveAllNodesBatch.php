<?php

namespace Drupal\save_all_nodes\Batch;

use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;

/**
 * Save All Nodes batch.
 *
 * @package Drupal\save_all_nodes\Batch
 */
class SaveAllNodesBatch {

  /**
   * Save nodes.
   *
   * @param array $chunk
   *   An array of node IDs.
   * @param mixed $context
   *   A batch context.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function batchOperation(array $chunk, &$context) {
    $config = \Drupal::config('save_all_nodes.settings');

    // Flag to update path alias.
    $applyPathAuto = FALSE;
    if ($config && $config->get('path_auto')) {
      $applyPathAuto = TRUE;
    }

    // Flag for new revision.
    $applyRevisions = FALSE;
    if ($config && $config->get('new_revision')) {
      $applyRevisions = TRUE;
    }

    // Flag for saving translations.
    $saveTranslations = FALSE;
    if ($config && $config->get('translations')) {
      $saveTranslations = TRUE;
    }

    foreach ($chunk as $nid) {
      /** @var \Drupal\node\Entity\Node $node */
      if ($node = Node::load($nid)) {

        // Update alias if specified.
        if ($applyPathAuto) {
          $node->set("path", ["pathauto" => PathautoState::CREATE]);
        }

        // Create new revision or not if specified.
        $node->setNewRevision($applyRevisions);

        // Save default node.
        $node->save();

        // Check/save translations.
        if ($node->getTranslationLanguages(FALSE) && $saveTranslations) {
          foreach ($node->getTranslationLanguages(FALSE) as $langcode => $language) {
            if ($node->hasTranslation($langcode)) {
              $translatedNode = $node->getTranslation($langcode);
              // Update alias if specified.
              if ($applyPathAuto) {
                $translatedNode->set("path", ["pathauto" => PathautoState::CREATE]);
              }
              // Create new revision or not if specified.
              $translatedNode->setNewRevision($applyRevisions);
              $translatedNode->save();
            }
          }
        }

        // Save node id to results.
        $context['results'][] = $nid;
      }
    }
  }

  /**
   * Handle batch completion.
   *
   * @param bool $success
   *   TRUE if all batch API tasks were completed successfully.
   * @param array $results
   *   An array of saved node IDs.
   * @param array $operations
   *   A list of the operations that had not been completed.
   */
  public static function batchFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('Saved @count node(s).', [
        '@count' => count($results),
      ]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addError($message);
    }
  }

}
