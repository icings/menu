<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Matcher\Voter;

use Cake\Core\Configure;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Icings\Menu\Matcher\Voter\FuzzyRouteVoter;
use Icings\Menu\TestSuite\RequestFactoryTrait;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\MockObject\MockObject;

class FuzzyRouteVoterTest extends TestCase
{
    use RequestFactoryTrait;

    public function setUp(): void
    {
        parent::setUp();

        Router::scope('/', function (RouteBuilder $routes) {
            $routes->setExtensions(['json']);
            $routes->setRouteClass(DashedRoute::class);

            $routes->connect('/named', [
                'controller' => 'Named',
                'action' => 'index',
            ], [
                '_name' => 'named',
            ]);

            $routes->connect('/named/:element', [
                'controller' => 'Named',
                'action' => 'index',
            ], [
                'pass' => ['element'],
                '_name' => 'namedWithElement',
            ]);

            $routes->connect('/:controller');
            $routes->connect('/:controller/:action');
            $routes->connect('/:controller/:action/:id', [], [
                'id' => Router::ID,
                'pass' => ['id'],
            ]);
            $routes->connect('/:controller/:action/:id/:slug', [], [
                'id' => Router::ID,
                'pass' => ['id', 'slug'],
            ]);

            $routes->connect('/special', [
                'controller' => 'Special',
                'action' => 'index',
                'specialKey' => [
                    'foo', 123, 'a' => 'a', 'b' => 'b',
                ],
            ]);

            $routes->prefix('PrefixName', function (RouteBuilder $routes) {
                $routes->connect('/:controller/:action');
            });

            $routes->plugin('PluginName', function (RouteBuilder $routes) {
                $routes->connect('/:controller/:action');
            });
        });
    }

    /**
     * @return array
     */
    public function paramsDataProvider()
    {
        $provider = [
            'Controller only request' => [
                '/controller',
                ['controller' => 'Controller', 'action' => 'index'],
            ],
            'Controller + action request' => [
                '/controller/action',
                ['controller' => 'Controller', 'action' => 'action'],
            ],
            'Request with passed element' => [
                '/controller/action/123',
                ['controller' => 'Controller', 'action' => 'action', 'id' => '123', '123'],
            ],
            'Request with extension' => [
                '/controller/action.json',
                ['controller' => 'Controller', 'action' => 'action', '_ext' => 'json'],
            ],
            'Request with query string' => [
                '/controller/action?query=value',
                ['controller' => 'Controller', 'action' => 'action', 'query' => 'value', '?' => ['query' => 'value']],
            ],
            'Request with numerical query string value' => [
                '/controller/action?query=123',
                ['controller' => 'Controller', 'action' => 'action', 'query' => '123', '?' => ['query' => '123']],
            ],
            'Request with float query string value' => [
                '/controller/action?query=12.3',
                ['controller' => 'Controller', 'action' => 'action', 'query' => '12.3', '?' => ['query' => '12.3']],
            ],
            'Prefix request' => [
                '/prefix-name/controller/action',
                ['controller' => 'Controller', 'action' => 'action', 'prefix' => 'prefix_name'],
            ],
            'Plugin request' => [
                '/plugin-name/controller/action',
                ['controller' => 'Controller', 'action' => 'action', 'plugin' => 'PluginName'],
            ],
            'Request route with custom defaults' => [
                '/special',
                ['controller' => 'Special', 'action' => 'index', 'specialKey' => ['foo', 123, 'a' => 'a', 'b' => 'b']],
            ],
        ];

        if (version_compare(Configure::version(), '3.5.11', '>=')) {
            $provider += [
                'Request named route' => [
                    '/named',
                    ['_name' => 'named', 'controller' => 'Named', 'action' => 'index'],
                ],
                'Request named route with elements' => [
                    '/named/element',
                    ['_name' => 'namedWithElement', 'controller' => 'Named', 'action' => 'index', 'element' => 'element', 'element'],
                ],
            ];
        } else {
            $provider += [
                'Request named route' => [
                    '/named',
                    ['controller' => 'Named', 'action' => 'index'],
                ],
            ];
        }

        return $provider;
    }

