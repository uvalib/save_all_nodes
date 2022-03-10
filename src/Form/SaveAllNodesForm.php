<?php

namespace Drupal\save_all_nodes\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Save All Nodes form.
 *
 * @package Drupal\save_all_nodes\Form
 */
class SaveAllNodesForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * SaveAllNodesForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface         $messenger,
    ConfigFactoryInterface     $config_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->configFactory = $config_factory;
    $this->config = $config_factory->get('save_all_nodes.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'save_all_nodes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node_types = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();

    $options = [];
    foreach ($node_types as $node_type) {
      $options[$node_type->id()] = $node_type->label();
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('If none selected, all nodes of all types will be saved.'),
      '#options' => $options,
    ];

    $form['available_node_types'] = [
      '#type' => 'hidden',
      '#value' => $options,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save now'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $available_node_types = $form_state->getValue('available_node_types');
    $selected_node_types = $form_state->getValue('node_types');
    $node_types = !empty(array_filter($selected_node_types)) ? array_filter(array_values($selected_node_types)) : array_keys($available_node_types);

    $chunk_size = 250;
    $config = $this->configFactory->getEditable('save_all_nodes.settings');
    if ($config && !empty($config->get('chunk_size'))) {
      $chunk_size = $config->get('chunk_size');
    }

    $this->setBatch($node_types, $chunk_size);
  }

  /**
   * Adds a new batch.
   *
   * @param array $node_types
   *   An array of node type machine names.
   * @param int $chunk_size
   *   Number of nodes to be processed per batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setBatch(array $node_types, int $chunk_size) {
    // An array with lots of node IDs.
    $nids = $this->getNodeIds($node_types);

    // Chop array into sub-arrays (chunks) of specified size.
    $chunks = array_chunk($nids, $chunk_size);
    $num_chunks = count($chunks);

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
  }

  /**
   * Get an array node IDs.
   *
   * @param array $node_types
   *   An array of node type machine names.
   *
   * @return array|int
   *   An array of node IDs at best.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNodeIds(array $node_types) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    return $query->condition('type', $node_types, 'IN')->execute();
  }

}
