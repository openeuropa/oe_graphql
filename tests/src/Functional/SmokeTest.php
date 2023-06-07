<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_graphql\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Smoke test for project scaffolding.
 */
class SmokeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'system',
    'oe_graphql',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer graphql configuration'], '', TRUE));
  }

  /**
   * Tests that the test site is reachable.
   */
  public function testSiteIsReachable(): void {
    $this->drupalGet('/admin/config/graphql');
    $this->assertSession()->pageTextContains('Servers');
  }

}
