<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Integration;

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\TestSuite\TestCase;
use Icings\Menu\Integration\RoutingExtension;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;

class RoutingExtensionTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Icings\Menu\Integration\RoutingExtension
     */
    public $RoutingExtension;

    public function setUp()
    {
        parent::setUp();
        $this->RoutingExtension = new RoutingExtension();

        Router::scope('/', function (RouteBuilder $routes) {
            if (method_exists($routes, 'setRouteClass')) {
                $routes->setRouteClass(DashedRoute::class);
            } else {
                $routes->routeClass(DashedRoute::class);
            }

            $routes->connect('/:controller/:action');
        });
    }

    public function tearDown()
    {
        unset($this->RoutingExtension);

        parent::tearDown();
    }

    public function testBuildOptionsDefaults()
    {
        $options = $this->RoutingExtension->buildOptions();
        $expected = [];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineUriOnly()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineUriAsNamedRoute()
    {
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->connect(
                '/named/route',
                [
                    'controller' => 'Named',
                    'action' => 'route',
                ],
                [
                    '_name' => 'RouteName',
                ]
            );
        });

        $originalOptions = [
            'uri' => [
                '_name' => 'RouteName',
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/named/route',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineUriAsString()
    {
        $originalOptions = [
            'uri' => '/controller/action',
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action',
            'extras' => [
                'routes' => [
                    '/controller/action',
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineRoutesOnly()
    {
        $originalOptions = [
            'routes' => [
                [
                    'controller' => 'ControllerName',
                    'action' => 'actionName',
                ],
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = $originalOptions;
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineUriAndRoutes()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller1',
                'action' => 'action',
            ],
            'routes' => [
                [
                    'controller' => 'Controller2',
                    'action' => 'action',
                ],
                [
                    'controller' => 'Controller3',
                    'action' => 'action',
                ],
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller1/action',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                    $originalOptions['routes'][0],
                    $originalOptions['routes'][1],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineRoutesAsNamedRoute()
    {
        Router::scope('/', function (RouteBuilder $routes) {
            $routes->connect(
                '/named/route',
                [
                    'controller' => 'Named',
                    'action' => 'route',
                ],
                [
                    '_name' => 'RouteName',
                ]
            );
        });

        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
            ],
            'routes' => [
                ['_name' => 'RouteName'],
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                    $originalOptions['routes'][0],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineRoutesAsStrings()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
            ],
            'routes' => [
                '/other/action',
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                    '/other/action',
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineNonConnectedRoutes()
    {
        Router::reload();
        Router::scope('/', function (RouteBuilder $routes) {
            if (method_exists($routes, 'setRouteClass')) {
                $routes->setRouteClass(DashedRoute::class);
            } else {
                $routes->routeClass(DashedRoute::class);
            }

            $routes->connect('/members/about', ['controller' => 'Members', 'action' => 'about']);
        });

        $originalOptions = [
            'uri' => [
                'controller' => 'Members',
                'action' => 'about',
            ],
            'routes' => [
                [
                    'controller' => 'Members',
                ],
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/members/about',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                    $originalOptions['routes'][0],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDoNotAddUriToRoutesDefineUriAndRoutes()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller1',
                'action' => 'action',
            ],
            'routes' => [
                [
                    'controller' => 'Controller2',
                    'action' => 'action',
                ],
                [
                    'controller' => 'Controller3',
                    'action' => 'action',
                ],
            ],
            'addUriToRoutes' => false,
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller1/action',
            'extras' => [
                'routes' => $originalOptions['routes'],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDoNotAddUriToRoutesUriDefineUriOnly()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
            ],
            'addUriToRoutes' => false,
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action',
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsExplicitlyDoNotIgnoreQueryString()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                'query' => 'value',
            ],
            'ignoreQueryString' => false,
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action?query=value',
            'extras' => [
                'ignoreQueryString' => false,
                'routes' => [
                    $originalOptions['uri'],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsExplicitlyDoIgnoreQueryString()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                'query' => 'value',
            ],
            'ignoreQueryString' => true,
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action?query=value',
            'extras' => [
                'ignoreQueryString' => true,
                'routes' => [
                    $originalOptions['uri'],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsIgnoreQueryStringOnUriWithoutQueryParameters()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
            ],
            'ignoreQueryString' => true,
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action',
            'extras' => [
                'ignoreQueryString' => true,
                'routes' => [
                    $originalOptions['uri'],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsIgnoreQueryStringOnUriWithQueryParameters()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                'query' => 'value',
            ],
            'ignoreQueryString' => true,
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action?query=value',
            'extras' => [
                'ignoreQueryString' => true,
                'routes' => [
                    $originalOptions['uri'],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsIgnoreQueryStringOnUriAndRoutesWithQueryParameters()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller1',
                'action' => 'action',
                'query' => 'value',
            ],
            'routes' => [
                [
                    'controller' => 'Controller2',
                    'action' => 'action',
                    'query' => 'value',
                ],
                [
                    'controller' => 'Controller3',
                    'action' => 'action',
                    'query' => 'value',
                ],
            ],
            'ignoreQueryString' => true,
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller1/action?query=value',
            'extras' => [
                'ignoreQueryString' => true,
                'routes' => [
                    $originalOptions['uri'],
                    $originalOptions['routes'][0],
                    $originalOptions['routes'][1],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDoNotIgnoreQueryStringOnUriWithQueryParameters()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                'query' => 'value',
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller/action?query=value',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDoNotIgnoreQueryStringOnUriAndRoutesWithQueryParameters()
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller1',
                'action' => 'action',
                'query' => 'value',
            ],
            'routes' => [
                [
                    'controller' => 'Controller2',
                    'action' => 'action',
                    'query' => 'value',
                ],
                [
                    'controller' => 'Controller3',
                    'action' => 'action',
                    'query' => 'value',
                ],
            ],
        ];
        $options = $this->RoutingExtension->buildOptions($originalOptions);
        $expected = [
            'uri' => '/controller1/action?query=value',
            'extras' => [
                'routes' => [
                    $originalOptions['uri'],
                    $originalOptions['routes'][0],
                    $originalOptions['routes'][1],
                ],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildItem()
    {
        $item = new MenuItem('item', $this->getMockBuilder(FactoryInterface::class)->getMock());
        $this->assertNull($this->RoutingExtension->buildItem($item, []));
    }
}
