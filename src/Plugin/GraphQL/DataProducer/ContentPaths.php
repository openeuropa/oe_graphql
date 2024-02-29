<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get the content paths.
 *
 * @DataProducer(
 *   id = "oe_graphql_content_paths",
 *   name = @Translation("Content paths"),
 *   description = @Translation("Get content paths by content type"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Array of paths")
 *   ),
 *   consumes = {
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Content type"),
 *     )
 *   }
 * )
 */
class ContentPaths extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new self($configuration, $plugin_id, $plugin_definition);

    $plugin->entityTypeManager = $container->get('entity_type.manager');
    $plugin->renderer = $container->get('renderer');

    return $plugin;
  }

  /**
   * Build the list of paths.
   *
   * @param string $type
   *   The content type.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $cacheContext
   *   The caching context related to the current field.
   *
   * @return \GraphQL\Deferred
   *   The list of paths.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(string $type, FieldContext $cacheContext): Deferred {
    $storage = $this->entityTypeManager->getStorage('node');
    $entityType = $storage->getEntityType();
    $query = $storage->getQuery();

    // Ensure that access checking is performed on the query.
    $query->currentRevision()->accessCheck(TRUE);
    $query->condition('status', TRUE);
    $query->sort('created');
    $query->condition('type', [$type], 'IN');

    $cacheContext->addCacheTags($entityType->getListCacheTags());
    $cacheContext->addCacheContexts($entityType->getListCacheContexts());

    // Defer the query execution and path alias mapping.
    return new Deferred(function () use ($query, $cacheContext, $storage) {
      return array_map(function ($id) use ($cacheContext, $storage) {
        $node = $storage->load($id);
        $languages = $node->getTranslationLanguages();
        $context = new RenderContext();
        $path = $this->renderer->executeInRenderContext($context, function () use ($node) {
          return $node->toUrl()->toString();
        });
        $cacheContext->addCacheableDependency($node);
        // Make sure we get the entity processed path without Drupal base path.
        $base_path = base_path();
        if (strpos($path, $base_path) === 0) {
          $path = substr($path, strlen($base_path) - 1);
        }
        return [
          'path' => $path,
          'translations' => array_keys((array) $languages),
        ];
      }, $query->execute());
    });
  }

}
