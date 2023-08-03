<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\DataProducerPluginManager;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\oe_graphql\GraphQL\Traits\ResolverHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class for schema extensions.
 */
abstract class SchemaExtensionBase extends SdlSchemaExtensionPluginBase {

  use ResolverHelperTrait;

  /**
   * The data producer plugin manager.
   *
   * @var \Drupal\graphql\Plugin\DataProducerPluginManager
   */
  protected DataProducerPluginManager $dataProducerManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->createResolverBuilder();
    $plugin->dataProducerManager = $container->get('plugin.manager.graphql.data_producer');
    $plugin->entityTypeManager = $container->get('entity_type.manager');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $this->registry = $registry;
  }

  /**
   * Add fields common to all entities.
   *
   * @param string $type
   *   The schema type name.
   * @param string $entityTypeId
   *   The entity type ID.
   */
  protected function resolveBaseFields(string $type, string $entityTypeId) {
    $this->addFieldResolverIfNotExists(
      $type,
      'uuid',
      $this->builder->produce('entity_uuid')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists(
      $type,
      'label',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );
  }

}
