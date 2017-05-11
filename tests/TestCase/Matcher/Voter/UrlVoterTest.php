<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Matcher\Voter;

use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;
use Cake\TestSuite\TestCase;
use Icings\Menu\Matcher\Voter\UrlVoter;
use Icings\Menu\TestSuite\RequestFactoryTrait;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;

class UrlVoterTest extends TestCase
{
    use RequestFactoryTrait;

    public function setUp()
    {
        parent::setUp();

        Router::scope('/', function (RouteBuilder $routes) {
            $routes->extensions(['json']);
            $routes->routeClass(DashedRoute::class);

            $routes->connect('/:controller/:action');
        });
    }

    public function testBaseUrlCompatibility()
    {
        Configure::write('App.baseUrl', '/base');

        $request = $this->createRequest('/base/controller/action?action=value');
        $voter = new UrlVoter($request);

        $this->assertEquals('/base/controller/action?action=value', $voter->getUrl());
        $this->assertEquals('/base/controller/action', $voter->getUrlWithoutQuery());
    }

    public function testIgnoreVoter()
    {
        $request = $this->createRequest('/controller/action');

        /* @var $voter UrlVoter|\PHPUnit_Framework_MockObject_MockObject */
        $voter = $this
            ->getMockBuilder(UrlVoter::class)
            ->setConstructorArgs([$request])
            ->setMethods(['config'])
            ->getMock();
        $voter
            ->expects($this->never())
            ->method('config');

        $factory = $this
            ->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $item = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['item', $factory])
            ->setMethods(['getExtra'])
            ->getMock();
        $item
            ->expects($this->at(0))
            ->method('getExtra')
            ->with('urls')
            ->willReturn(null);
        $item
            ->expects($this->any())
            ->method('getExtra')
            ->with($this->logicalNot($this->equalTo('urlsWithoutQuery')));

        $this->assertNull($voter->matchItem($item));
    }

    /**
     * @return array
     */
    public function matchingDataProvider()
    {
        return [
            'Request without query, URL without query, matching path' => [
                '/controller/action',
                ['/controller/action'],
                true
            ],
            'Request without query, URL without query, non-matching path' => [
                '/controller/action',
                ['/other/action'],
                false
            ],
            'Request without query, URL with query, matching path, non-matching query' => [
                '/controller/action',
                ['/controller/action?query=value'],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true]
                ]
            ],
            'Request without query, URL with query, non-matching path, non-matching query' => [
                '/controller/action',
                ['/other/action?query=value'],
                false
            ],
            'Request with query, URL without query, matching path, non-matching query' => [
                '/controller/action?query=value',
                ['/controller/action'],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true]
                ]
            ],
            'Request with query, URL without query, non-matching path, non-matching query' => [
                '/controller/action?query=value',
                ['/other/action'],
                false
            ],
            'Request with query, URL with query, matching path, matching query' => [
                '/controller/action?query=value',
                ['/controller/action?query=value'],
                true
            ],
            'Request with query, URL with query, non-matching path, matching query' => [
                '/controller/action?query=value',
                ['/other/action?query=value'],
                false
            ],
            'Request with query, URL with query, matching path, non-matching query' => [
                '/controller/action?query=value',
                ['/controller/action?other=value'],
                [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => true],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => false],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => true]
                ]
            ],
            'Request with query, URL with query, non-matching path, non-matching query' => [
                '/controller/action?query=value',
                ['/other/action?other=value'],
                false
            ]
        ];
    }

    /**
     * @return array
     */
    public function expandedMatchingDataProvider()
    {
        $sets = $this->matchingDataProvider();
        $expanded = [];
        foreach ($sets as $name => $set) {
            if (is_bool($set[2])) {
                $set[2] = [
                    ['voterIgnore' => true, 'itemIgnore' => null, 'expected' => $set[2]],
                    ['voterIgnore' => true, 'itemIgnore' => true, 'expected' => $set[2]],
                    ['voterIgnore' => true, 'itemIgnore' => false, 'expected' => $set[2]],
                    ['voterIgnore' => false, 'itemIgnore' => null, 'expected' => $set[2]],
                    ['voterIgnore' => false, 'itemIgnore' => false, 'expected' => $set[2]],
                    ['voterIgnore' => false, 'itemIgnore' => true, 'expected' => $set[2]]
                ];
            }

            foreach ($set[2] as $index => $config) {
                $expanded[$name . ' (#' . $index . ')'] = [
                    $set[0],
                    $set[1],
                    $config['voterIgnore'],
                    $config['itemIgnore'],
                    $config['expected']
                ];
            }
        }

        return $expanded;
    }

    /**
     * @dataProvider expandedMatchingDataProvider
     *
     * @param string $requestUri The request URI to match against.
     * @param array $urls An array of URLs to match.
     * @param boolean $voterIgnore
     * @param boolean $itemIgnore
     * @param boolean $expected The expected assertion result.
     */
    public function testMatching($requestUri, $urls, $voterIgnore, $itemIgnore, $expected)
    {
        $request = $this->createRequest($requestUri);

        $urlsWithoutQuery = [];
        foreach ($urls as $url) {
            if (strpos($url, '?') !== false) {
                $url = explode('?', $url, 2)[0];
            }
            $urlsWithoutQuery[] = $url;
        }

        $voter = new UrlVoter($request, [
            'ignoreQueryString' => $voterIgnore
        ]);

        $factory = $this
            ->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $item = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['item', $factory])
            ->setMethods(['getExtra'])
            ->getMock();
        $item
            ->expects($this->at(0))
            ->method('getExtra')
            ->with('urls')
            ->willReturn([
                'original' => $urls,
                'withoutQuery' => $urlsWithoutQuery
            ]);
        $item
            ->expects($this->at(1))
            ->method('getExtra')
            ->with('ignoreQueryString')
            ->willReturn($itemIgnore);

        $this->assertEquals($expected, $voter->matchItem($item));
    }
}
