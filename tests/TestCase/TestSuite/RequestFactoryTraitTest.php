<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\TestSuite;

use Cake\Http\ServerRequest;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\TestSuite\TestCase;
use Icings\Menu\TestSuite\RequestFactoryTrait;

class RequestFactoryTraitTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Router::scope('/', function (RouteBuilder $routes) {
            if (method_exists($routes, 'setRouteClass')) {
                $routes->setRouteClass(DashedRoute::class);
            } else {
                $routes->routeClass(DashedRoute::class);
            }

            $routes->connect('/:controller/:action');
        });
    }

    public function testCreateServerRequest()
    {
        /** @var RequestFactoryTrait $factory */
        $factory = $this->getMockForTrait(RequestFactoryTrait::class);

        $request = $factory::createRequest('/controller/action');
        $this->assertInstanceOf(ServerRequest::class, $request);
    }

    public function testCreateServerRequestWithQueryString()
    {
        /** @var RequestFactoryTrait $factory */
        $factory = $this->getMockForTrait(RequestFactoryTrait::class);

        $request = $factory::createRequest('/controller/action?query=value');
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals('/controller/action?query=value', $request->getRequestTarget());
        $this->assertEquals(['query' => 'value'], $request->getQueryParams());
    }
}
