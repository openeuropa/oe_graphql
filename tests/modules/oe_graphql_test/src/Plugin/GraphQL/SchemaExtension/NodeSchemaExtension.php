<?php

declare(strict_types=1);

namespace Drupal\oe_graphql_test\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\oe_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionBase;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManager;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The schema extension for the youth hub content types.
 *
 * @SchemaExtension(
 *   id = "node",
 *   name = "Test content schema",
 *   description = "The schema extension for nodes.",
 *   schema = "oe_default"
 * )
 */
class NodeSchemaExtension extends SchemaExtensionBase {

  /**
   * Path alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected AliasManager $aliasManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->aliasManager = $container->get('path_alias.manager');

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->addTypeResolvers();

    // Resolve query.
    $this->registry->addFieldResolver('Query', 'content',
      $this->builder->compose(
        $this->builder->produce('route_load')
          ->map('path', $this->builder->fromArgument('path')),
        $this->builder->produce('route_entity')
          ->map('url', $this->builder->fromParent())
      )
    );

    // Resolve base fields.
    $this->resolveBaseFields('Page', 'page');
  }

  /**
   * Add resolvers for the interface types.
   */
  protected function addTypeResolvers(): void {
    foreach (['Page'] as $interface) {
      $this->registry->addTypeResolver($interface,
        self::resolveContentTypes(...)
      );
    }
  }

  /**
   * Resolves page types.
   *
   * @param mixed $value
   *   The current value.
   * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
   *   The resolve context.
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *   The resolve information.
   *
   * @return string
   *   Response type.
   *
   * @throws \Exception
   */
  protected static function resolveContentTypes($value, ResolveContext $context, ResolveInfo $info): string {
    if ($value instanceof NodeInterface) {
      return match($value->bundle()) {
        'page' => 'Page',
      };
    }
    throw new \Exception('Invalid page type.');
  }

}
