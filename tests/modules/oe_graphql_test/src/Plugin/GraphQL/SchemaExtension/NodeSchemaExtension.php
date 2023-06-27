<?php

declare(strict_types=1);

namespace Drupal\oe_graphql_test\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Language\LanguageInterface;
use Drupal\graphql\GraphQL\Execution\ResolveContext;
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
    $this->resolveFields();
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
   * Add all field resolvers.
   */
  protected function resolveFields(): void {
    $this->registry->addFieldResolver('Query', 'content',
      $this->builder->compose(
        $this->builder->produce('route_load')
          ->map('path', $this->builder->fromArgument('path')),
        $this->builder->produce('route_entity')
          ->map('url', $this->builder->fromParent())
      )
    );
    $this->resolveContentInterfaceFields('Page');
  }

  /**
   * Resolve fields of the event interface.
   */
  protected function resolveContentInterfaceFields(string $type) {
    $this->addFieldResolverIfNotExists($type, 'id',
      $this->builder->produce('entity_uuid')
        ->map('entity', $this->builder->fromParent())
    );
    $this->addFieldResolverIfNotExists($type, 'path',
      $this->builder->callback(function (NodeInterface $node) {
        return $this->aliasManager->getAliasByPath('/node/' . $node->id(), $node->language()->getId());
      })
    );
    $this->addFieldResolverIfNotExists($type, 'title',
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
