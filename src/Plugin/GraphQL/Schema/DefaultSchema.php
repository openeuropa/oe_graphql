<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\oe_graphql\GraphQL\Traits\ResolverHelperTrait;

/**
 * Default schema plugin.
 *
 * @Schema(
 *   id = "oe_default",
 *   name = "Default schema"
 * )
 */
class DefaultSchema extends SdlSchemaPluginBase {

  use ResolverHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $this->registry = new ResolverRegistry();
    $this->createResolverBuilder();
    $this->resolveBaseTypes();

    return $this->registry;
  }

  /**
   * Resolve custom types, that are used in multiple places.
   */
  private function resolveBaseTypes() {
    $this->addSimpleCallbackFields('Schema', ['query']);
  }

}
