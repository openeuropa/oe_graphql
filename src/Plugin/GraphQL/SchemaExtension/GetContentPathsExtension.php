<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql_core_schema\CoreSchemaExtensionInterface;

/**
 * Get content paths and their translations.
 *
 * @SchemaExtension(
 *   id = "oe_graphql_get_content_paths",
 *   name = "OpenEuropa: Get content paths",
 *   description = "Get content paths and their translations.",
 *   schema = "core_composable"
 * )
 */
class GetContentPathsExtension extends SdlSchemaExtensionPluginBase implements CoreSchemaExtensionInterface {

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
    foreach (['path', 'langcode'] as $field) {
      $registry->addFieldResolver('ContentPath', $field, $builder->callback(function ($data) use ($field) {
        return $data[$field];
      }));
    }

    $registry->addFieldResolver('Query', 'contentPaths',
      $builder->produce('oe_graphql_content_paths')
        ->map('type', $builder->fromArgument('type')),
    );
  }

}
