<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Icings\Menu\TestSuite\RequestFactoryTrait;
use PHPUnit\Framework\MockObject\MockObject;

class RequestFactoryTraitTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Router::scope('/', function (RouteBuilder $routes) {
            $routes->setRouteClass(DashedRoute::class);

            $routes->connect('/:controller/:action');
        });
    }

    public function testCreateServerRequest()
    {
        $this->skipIf((float)Configure::version() < 3.4);

        /** @var RequestFactoryTrait|MockObject $stub */
        $stub = $this->getMockForTrait(RequestFactoryTrait::class);
        $request = $stub::createRequest('/controller/action');

        $this->assertInstanceOf(ServerRequest::class, $request);
    }

    public function testCreateServerRequestWithQueryString()
    {
        $this->skipIf((float)Configure::version() < 3.4);

        /** @var RequestFactoryTrait|MockObject $stub */
        $stub = $this->getMockForTrait(RequestFactoryTrait::class);
        $request = $stub::createRequest('/controller/action?query=value');

        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals('/controller/action?query=value', $request->getRequestTarget());
        $this->assertEquals(['query' => 'value'], $request->getQueryParams());
    }
}
