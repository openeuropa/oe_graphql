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
 * Path content query extension.
 *
 * @SchemaExtension(
 *   id = "oe_graphql_path_content_query",
 *   name = "OpenEuropa: Path Content Query",
 *   description = "Query content by URL path, revision and language.",
 *   schema = "core_composable"
 * )
 */
class PathContentQueryExtension extends SdlSchemaExtensionPluginBase {

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
    // Resolve query.
    $this->registry->addFieldResolver('Query', 'content',
      $this->builder->compose(
        $this->builder->produce('route_load')
          ->map('path', $this->builder->fromArgument('path')),
        $this->builder->produce('oe_graphql_route_entity_revision')
          ->map('url', $this->builder->fromParent())
          ->map('revision_id', $this->builder->fromArgument('revision'))
          ->map('language', $this->builder->fromArgument('lang'))
      )
    );
  }

}