    /**
     * @dataProvider paramsDataProvider
     *
     * @param string $requestUri
     * @param array $expected
     */
    public function testGetParams($requestUri, array $expected)
    {
        $request = static::createRequest($requestUri);
        $voter = new FuzzyRouteVoter($request);

        $defaults = [
            '?' => [],
            '_ext' => null,
            '_host' => 'localhost',
            '_method' => 'GET',
            'action' => null,
            'controller' => null,
            'plugin' => null,
        ];

        $expected = Hash::merge($defaults, $expected);
        ksort($expected, \SORT_STRING);

        $actual = $voter->getParams();
        ksort($actual, \SORT_STRING);

        $this->assertSame($expected, $actual);
    }

    public function testGetParamsWithoutPass()
    {
        $request = static::createRequest('/controller/action');

        $params = $request->getAttribute('params');
        unset($params['pass']);
        $request = $request->withAttribute('params', $params);

        $voter = new FuzzyRouteVoter($request);

        $expected = [
            '?' => [],
            '_ext' => null,
            '_host' => 'localhost',
            '_method' => 'GET',
            'action' => 'action',
            'controller' => 'Controller',
            'plugin' => null,
        ];
        $actual = $voter->getParams();

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array
     */
    public function matchingDataProvider()
    {
        $provider = [
            'No URL arrays set' => [
                null,
                '/controller/action',
                null,
            ],

            'Exact matching URL array' => [
                [['controller' => 'Controller', 'action' => 'action']],
                '/controller/action',
                true,
            ],
            'Wrong action name does not match' => [
                [['controller' => 'Controller', 'action' => 'other']],
                '/controller/action',
                false,
            ],
            'Wrong controller name does not match' => [
                [['controller' => 'Other', 'action' => 'action']],
                '/controller/action',
                false,
            ],
            'Elements do not require exact order' => [
                [['action' => 'action', 'controller' => 'Controller']],
                '/controller/action',
                true,
            ],

            'Match configured extension' => [
                [['controller' => 'Controller', 'action' => 'action', '_ext' => 'json']],
                '/controller/action.json',
                true,
            ],
            'Wrong extension does not match' => [
                [['controller' => 'Controller', 'action' => 'action', '_ext' => 'xml']],
                '/controller/action.json',
                false,
            ],

            'Exact matching passed parameters' => [
                [['controller' => 'Controller', 'action' => 'action', '123', 'whatever']],
                '/controller/action/123/whatever',
                true,
            ],
            'Wrong passed parameter does not match' => [
                [['controller' => 'Controller', 'action' => 'action', '456']],
                '/controller/action/123',
                false,
            ],
            'Non existing passed parameters do not match' => [
                [['controller' => 'Controller', 'action' => 'action', 'whatever', '123']],
                '/controller/action/123',
                false,
            ],
            'Matching passed parameters requires exact order when using numerical indices' => [
                [['controller' => 'Controller', 'action' => 'action', 'whatever', '123']],
                '/controller/action/123/whatever',
                false,
            ],
            'Matching passed parameters partially requires exact order when using numerical indices' => [
                [['controller' => 'Controller', 'action' => 'action', 'whatever']],
                '/controller/action/123/whatever',
                false,
            ],
            'Matching passed parameters does not require exact order when using named indices' => [
                [['controller' => 'Controller', 'action' => 'action', 'slug' => 'whatever', 'id' => '123']],
                '/controller/action/123/whatever',
                true,
            ],
            'Matching passed parameters partially does not require exact order when using named indices' => [
                [['controller' => 'Controller', 'action' => 'action', 'slug' => 'whatever']],
                '/controller/action/123/whatever',
                true,
            ],
            'Matching passed parameters partially using numerical indices works' => [
                [['controller' => 'Controller', 'action' => 'action', '123']],
                '/controller/action/123/whatever',
                true,
            ],
            'Matching passed parameters partially using named indices works' => [
                [['controller' => 'Controller', 'action' => 'action', 'id' => '123']],
                '/controller/action/123/whatever',
                true,
            ],

            'Matching without prefix works' => [
                [['controller' => 'Controller', 'action' => 'action']],
                '/prefix-name/controller/action',
                true,
            ],
            'Exact prefix matching' => [
                [['controller' => 'Controller', 'action' => 'action', 'prefix' => 'prefix_name']],
                '/prefix-name/controller/action',
                true,
            ],
            'Wrong prefix does not match' => [
                [['controller' => 'Controller', 'action' => 'action', 'prefix' => 'other']],
                '/prefix-name/controller/action',
                false,
            ],
            'Null prefix does not match' => [
                [['controller' => 'Controller', 'action' => 'action', 'prefix' => null]],
                '/prefix-name/controller/action',
                false,
            ],

            'Matching without plugin works' => [
                [['controller' => 'Controller', 'action' => 'action']],
                '/plugin-name/controller/action',
                true,
            ],
            'Exact plugin matching' => [
                [['controller' => 'Controller', 'action' => 'action', 'plugin' => 'PluginName']],
                '/plugin-name/controller/action',
                true,
            ],
            'Wrong plugin does not match' => [
                [['controller' => 'Controller', 'action' => 'action', 'plugin' => 'OtherPlugin']],
                '/plugin-name/controller/action',
                false,
            ],
            'Null plugin does not match' => [
                [['controller' => 'Controller', 'action' => 'action', 'plugin' => null]],
                '/plugin-name/controller/action',
                false,
            ],

            'Matching named route without route name works' => [
                [['controller' => 'Named', 'action' => 'index']],
                '/named',
                true,
            ],
            'Matching named route with elements without route name works' => [
                [['controller' => 'Named', 'action' => 'index']],
                '/named/element',
                true,
            ],

            'Matching without custom defaults works' => [
                [['controller' => 'Special', 'action' => 'index']],
                '/special',
                true,
            ],
            'Exact custom defaults matching' => [
                [['controller' => 'Special', 'action' => 'index', 'specialKey' => ['foo', 123, 'a' => 'a', 'b' => 'b']]],
                '/special',
                true,
            ],
            'Custom default keys do not require exact order' => [
                [['specialKey' => ['foo', 123, 'a' => 'a', 'b' => 'b'], 'controller' => 'Special', 'action' => 'index']],
                '/special',
                true,
            ],
            'Custom default array values require exact order for numeric indices' => [
                [['controller' => 'Special', 'action' => 'index', 'specialKey' => [123, 'foo', 'a' => 'a', 'b' => 'b']]],
                '/special',
                false,
            ],
            'Custom default array values require exact order for named indices' => [
                [['controller' => 'Special', 'action' => 'index', 'specialKey' => ['foo', 123, 'b' => 'b', 'a' => 'a']]],
                '/special',
                false,
            ],

            'Exact host matching' => [
                [['controller' => 'Controller', 'action' => 'index', '_host' => 'localhost']],
                '/controller/index',
                true,
            ],
            'Wrong host does not match' => [
                [['controller' => 'Controller', 'action' => 'index', '_host' => 'other']],
                '/controller/index',
                false,
            ],
            'Null host does not match' => [
                [['controller' => 'Controller', 'action' => 'index', '_host' => null]],
                '/controller/index',
                false,
            ],

            'Exact method matching' => [
                [['controller' => 'Controller', 'action' => 'index', '_method' => 'GET']],
                '/controller/index',
                true,
            ],
            'Wrong method does not match' => [
                [['controller' => 'Controller', 'action' => 'index', '_method' => 'POST']],
                '/controller/index',
                false,
            ],
            'Null method does not match' => [
                [['controller' => 'Controller', 'action' => 'index', '_method' => null]],
                '/controller/index',
                false,
            ],

            'Fragments are being ignored' => [
                [['controller' => 'Controller', 'action' => 'action', '#' => 'fragment']],
                '/controller/action',
                true,
            ],
            'Special keys are being ignored' => [
                [[
                    'controller' => 'Controller',
                    'action' => 'action',
                    '_base' => 'base',
                    '_scheme' => 'https',
                    '_port' => 42,
                    '_full' => true,
                    '_ssl' => true,
                ]],
                '/controller/action',
                true,
            ],

            'Exact query arguments matching' => [
                [['controller' => 'Controller', 'action' => 'action', 'query' => 'value']],
                '/controller/action?query=value',
                true,
            ],
            'Matching integer query argument values works' => [
                [['controller' => 'Controller', 'action' => 'action', 'query' => 123]],
                '/controller/action?query=123',
                true,
            ],
            'Matching float query argument values works' => [
                [['controller' => 'Controller', 'action' => 'action', 'query' => 12.3]],
                '/controller/action?query=12.3',
                true,
            ],
            'Matching query arguments partially works' => [
                [['controller' => 'Controller', 'action' => 'action', 'query' => 'value']],
                '/controller/action?query=value&other=value',
                true,
            ],
            'Wrong query argument does not match' => [
                [['controller' => 'Controller', 'action' => 'action', 'query' => 'value']],
                '/controller/action?other=value',
                false,
            ],
            'Matching query arguments does does not require exact order' => [
                [['controller' => 'Controller', 'action' => 'action', 'query' => 'value', 'other' => 'value']],
                '/controller/action?other=value&query=value',
                true,
            ],
            'Matching query arguments partially does not require exact order' => [
                [['controller' => 'Controller', 'action' => 'action', 'query' => 'value']],
                '/controller/action?other=value&query=value',
                true,
            ],
            'Exact query arguments matching via special `?` key' => [
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 'value']]],
                '/controller/action?query=value',
                true,
            ],
            'Matching integer query argument values via special `?` key works' => [
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 123]]],
                '/controller/action?query=123',
                true,
            ],
            'Matching float query argument values via special `?` key works' => [
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 12.3]]],
                '/controller/action?query=12.3',
                true,
            ],
            'Matching query arguments partially via special `?` key works' => [
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 'value']]],
                '/controller/action?query=value&other=value',
                true,
            ],
            'Wrong query argument via special `?` key does not match' => [
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 'value']]],
                '/controller/action?other=value',
                false,
            ],
            'Matching query arguments via special `?` key does does not require exact order' => [
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 'value', 'other' => 'value']]],
                '/controller/action?other=value&query=value',
                true,
            ],
            'Matching query arguments partially via special `?` key does does not require exact order' => [
                [['controller' => 'Controller', 'action' => 'action', '?' => ['query' => 'value']]],
                '/controller/action?other=value&query=value',
                true,
            ],
        ];

        if (version_compare(Configure::version(), '3.5.11', '>=')) {
            $provider += [
                'Matching named route with route name only works' => [
                    [['_name' => 'named']],
                    '/named',
                    true,
                ],
                'Matching named route with elements does work' => [
                    [['_name' => 'namedWithElement', 'element' => 'element']],
                    '/named/element',
                    true,
                ],
                'Matching named route with elements partially does work' => [
                    [['_name' => 'namedWithElement']],
                    '/named/element',
                    true,
                ],
            ];
        }

        return $provider;
    }

    /**
     * @dataProvider matchingDataProvider
     *
     * @param array $url The URL array to test.
     * @param string $requestUri The request URI to test against.
     * @param bool|null $expected The matching result.
     */
    public function testMatching($url, $requestUri, $expected)
    {
        /** @var ItemInterface|MockObject $item */
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->once())
            ->method('getExtra')
            ->with('routes')
            ->will($this->returnValue($url));

        $request = static::createRequest($requestUri);
        $voter = new FuzzyRouteVoter($request);

        $this->assertEquals($expected, $voter->matchItem($item));
    }
}
