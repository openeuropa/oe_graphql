<?php

declare(strict_types=1);

namespace Drupal\oe_graphql_test\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\node\NodeInterface;
use Drupal\oe_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionBase;
use Drupal\path_alias\AliasManager;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test schema extension.
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
    // Resolve base fields.
    $this->resolveBaseFields('Page', 'test_page');
  }

  /**
   * Add resolvers for the interface types.
   */
  protected function addTypeResolvers(): void {
    foreach (['Page'] as $interface) {
      $this->registry->addTypeResolver($interface,
        \Closure::fromCallable([self::class, 'resolveContentTypes'])
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
        'test_page' => 'Page',
      };
    }
    throw new \Exception('Invalid page type.');
  }

}
