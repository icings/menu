<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\TestSuite;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Network\Request;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\TestSuite\TestCase;
use Icings\Menu\TestSuite\RequestFactoryTrait;

class Stub
{
    use RequestFactoryTrait;
}

class RequestFactoryTraitTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Router::scope('/', function (RouteBuilder $routes) {
            $routes->routeClass(DashedRoute::class);
            $routes->connect('/:controller/:action');
        });
    }

    public function testCreateLegacyRequest()
    {
        Configure::write('Cake.version', '3.1.0');

        $request = Stub::createRequest('/controller/action');
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('/controller/action', $request->here());
    }

    public function testCreateLegacyRequestWithQueryString()
    {
        Configure::write('Cake.version', '3.1.0');

        $request = Stub::createRequest('/controller/action?query=value');
        $this->assertInstanceOf(Request::class, $request);
        $this->assertEquals('/controller/action?query=value', $request->here());
        $this->assertEquals(['query' => 'value'], $request->query);
    }

    public function testCreateServerRequest()
    {
        $this->skipIf((float)Configure::version() < 3.4);

        $request = Stub::createRequest('/controller/action');
        $this->assertInstanceOf(ServerRequest::class, $request);
    }

    public function testCreateServerRequestWithQueryString()
    {
        $this->skipIf((float)Configure::version() < 3.4);

        $request = Stub::createRequest('/controller/action?query=value');
        $this->assertInstanceOf(ServerRequest::class, $request);
        $this->assertEquals('/controller/action?query=value', $request->getRequestTarget());
        $this->assertEquals(['query' => 'value'], $request->getQueryParams());
    }
}
