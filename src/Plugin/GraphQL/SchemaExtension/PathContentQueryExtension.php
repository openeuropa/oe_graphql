<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql_core_schema\CoreSchemaExtensionInterface;

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
class PathContentQueryExtension extends SdlSchemaExtensionPluginBase implements CoreSchemaExtensionInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeDependencies() {
    return ['node'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $builder = new ResolverBuilder();
    foreach (['path', 'translations'] as $field) {
      $registry->addFieldResolver('ContentPath', $field, $builder->callback(function ($data) use ($field) {
        return $data[$field];
      }));
    }

    $registry->addFieldResolver('Query', 'contentPaths',
      $builder->produce('oe_graphql_content_paths')
        ->map('type', $builder->fromArgument('type')),
    );

    $registry->addFieldResolver('Query', 'content',
      $builder->compose(
        $builder->produce('route_load')
          ->map('path', $builder->fromArgument('path')),
        $builder->produce('oe_graphql_route_entity_revision')
          ->map('url', $builder->fromParent())
          ->map('revision_id', $builder->fromArgument('revision'))
          ->map('language', $builder->fromArgument('langcode'))
      )
    );
  }

}
