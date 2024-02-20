<?php

declare(strict_types=1);

namespace Drupal\oe_graphql\GraphQL\Traits;

use Drupal\graphql\GraphQL\Resolver\ResolverInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;

/**
 * Helper functions for field resolvers.
 *
 * This trait is from the graphql integration of the thunder distribution.
 */
trait ResolverHelperTrait {

  /**
   * ResolverBuilder.
   *
   * @var \Drupal\graphql\GraphQL\ResolverBuilder
   */
  protected $builder;

  /**
   * ResolverRegistryInterface.
   *
   * @var \Drupal\graphql\GraphQL\ResolverRegistryInterface
   */
  protected $registry;

  /**
   * Add field resolver to registry, if it does not already exist.
   *
   * @param string $type
   *   The type name.
   * @param string $field
   *   The field name.
   * @param \Drupal\graphql\GraphQL\Resolver\ResolverInterface $resolver
   *   The field resolver.
   */
  protected function addFieldResolverIfNotExists(string $type, string $field, ResolverInterface $resolver) {
    if (!$this->registry->getFieldResolver($type, $field)) {
      $this->registry->addFieldResolver($type, $field, $resolver);
    }
  }

  /**
   * Create the ResolverBuilder.
   */
  protected function createResolverBuilder() {
    $this->builder = new ResolverBuilder();
  }

  /**
   * Produces an entity_reference field.
   *
   * @param string $field
   *   Name of the filed.
   * @param bool $multiple
   *   If there are multiple referenced fields.
   * @param \Drupal\graphql\GraphQL\Resolver\ResolverInterface|null $entity
   *   Entity to get the field property.
   *
   * @return \Drupal\graphql\GraphQL\Resolver\ResolverInterface
   *   The field data producer.
   */
  public function fromEntityReference(string $field, bool $multiple = TRUE, ResolverInterface $entity = NULL) {
    $reference = $this->builder->produce('entity_reference')
      ->map('field', $this->builder->fromValue($field))
      ->map('entity', $entity ?: $this->builder->fromParent());

    if ($multiple) {
      return $reference;
    }

    return $this->builder->compose(
      $reference,
      $this->builder->callback(function ($entities) {
        if (empty($entities)) {
          return NULL;
        }
        return reset($entities);
      })
    );
  }

  /**
   * Produces an entity_reference_revisions field.
   *
   * @param string $field
   *   Name of the filed.
   * @param bool $multiple
   *   If there are multiple referenced fields.
   * @param \Drupal\graphql\GraphQL\Resolver\ResolverInterface|null $entity
   *   Entity to get the field property.
   *
   * @return \Drupal\graphql\GraphQL\Resolver\ResolverInterface
   *   The field data producer.
   */
  public function fromEntityReferenceRevision(string $field, bool $multiple = TRUE, ResolverInterface $entity = NULL) {
    $reference = $this->builder->produce('entity_reference_revisions')
      ->map('field', $this->builder->fromValue($field))
      ->map('entity', $entity ?: $this->builder->fromParent());

    if ($multiple) {
      return $reference;
    }

    return $this->builder->compose(
      $reference,
      $this->builder->callback(function ($entities) {
        if (empty($entities)) {
          return NULL;
        }
        return reset($entities);
      })
    );
  }

  /**
   * Produces an entity_reference field.
   *
   * @param string $field
   *   Name of the filed.
   * @param string $entityType
   *   The entity type of the referencing entities.
   * @param string[] $bundles
   *   The bundles of the referencing entities.
   * @param bool $multiple
   *   If there are multiple entities or just the first.
   * @param \Drupal\graphql\GraphQL\Resolver\ResolverInterface|null $entity
   *   Entity to get the field property.
   *
   * @return \Drupal\graphql\GraphQL\Resolver\ResolverInterface
   *   The field data producer.
   */
  public function fromReverseEntityReference(string $field, string $entityType, array $bundles = [], bool $multiple = TRUE, ResolverInterface $entity = NULL) {

    $reference = $this->builder->produce('reverse_entity_reference')
      ->map('type', $this->builder->fromValue($entityType))
      ->map('field', $this->builder->fromValue($field))
      ->map('entity', $entity ?: $this->builder->fromParent());

    if (!empty($bundles)) {
      $reference = $reference->map('bundles', $this->builder->fromValue($bundles));
    }

    if ($multiple) {
      return $reference;
    }

    return $this->builder->compose(
      $reference,
      $this->builder->callback(function ($entities) {
        if (empty($entities)) {
          return NULL;
        }
        return reset($entities);
      })
    );
  }

  /**
   * Define callback field resolver for a type.
   *
   * @param string $type
   *   Type to add fields.
   * @param array $fields
   *   The fields.
   */
  public function addSimpleCallbackFields(string $type, array $fields) {
    foreach ($fields as $field) {
      $this->addFieldResolverIfNotExists($type, $field,
        $this->builder->callback(function ($arr) use ($field) {
          return $arr[$field];
        })
      );
    }
  }

  /**
   * Add callbacks for a type.
   *
   * @param string $type
   *   The graphql type.
   * @param array $callbacks
   *   The callbacks keyed by graphql field.
   */
  public function addTypedCallback(string $type, array $callbacks): void {
    foreach ($callbacks as $field => $callback) {
      $this->addFieldResolverIfNotExists($type, $field,
        $this->builder->callback($callback)
      );
    }
  }

}
