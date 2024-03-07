<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_graphql\Functional;

use Drupal\graphql\Entity\Server;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\graphql\Traits\HttpRequestTrait;
use Drupal\user\Entity\Role;

/**
 * Test basic GraphQL query, to ensure that configuration works as intended.
 */
class QueryTest extends BrowserTestBase {

  use HttpRequestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'system',
    'graphql',
    'oe_graphql',
    'graphql_core_schema',
    'oe_graphql_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests query.
   */
  public function testQuery(): void {
    $values = [
      'type' => 'test_page',
      'title' => 'Test page',
    ];
    $node = Node::create($values);
    $node->save();

    $node->addTranslation('fr', ['title' => 'Test page FR']);
    $node->save();

    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), [
      'access content',
      'execute oe_default arbitrary graphql requests',
    ]);
    $this->server = Server::load('oe_default');
    $response = $this->query(<<<QUERY
query {
  content(path: "/node/1") {
      label
  }
}
QUERY
    );
    $this->assertEquals('{"data":{"content":{"label":"Test page"}}}', $response->getContent());
    $response = $this->query(<<<QUERY
query {
  content(path: "/node/1", langcode: "fr") {
      label
  }
}
QUERY
    );
    $this->assertEquals('{"data":{"content":{"label":"Test page FR"}}}', $response->getContent());

    // Create a new revision.
    $node->setTitle('Test page rev 2');
    $node->setNewRevision(TRUE);
    $node->save();

    // Assert that, with no revision parameter, we get the latest revision.
    $response = $this->query(<<<QUERY
query {
  content(path: "/node/1") {
      label
  }
}
QUERY
    );
    $this->assertEquals('{"data":{"content":{"label":"Test page rev 2"}}}', $response->getContent());

    // Assert that we get the specified revision.
    $response = $this->query(<<<QUERY
query {
  content(path: "/node/1", revision: 1) {
      label
  }
}
QUERY
    );
    $this->assertEquals('{"data":{"content":{"label":"Test page"}}}', $response->getContent());

    // Assert list of content paths.
    $response = $this->query(<<<QUERY
query {
  contentPaths(type: "test_page") {
    path
    langcode
  }
}
QUERY
    );
    $this->assertEquals('{"data":{"contentPaths":[[{"path":"\/node\/1","langcode":"en"},{"path":"\/node\/1","langcode":"fr"}]]}}', $response->getContent());
  }

}
