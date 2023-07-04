<?php

declare(strict_types = 1);

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

    $this->grantPermissions(Role::load(Role::ANONYMOUS_ID), [
      'access content',
      'execute oe_default arbitrary graphql requests',
    ]);
    $this->server = Server::load('oe_default');
    $response = $this->query(<<<QUERY
query {
  content(path: "node/1") {
    label
  }
}
QUERY
    );
    $this->assertEquals('{"data":{"content":{"label":"Test page"}}}', $response->getContent());
  }

}
