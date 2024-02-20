<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\Plugin\GraphQL\DataProducer\Routing;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads the entity of given revision associated with the current URL.
 *
 * @DataProducer(
 *   id = "oe_graphql_route_entity_revision",
 *   name = @Translation("Load given entity revision by URL"),
 *   description = @Translation("The entity belonging to the current url, on a given revision."),
 *   produces = @ContextDefinition("entity",
 *     label = @Translation("Entity")
 *   ),
 *   consumes = {
 *     "url" = @ContextDefinition("any",
 *       label = @Translation("The URL")
 *     ),
 *     "revision_id" = @ContextDefinition("integer",
 *       label = @Translation("Revision ID"),
 *       required = FALSE
 *     ),
 *     "language" = @ContextDefinition("string",
 *       label = @Translation("Language"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class RouteEntityRevision extends DataProducerPluginBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * The entity buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected $entityBuffer;

  /**
   * The entity revision buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer
   */
  protected EntityRevisionBuffer $entityRevisionBuffer;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new self(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('entity_type.manager'),
      $container->get('graphql.buffer.entity'),
      $container->get('graphql.buffer.entity_revision')
    );
  }

  /**
   * RouteEntityRevision constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The language manager service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $entityBuffer
   *   The entity buffer service.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityRevisionBuffer $entityRevisionBuffer
   *   The entity revision buffer service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    EntityTypeManagerInterface $entityTypeManager,
    EntityBuffer $entityBuffer,
    EntityRevisionBuffer $entityRevisionBuffer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->entityBuffer = $entityBuffer;
    $this->entityRevisionBuffer = $entityRevisionBuffer;
  }

  /**
   * Resolver.
   *
   * @param \Drupal\Core\Url|mixed $url
   *   The URL to get the route entity from.
   * @param int|null $revisionId
   *   Entity revision ID.
   * @param string|null $language
   *   The language code to get a translation of the entity.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The GraphQL field context.
   */
  public function resolve($url, ?int $revisionId, ?string $language, FieldContext $context): ?Deferred {
    if ($url instanceof Url) {
      [, $type] = explode('.', $url->getRouteName());
      $parameters = $url->getRouteParameters();
      $id = $parameters[$type];
      if (isset($revisionId)) {
        $resolver = $this->entityRevisionBuffer->add($type, $revisionId);
      }
      else {
        $resolver = $this->entityBuffer->add($type, $id);
      }

      return new Deferred(function () use ($type, $resolver, $context, $language) {
        if (!$entity = $resolver()) {
          // If there is no entity with this id, add the list cache tags so that
          // the cache entry is purged whenever a new entity of this type is
          // saved.
          $type = $this->entityTypeManager->getDefinition($type);
          /** @var \Drupal\Core\Entity\EntityTypeInterface $type */
          $tags = $type->getListCacheTags();
          $context->addCacheTags($tags)->addCacheTags(['4xx-response']);
          return NULL;
        }

        // Get the correct translation.
        if (isset($language) && $language != $entity->language()->getId() && $entity instanceof TranslatableInterface) {
          $entity = $entity->getTranslation($language);
          $entity->addCacheContexts(["static:language:{$language}"]);
        }

        $access = $entity->access('view', NULL, TRUE);
        $context->addCacheableDependency($access);
        if ($access->isAllowed()) {
          return $entity;
        }
        return NULL;
      });
    }
    return NULL;
  }

}
