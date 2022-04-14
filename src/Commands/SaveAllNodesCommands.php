<?php

namespace Drupal\save_all_nodes\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;

/**
 * Save All Nodes Commands.
 *
 * @package Drupal\save_all_nodes\Commands
 */
class SaveAllNodesCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SaveAllNodesCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $loggerFactory,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct();
    $this->logger = $loggerFactory->get('save_all_nodes');
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Save all nodes.
   *
   * @param string $node_type
   *   The node type.
   *
   * @option node_type
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @command save-all-nodes page
   * @validate-module-enabled save_all_nodes
   */
  public function saveNodes(string $node_type = 'page') {

    $this->output()->writeln('Node type: ' . $node_type);
    $config = $this->configFactory->get('save_all_nodes.settings');

    // An array with lots of node IDs.
    $node_types = [$node_type];
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $nids = $query->condition('type', $node_types, 'IN')->execute();

    $this->output()->writeln('Total nodes found: ' . count($nids));

    // Chunk size.
    $chunk_size = 250;
    if (!empty($config->get('chunk_size'))) {
      $chunk_size = $config->get('chunk_size');
    }
    $this->output()->writeln('Chunk size: ' . $chunk_size);

    // Chop array into sub-arrays (chunks) of specified size.
    $chunks = array_chunk($nids, $chunk_size);
    $num_chunks = count($chunks);
    $this->output()->writeln('Total chunks to process: ' . $num_chunks);

    // Now save all nodes chunk by chunk.
    $operations = [];
    for ($i = 0; $i < $num_chunks; $i++) {
      $operations[] = [
        '\Drupal\save_all_nodes\Batch\SaveAllNodesBatch::batchOperation',
        [$chunks[$i]],
      ];
    }

    $batch = [
      'title' => $this->t('Saving node(s)'),
      'progress_message' => $this->t('Completed @current out of @total chunks.'),
      'finished' => '\Drupal\save_all_nodes\Batch\SaveAllNodesBatch::batchFinished',
      'operations' => $operations,
    ];

    batch_set($batch);
    drush_backend_batch_process();
  }

}
