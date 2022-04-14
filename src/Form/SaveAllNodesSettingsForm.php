<?php

namespace Drupal\save_all_nodes\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Save All Nodes settings form.
 *
 * @package Drupal\save_all_nodes\Form
 */
class SaveAllNodesSettingsForm extends FormBase {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * SaveAllNodesSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger,
    ConfigFactoryInterface $config_factory
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
    return 'save_all_nodes_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['path_auto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Path Auto/Alias updating on nodes.'),
      '#description' => $this->t('Enable path auto to regenerate alias on node when saved.'),
      '#default_value' => $this->config->get('path_auto'),
    ];

    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision on node save.'),
      '#description' => $this->t('Enable or disable new revision when node is saved.'),
      '#default_value' => $this->config->get('new_revision'),
    ];

    $form['translations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save translations on node save.'),
      '#description' => $this->t('Enable or disable to save translations when node is saved.'),
      '#default_value' => $this->config->get('translations'),
    ];

    $form['chunk_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Chunk size'),
      '#description' => $this->t('Number of nodes in a chunk to be processed per batch operation. 250 if left empty.'),
      '#default_value' => !empty($this->config->get('chunk_size')) ? $this->config->get('chunk_size') : 250,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
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
    $chunk_size = !empty($form_state->getValue('chunk_size')) ? (int) $form_state->getValue('chunk_size') : 250;

    $config = $this->configFactory->getEditable('save_all_nodes.settings');
    $config->set('path_auto', $form_state->getValue('path_auto'));
    $config->set('new_revision', $form_state->getValue('new_revision'));
    $config->set('translations', $form_state->getValue('translations'));
    $config->set('chunk_size', $chunk_size);
    $config->save();
  }

}
