<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Integration;

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Icings\Menu\Integration\RoutingExtension;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\MockObject\MockObject;

class RoutingExtensionTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Icings\Menu\Integration\RoutingExtension
     */
    public $RoutingExtension;

    public function setUp(): void
    {
        parent::setUp();
        $this->RoutingExtension = new RoutingExtension();

        Router::createRouteBuilder('/')
            ->scope('/', function (RouteBuilder $routes) {
                $routes->setRouteClass(DashedRoute::class);
                $routes->connect('/{controller}/{action}');
            });
    }

    public function tearDown(): void
    {
        unset($this->RoutingExtension);

        parent::tearDown();
    }

    public function testBuildOptionsDefaults(): void
    {
        $options = $this->RoutingExtension->buildOptions();
        $expected = [];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineUriOnly(): void
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

    public function testBuildOptionsDefineUriAsNamedRoute(): void
    {
        Router::createRouteBuilder('/')
            ->scope('/', function (RouteBuilder $routes) {
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

    public function testBuildOptionsDefineUriAsString(): void
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

    public function testBuildOptionsDefineRoutesOnly(): void
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

    public function testBuildOptionsDefineUriAndRoutes(): void
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

    public function testBuildOptionsDefineRoutesAsNamedRoute(): void
    {
        Router::createRouteBuilder('/')
            ->scope('/', function (RouteBuilder $routes) {
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

    public function testBuildOptionsDefineRoutesAsStrings(): void
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

    public function testBuildOptionsDefineNonConnectedRoutes(): void
    {
        Router::reload();
        Router::createRouteBuilder('/')
            ->scope('/', function (RouteBuilder $routes) {
                $routes->setRouteClass(DashedRoute::class);
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

    public function testBuildOptionsDoNotAddUriToRoutesDefineUriAndRoutes(): void
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

    public function testBuildOptionsDoNotAddUriToRoutesUriDefineUriOnly(): void
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

    public function testBuildOptionsExplicitlyDoNotIgnoreQueryString(): void
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                '?' => [
                    'query' => 'value',
                ],
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

    public function testBuildOptionsExplicitlyDoIgnoreQueryString(): void
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                '?' => [
                    'query' => 'value',
                ],
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

    public function testBuildOptionsIgnoreQueryStringOnUriWithoutQueryParameters(): void
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

    public function testBuildOptionsIgnoreQueryStringOnUriWithQueryParameters(): void
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                '?' => [
                    'query' => 'value',
                ],
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

    public function testBuildOptionsIgnoreQueryStringOnUriAndRoutesWithQueryParameters(): void
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller1',
                'action' => 'action',
                '?' => [
                    'query' => 'value',
                ],
            ],
            'routes' => [
                [
                    'controller' => 'Controller2',
                    'action' => 'action',
                    '?' => [
                        'query' => 'value',
                    ],
                ],
                [
                    'controller' => 'Controller3',
                    'action' => 'action',
                    '?' => [
                        'query' => 'value',
                    ],
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

    public function testBuildOptionsDoNotIgnoreQueryStringOnUriWithQueryParameters(): void
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller',
                'action' => 'action',
                '?' => [
                    'query' => 'value',
                ],
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

    public function testBuildOptionsDoNotIgnoreQueryStringOnUriAndRoutesWithQueryParameters(): void
    {
        $originalOptions = [
            'uri' => [
                'controller' => 'Controller1',
                'action' => 'action',
                '?' => [
                    'query' => 'value',
                ],
            ],
            'routes' => [
                [
                    'controller' => 'Controller2',
                    'action' => 'action',
                    '?' => [
                        'query' => 'value',
                    ],
                ],
                [
                    'controller' => 'Controller3',
                    'action' => 'action',
                    '?' => [
                        'query' => 'value',
                    ],
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

    public function testBuildItem(): void
    {
        /** @var FactoryInterface|MockObject $factory */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();

        $item = new MenuItem('item', $factory);
        $clone = clone $item;

        $this->RoutingExtension->buildItem($item, []);
        $this->assertEquals($item, $clone);
    }
}
