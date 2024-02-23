<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\DataProducerPluginManager;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\node\NodeInterface;
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

  /**
   * Add fields common to all entities.
   *
   * @param string $type
   *   The schema type name.
   */
  protected function resolveContentInterfaceFields(string $type) {
    $this->addFieldResolverIfNotExists($type, 'uuid',
      $this->builder->produce('entity_uuid')
        ->map('entity', $this->builder->fromParent())
    );
    $this->addFieldResolverIfNotExists($type, 'id',
      $this->builder->produce('entity_id')
        ->map('entity', $this->builder->fromParent())
    );
    $this->addFieldResolverIfNotExists($type, 'revision',
      $this->builder->callback(function (NodeInterface $node) {
        return (int) $node->getRevisionId();
      })
    );
    $this->addFieldResolverIfNotExists($type, 'path',
      $this->builder->callback(function (NodeInterface $node) {
        return $this->aliasManager->getAliasByPath('/node/' . $node->id(), $node->language()->getId());
      })
    );
    $this->addFieldResolverIfNotExists($type, 'label',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );
    $this->addFieldResolverIfNotExists($type, 'created',
      $this->builder->produce('entity_created')
        ->map('entity', $this->builder->fromParent())
    );
    $this->addFieldResolverIfNotExists($type, 'changed',
      $this->builder->produce('entity_changed')
        ->map('entity', $this->builder->fromParent())
    );
    $this->addFieldResolverIfNotExists($type, 'lang',
      $this->builder->compose(
        $this->builder->produce('entity_language')
          ->map('entity', $this->builder->fromParent()),
        $this->builder->callback(function (LanguageInterface $language) {
          return $language->getId();
        })
      )
    );
  }

}
