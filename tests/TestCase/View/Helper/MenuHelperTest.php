<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Icings\Menu\Matcher\Matcher;
use Icings\Menu\Matcher\Voter\FuzzyRouteVoter;
use Icings\Menu\Matcher\Voter\UrlVoter;
use Icings\Menu\MenuFactory;
use Icings\Menu\MenuFactoryInterface;
use Icings\Menu\View\Helper\MenuHelper;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Knp\Menu\MenuItem;
use Knp\Menu\Renderer\RendererInterface;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\MockObject\MockObject;

class MenuHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Icings\Menu\View\Helper\MenuHelper
     */
    public $Menu;

    /**
     * @var \Cake\View\View
     */
    public $View;

    public function setUp(): void
    {
        parent::setUp();

        $this->View = new View();
        $this->Menu = new MenuHelper($this->View);
    }

    public function tearDown(): void
    {
        unset($this->Menu);
        unset($this->View);

        parent::tearDown();
    }

    // -----------------------------------------------------------------------------------------------------------------
    //region construct()
    // -----------------------------------------------------------------------------------------------------------------

    public function testConstructDefaults(): void
    {
        $helper = new MenuHelper($this->View);

        $factory = $helper->getMenuFactory();
        $this->assertInstanceOf(MenuFactory::class, $factory);

        $expected = [
            'matching' => MenuHelper::MATCH_URL,
            'matcher' => null,
            'voters' => null,
            'renderer' => null,
        ];
        $this->assertEquals($expected, $helper->getConfig());
    }

    public function testConstructConfiguration(): void
    {
        $config = [
            'matching' => MenuHelper::MATCH_FUZZY_ROUTE,
            'matcher' => 'matcher',
            'voters' => 'voters',
            'renderer' => 'renderer',
        ];
        $helper = new MenuHelper($this->View, $config);

        $this->assertEquals($config, $helper->getConfig());
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region create()
    // -----------------------------------------------------------------------------------------------------------------

    public function testCreateInvalidNameArgumentType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches(
            '/^Argument 1 passed to Icings\\\\Menu\\\\View\\\\Helper\\\\MenuHelper::create\(\) must be of the type string, (int|integer) given/'
        );

        $this->Menu->create(123);
    }

    public function testCreateInvalidNameArgumentContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `$name` argument must not be empty.');

        $this->Menu->create('');
    }

    public function testCreateMenuReceivesOnlyMenuOptions(): void
    {
        $menuOptions = [
            'templates' => [],
            'templateVars' => [],
            'menuAttributes' => [],
        ];
        $nonMenuOptions = [
            'option' => 'value',
        ];

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        /** @var MenuFactoryInterface|MockObject $factory */
        $factory = $this
            ->getMockBuilder(MenuFactoryInterface::class)
            ->getMock();
        $factory
            ->expects($this->once())
            ->method('createItem')
            ->with('name', $menuOptions)
            ->willReturn($menu);

        $this->Menu->setMenuFactory($factory);

        $options = $menuOptions + $nonMenuOptions;
        $this->Menu->create('name', $options);
    }

    public function testCreateNonMenuItemOptionsAreRendererOptions(): void
    {
        $menuItemOptions = [
            'templates' => [],
            'templateVars' => [],
            'menuAttributes' => [],
        ];
        $nonRendererOptions = [
            'matching' => 'matching',
            'matcher' => 'matcher',
            'voters' => 'voters',
            'renderer' => 'renderer',
        ];
        $rendererOptions = [
            'currentClass' => 'current',
        ];

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        /** @var MenuFactoryInterface|MockObject $factory*/
        $factory = $this
            ->getMockBuilder(MenuFactoryInterface::class)
            ->getMock();
        $factory
            ->expects($this->once())
            ->method('createItem')
            ->with('name', $menuItemOptions)
            ->willReturn($menu);

        /** @var MenuHelper|MockObject $helper*/
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods(['_addMenu'])
            ->getMock();
        $helper
            ->setMenuFactory($factory);
        $helper
            ->expects($this->at(0))
            ->method('_addMenu')
            ->with($menu, $rendererOptions);

        $options = $menuItemOptions + $nonRendererOptions + $rendererOptions;
        $helper->create('name', $options);
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region render() menu
    // -----------------------------------------------------------------------------------------------------------------

    public function testRenderLastCreatedMenu(): void
    {
        /** @var MenuFactoryInterface|MockObject $factory*/
        $factory = $this
            ->getMockBuilder(MenuFactoryInterface::class)
            ->getMock();

        $otherMenu = new MenuItem('other', $factory);
        $mainMenu = new MenuItem('main', $factory);

        $factory
            ->expects($this->exactly(2))
            ->method('createItem')
            ->willReturnOnConsecutiveCalls($otherMenu, $mainMenu);

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($mainMenu))
            ->willReturn('rendered');

        $this->Menu->setConfig([
            'renderer' => $renderer,
        ]);
        $this->Menu->setMenuFactory($factory);

        $this->Menu->create('other');
        $this->Menu->create('main');
        $this->Menu->render();
    }

    public function testRenderLastCreatedMenuNoMenuHasBeenCreated(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No menu has been created.');

        $this->Menu->render();
    }

    public function testRenderNamedMenu(): void
    {
        /** @var MenuFactoryInterface|MockObject $factory*/
        $factory = $this
            ->getMockBuilder(MenuFactoryInterface::class)
            ->getMock();

        $otherMenu = new MenuItem('other', $factory);
        $mainMenu = new MenuItem('main', $factory);

        $factory
            ->expects($this->exactly(2))
            ->method('createItem')
            ->willReturnOnConsecutiveCalls($otherMenu, $mainMenu);

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($mainMenu))
            ->willReturn('rendered');

        $this->Menu->setConfig([
            'renderer' => $renderer,
        ]);
        $this->Menu->setMenuFactory($factory);

        $this->Menu->create('other');
        $this->Menu->create('main');
        $this->Menu->render('main');
    }

    public function testRenderNamedMenuDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The menu with the name `non-existent` does not exist.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        /** @var MenuFactoryInterface|MockObject $factory*/
        $factory = $this
            ->getMockBuilder(MenuFactoryInterface::class)
            ->getMock();
        $factory
            ->expects($this->exactly(2))
            ->method('createItem')
            ->willReturnOnConsecutiveCalls($menu, $menu);

        $this->Menu->setMenuFactory($factory);
        $this->Menu->create('main');
        $this->Menu->create('other');
        $this->Menu->render('non-existent');
    }

    public function testRenderMenuObject(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($menu), $this->isType('array'))
            ->willReturn('rendered');

        $this->Menu->setConfig([
            'renderer' => $renderer,
        ]);

        $this->Menu->render($menu);
    }

    public function testRenderInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `$menu` argument must be either a `Knp\Menu\ItemInterface` implementation, the name of a menu, or an array, `integer` given.');

        $this->Menu->render(123);
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region render() options
    // -----------------------------------------------------------------------------------------------------------------

    public function testRenderDefaultOptions(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->identicalTo($voter));

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->setMethods(['render'])
            ->getMock();
        $renderer
            ->expects($this->at(0))
            ->method('render')
            ->with($this->identicalTo($menu))
            ->willReturn('rendered');

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
                '_createDefaultRenderer',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);
        $helper
            ->expects($this->at(1))
            ->method('_createDefaultVoters')
            ->with(MenuHelper::MATCH_URL)
            ->willReturn([$voter]);
        $helper
            ->expects($this->at(2))
            ->method('_createDefaultRenderer')
            ->with($this->identicalTo($matcher))
            ->willReturn($renderer);

        $helper->render($menu);
    }

    public function testRenderRendererOnlyReceivesRendererOptions(): void
    {
        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();

        $menuOptions = [
            'menuAttributes' => [],
            'matching' => 'matching',
            'matcher' => $matcher,
            'voters' => [$voter],
        ];
        $rendererOptions = [
            'templates' => [],
            'templateVars' => [],
            'option' => 'value',
        ];

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($menu), $rendererOptions)
            ->willReturn('rendered');

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultRenderer',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultRenderer')
            ->with($this->identicalTo($matcher))
            ->willReturn($renderer);

        $options = $menuOptions + $rendererOptions;
        $helper->render($menu, $options);
    }

    public function renderMergeWithHelperAndCreateOptionsTestSetup(): array
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $voter1 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter2 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter3 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher1 = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher1
            ->expects($this->never())
            ->method('addVoter');

        $matcher2 = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher2
            ->expects($this->never())
            ->method('addVoter');

        $renderer1 = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer1
            ->expects($this->never())
            ->method('render');

        $renderer2 = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer2
            ->expects($this->never())
            ->method('render');

        $renderer3 = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer3
            ->expects($this->once())
            ->method('render')
            ->with($this->identicalTo($menu), [
                'nested' => [
                    'option1' => 'helper value',
                    'option2' => 'create value',
                    'option3' => 'create value',
                    'option4' => 'render value',
                ],
                'nonNested1' => 'helper value',
                'nonNested2' => 'create value',
                'nonNested3' => 'create value',
                'nonNested4' => 'render value',
            ])
            ->willReturn('rendered');

        /** @var MenuFactoryInterface|MockObject $factory*/
        $factory = $this
            ->getMockBuilder(MenuFactoryInterface::class)
            ->getMock();
        $factory
            ->expects($this->once())
            ->method('createItem')
            ->with('name', [])
            ->willReturn($menu);

        $helperOptions = [
            'voters' => [$voter1],
            'renderer' => $renderer1,
            'nested' => [
                'option1' => 'helper value',
                'option2' => 'helper value',
            ],
            'nonNested1' => 'helper value',
            'nonNested2' => 'helper value',
        ];

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
                '_createDefaultRenderer',
            ])
            ->getMock();
        $helper
            ->setConfig($helperOptions);
        $helper
            ->setMenuFactory($factory);
        $helper
            ->expects($this->never())
            ->method('_createDefaultMatcher');
        $helper
            ->expects($this->never())
            ->method('_createDefaultVoters');
        $helper
            ->expects($this->never())
            ->method('_createDefaultRenderer');

        $createOptions = [
            'matcher' => $matcher1,
            'voters' => [$voter2],
            'renderer' => $renderer2,
            'nested' => [
                'option2' => 'create value',
                'option3' => 'create value',
            ],
            'nonNested2' => 'create value',
            'nonNested3' => 'create value',
        ];
        $menu = $helper->create('name', $createOptions);

        $renderOptions = [
            'matcher' => $matcher2,
            'voters' => [$voter3],
            'renderer' => $renderer3,
            'nested' => [
                'option3' => 'render value',
                'option4' => 'render value',
            ],
            'nonNested3' => 'render value',
            'nonNested4' => 'render value',
        ];

        return compact('helper', 'menu', 'renderOptions');
    }

    public function testRenderMergeWithHelperAndCreateOptionsNoMenuArgument(): void
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render(null, $test['renderOptions']);
    }

    public function testRenderMergeWithHelperAndCreateOptionsRenderOptionsViaMenuArgument(): void
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render($test['renderOptions']);
    }

    public function testRenderMergeWithHelperAndCreateOptionsNamedMenu(): void
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render($test['menu']->getName(), $test['renderOptions']);
    }

    public function testRenderMergeWithHelperAndCreateOptionsMenuInstance(): void
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render($test['menu'], $test['renderOptions']);
    }

    public function testRenderDefaultMatchingOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->callback(function ($argument) {
                return ($argument instanceof UrlVoter) &&
                    $argument->getConfig('ignoreQueryString') === true;
            }));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);

        $helper->render($menu);
    }

    public function testRenderUrlWithQueryStringMatchingOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->callback(function ($argument) {
                return ($argument instanceof UrlVoter) &&
                    $argument->getConfig('ignoreQueryString') === false;
            }));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);

        $helper->render($menu, [
            'matching' => MenuHelper::MATCH_URL_WITH_QUERY_STRING,
        ]);
    }

    public function testRenderFuzzyRouteMatchingOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->isInstanceOf(FuzzyRouteVoter::class));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);

        $helper->render($menu, [
            'matching' => MenuHelper::MATCH_FUZZY_ROUTE,
        ]);
    }

    public function testRenderInvalidMatchingOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `matching` option must be one of the `Icings\Menu\View\Helper\MenuHelper::MATCH_*` constant values, `\'invalid\'` given.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'matching' => 'invalid',
        ]);
    }

    public function testRenderMatcherOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->identicalTo($voter));

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer
            ->expects($this->at(0))
            ->method('render')
            ->with($this->identicalTo($menu))
            ->willReturn('rendered');

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
                '_createDefaultRenderer',
            ])
            ->getMock();
        $helper
            ->expects($this->never())
            ->method('_createDefaultMatcher');
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultVoters')
            ->with(MenuHelper::MATCH_URL)
            ->willReturn([$voter]);
        $helper
            ->expects($this->at(1))
            ->method('_createDefaultRenderer')
            ->with($this->identicalTo($matcher))
            ->willReturn($renderer);

        $helper->render($menu, [
            'matcher' => $matcher,
        ]);
    }

    public function testRenderInvalidMatcherOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `matcher` option must be a `Icings\Menu\Matcher\MatcherInterface` implementation, `string` given.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'matcher' => 'invalid',
        ]);
    }

    public function testRenderVotersOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->identicalTo($voter));

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer
            ->expects($this->at(0))
            ->method('render')
            ->with($this->identicalTo($menu))
            ->willReturn('rendered');

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
                '_createDefaultRenderer',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);
        $helper
            ->expects($this->never())
            ->method('_createDefaultVoters');
        $helper
            ->expects($this->at(1))
            ->method('_createDefaultRenderer')
            ->with($this->identicalTo($matcher))
            ->willReturn($renderer);

        $helper->render($menu, [
            'voters' => [$voter],
        ]);
    }

    public function testRenderInvalidVotersOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `voters` option must be an array, `string` given.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'voters' => 'invalid',
        ]);
    }

    public function testRenderInvalidVotersOptionArray(): void
    {
        $this->skipIf(PHP_MAJOR_VERSION < 7);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches(
            '/^Argument 1 .+? must (be an instance of|implement interface) Knp\\\\Menu\\\\Matcher\\\\Voter\\\\VoterInterface, string given/'
        );

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'voters' => ['invalid'],
        ]);
    }

    public function testRenderRendererOptionWithObject(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $renderer = $this
            ->getMockBuilder(RendererInterface::class)
            ->getMock();
        $renderer
            ->expects($this->at(0))
            ->method('render')
            ->with($this->identicalTo($menu))
            ->willReturn('rendered');

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
                '_createDefaultRenderer',
            ])
            ->getMock();
        $helper
            ->expects($this->never())
            ->method('_createDefaultMatcher');
        $helper
            ->expects($this->never())
            ->method('_createDefaultVoters');
        $helper
            ->expects($this->never())
            ->method('_createDefaultRenderer');

        $helper->render($menu, [
            'renderer' => $renderer,
        ]);
    }

    public function testRenderInvalidRendererOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `renderer` option must be a `Knp\Menu\Renderer\RendererInterface` implementation, `string` given.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'renderer' => 'invalid',
        ]);
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region render() output
    // -----------------------------------------------------------------------------------------------------------------

    public function testRenderOutput(): void
    {
        $menu = $this->Menu->create('menu');
        $menu->addChild('Home', ['uri' => '/uri']);

        $expected = '<ul><li><a href="/uri">Home</a></li></ul>';
        $this->assertEquals($expected, $this->Menu->render());
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region extractPath() item
    // -----------------------------------------------------------------------------------------------------------------

    public function testExtractPathFromExternalNode(): void
    {
        $menu = $this->Menu->create('menu');

        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild']);

        $path = $this->Menu->extractPath($menu['Parent']['Child']['Grandchild']);

        $this->assertEquals(
            ['Parent', 'Child', 'Grandchild'],
            collection($path)
                ->extract(function ($node) {
                    return $node->getLabel();
                })
                ->toArray()
        );
    }

    public function testExtractPathFromInternalNode(): void
    {
        $menu = $this->Menu->create('menu');

        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild']);

        $path = $this->Menu->extractPath($menu['Parent']['Child']);

        $this->assertEquals(
            ['Parent', 'Child'],
            collection($path)
                ->extract(function ($node) {
                    return $node->getLabel();
                })
                ->toArray()
        );
    }

    public function testExtractPathNoParentItemsPresent(): void
    {
        $menu = $this->Menu->create('menu');
        $menu->addChild('Item', ['uri' => '/item']);

        $path = $this->Menu->extractPath($menu['Item']);

        $this->assertEquals(
            ['Item'],
            collection($path)
                ->extract(function ($node) {
                    return $node->getLabel();
                })
                ->toArray()
        );
    }

    public function testExtractPathReturnsClones(): void
    {
        $menu = $this->Menu->create('menu');

        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild']);

        $path = $this->Menu->extractPath($menu['Parent']['Child']['Grandchild'], [
            'includeRoot' => true,
        ]);

        $i = 0;
        $this->assertEquals($path[$i]->getLabel(), $menu->getLabel());
        $this->assertNotSame($path[$i], $menu);
        $this->assertSame($path[$i]->getExtra('original'), $menu);

        $i++;
        $this->assertEquals($path[$i]->getLabel(), $menu['Parent']->getLabel());
        $this->assertNotSame($path[$i], $menu['Parent']);
        $this->assertSame($path[$i]->getExtra('original'), $menu['Parent']);

        $i++;
        $this->assertEquals($path[$i]->getLabel(), $menu['Parent']['Child']->getLabel());
        $this->assertNotSame($path[$i], $menu['Parent']['Child']);
        $this->assertSame($path[$i]->getExtra('original'), $menu['Parent']['Child']);

        $i++;
        $this->assertEquals($path[$i]->getLabel(), $menu['Parent']['Child']['Grandchild']->getLabel());
        $this->assertNotSame($path[$i], $menu['Parent']['Child']['Grandchild']);
        $this->assertSame($path[$i]->getExtra('original'), $menu['Parent']['Child']['Grandchild']);
    }

    public function testExtractPathInvalidMenuType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches(
            '/^Argument 1 passed to Icings\\\\Menu\\\\View\\\\Helper\\\\MenuHelper::extractPath\(\) must (be an instance of|implement interface) Knp\\\\Menu\\\\ItemInterface, (int|integer) given/'
        );

        $this->Menu->extractPath(123);
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region extractPath() options
    // -----------------------------------------------------------------------------------------------------------------

    public function testExtractPathIncludeRootItem(): void
    {
        $menu = $this->Menu->create('menu');

        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild']);

        $path = $this->Menu->extractPath($menu['Parent']['Child']['Grandchild'], [
            'includeRoot' => true,
        ]);

        $this->assertEquals(
            ['menu', 'Parent', 'Child', 'Grandchild'],
            collection($path)
                ->extract(function ($node) {
                    return $node->getLabel();
                })
                ->toArray()
        );
    }

    public function testExtractPathNoParentItemsPresentIncludeRootItem(): void
    {
        $menu = $this->Menu->create('menu');

        $path = $this->Menu->extractPath($menu, [
            'includeRoot' => true,
        ]);

        $this->assertEquals(
            ['menu'],
            collection($path)
                ->extract(function ($node) {
                    return $node->getLabel();
                })
                ->toArray()
        );
    }

    public function testExtractPathInvalidOptionsType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches(
            '/^Argument 2 passed to Icings\\\\Menu\\\\View\\\\Helper\\\\MenuHelper::extractPath\(\) must be of the type array, (int|integer) given/'
        );

        $this->Menu->extractPath($this->Menu->create('menu'), 123);
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region getCurrentItem() menu
    // -----------------------------------------------------------------------------------------------------------------

    public function testGetCurrentItemFromLastCreatedMenu(): void
    {
        $other = $this->Menu->create('other');
        $other->addChild('Parent', ['uri' => '/parent']);
        $other['Parent']->addChild('Child', ['uri' => '/parent/child'])->setCurrent(true);

        $main = $this->Menu->create('main');
        $main->addChild('Parent', ['uri' => '/parent']);
        $main['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $main['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild'])->setCurrent(true);

        $currentItem = $this->Menu->getCurrentItem();

        $this->assertSame($main['Parent']['Child']['Grandchild'], $currentItem);
    }

    public function testGetCurrentItemFromLastCreatedMenuNoMenuHasBeenCreated(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No menu has been created.');

        $this->Menu->getCurrentItem();
    }

    public function testGetCurrentItemFromNamedMenu(): void
    {
        $other = $this->Menu->create('other');
        $other->addChild('Parent', ['uri' => '/parent']);
        $other['Parent']->addChild('Child', ['uri' => '/parent/child'])->setCurrent(true);

        $main = $this->Menu->create('main');
        $main->addChild('Parent', ['uri' => '/parent']);
        $main['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $main['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild'])->setCurrent(true);

        $this->assertSame($other['Parent']['Child'], $this->Menu->getCurrentItem('other'));
        $this->assertSame($main['Parent']['Child']['Grandchild'], $this->Menu->getCurrentItem('main'));
    }

    public function testGetCurrentItemFromNamedMenuMenuDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The menu with the name `non-existent` does not exist.');

        $this->Menu->create('other');
        $this->Menu->create('main');

        $this->Menu->getCurrentItem('non-existent');
    }

    public function testGetCurrentItemFromMenuObject(): void
    {
        $menu = $this->Menu->create('main');
        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild'])->setCurrent(true);

        $this->assertSame($menu['Parent']['Child']['Grandchild'], $this->Menu->getCurrentItem($menu));
    }

    public function testGetCurrentItemRetrievesTheFirstCurrentItem(): void
    {
        $menu = $this->Menu->create('main');
        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child'])->setCurrent(true);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild'])->setCurrent(true);

        $this->assertSame($menu['Parent']['Child'], $this->Menu->getCurrentItem($menu));
    }

    public function testGetCurrentItemUsingMatcherWithVoters(): void
    {
        $this->Menu->getView()->setRequest(
            $this->Menu->getView()->getRequest()->withUri(new Uri('/parent/child'))
        );

        $menu = $this->Menu->create('main');
        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild']);

        $this->assertSame($menu['Parent']['Child'], $this->Menu->getCurrentItem($menu));
    }

    public function testGetCurrentItemInvalidMenuType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `$menu` argument must be either a `Knp\Menu\ItemInterface` implementation, the name of a menu, or an array, `integer` given.');

        $this->Menu->getCurrentItem(123);
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region getCurrentItem() options
    // -----------------------------------------------------------------------------------------------------------------

    public function getCurrentItemMergeWithHelperOptionsTestSetup(): array
    {
        $voter1 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter2 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->identicalTo($voter1));
        $matcher
            ->expects($this->at(1))
            ->method('addVoter')
            ->with($this->identicalTo($voter2));
        $matcher
            ->expects($this->never())
            ->method('clear');

        $helperOptions = [
            'matcher' => $matcher,
            'voters' => [$voter1],
            'clearMatcher' => true,
        ];

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
            ])
            ->getMock();
        $helper
            ->setConfig($helperOptions);
        $helper
            ->expects($this->never())
            ->method('_createDefaultMatcher');
        $helper
            ->expects($this->never())
            ->method('_createDefaultVoters');

        $menu = $helper->create('name');
        $menu->addChild('Item')->setCurrent(true);

        $getCurrentItemOptions = [
            'voters' => [$voter2],
            'clearMatcher' => false,
        ];

        return compact('helper', 'menu', 'getCurrentItemOptions');
    }

    public function testGetCurrentItemMergeWithHelperOptionsNoMenuArgument(): void
    {
        $test = $this->getCurrentItemMergeWithHelperOptionsTestSetup();
        $test['helper']->getCurrentItem(null, $test['getCurrentItemOptions']);
    }

    public function testGetCurrentItemMergeWithHelperOptionsGetCurrentItemOptionsViaMenuArgument(): void
    {
        $test = $this->getCurrentItemMergeWithHelperOptionsTestSetup();
        $test['helper']->getCurrentItem($test['getCurrentItemOptions']);
    }

    public function testGetCurrentItemMergeWithHelperOptionsNamedMenu(): void
    {
        $test = $this->getCurrentItemMergeWithHelperOptionsTestSetup();
        $test['helper']->getCurrentItem($test['menu']->getName(), $test['getCurrentItemOptions']);
    }

    public function testGetCurrentItemMergeWithHelperOptionsMenuInstance(): void
    {
        $test = $this->getCurrentItemMergeWithHelperOptionsTestSetup();
        $test['helper']->getCurrentItem($test['menu'], $test['getCurrentItemOptions']);
    }

    public function testGetCurrentItemDefaultMatchingOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->callback(function ($argument) {
                return ($argument instanceof UrlVoter) &&
                       $argument->getConfig('ignoreQueryString') === true;
            }));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);

        $helper->getCurrentItem($menu);
    }

    public function testGetCurrentItemUrlMatchingOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->callback(function ($argument) {
                return ($argument instanceof UrlVoter) &&
                       $argument->getConfig('ignoreQueryString') === true;
            }));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);

        $helper->getCurrentItem($menu, [
            'matching' => MenuHelper::MATCH_URL,
        ]);
    }

    public function testGetCurrentItemUrlWithQueryStringMatchingOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->callback(function ($argument) {
                return ($argument instanceof UrlVoter) &&
                       $argument->getConfig('ignoreQueryString') === false;
            }));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);

        $helper->getCurrentItem($menu, [
            'matching' => MenuHelper::MATCH_URL_WITH_QUERY_STRING,
        ]);
    }

    public function testGetCurrentItemFuzzyRouteMatchingOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->isInstanceOf(FuzzyRouteVoter::class));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);

        $helper->getCurrentItem($menu, [
            'matching' => MenuHelper::MATCH_FUZZY_ROUTE,
        ]);
    }

    public function testGetCurrentItemInvalidMatchingOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `matching` option must be one of the `Icings\Menu\View\Helper\MenuHelper::MATCH_*` constant values, `\'invalid\'` given.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->getCurrentItem($menu, [
            'matching' => 'invalid',
        ]);
    }

    public function testGetCurrentItemMatcherOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->identicalTo($voter));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
            ])
            ->getMock();
        $helper
            ->expects($this->never())
            ->method('_createDefaultMatcher');
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultVoters')
            ->with(MenuHelper::MATCH_URL)
            ->willReturn([$voter]);

        $helper->getCurrentItem($menu, [
            'matcher' => $matcher,
        ]);
    }

    public function testGetCurrentItemInvalidMatcherOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `matcher` option must be a `Icings\Menu\Matcher\MatcherInterface` implementation, `string` given.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->getCurrentItem($menu, [
            'matcher' => 'invalid',
        ]);
    }

    public function testGetCurrentItemVotersOption(): void
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->identicalTo($voter));

        /** @var MenuHelper|MockObject $helper */
        $helper = $this
            ->getMockBuilder(MenuHelper::class)
            ->setConstructorArgs([$this->View])
            ->setMethods([
                '_createDefaultMatcher',
                '_createDefaultVoters',
            ])
            ->getMock();
        $helper
            ->expects($this->at(0))
            ->method('_createDefaultMatcher')
            ->willReturn($matcher);
        $helper
            ->expects($this->never())
            ->method('_createDefaultVoters');

        $helper->getCurrentItem($menu, [
            'voters' => [$voter],
        ]);
    }

    public function testGetCurrentItemInvalidVotersOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The `voters` option must be an array, `string` given.');

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->getCurrentItem($menu, [
            'voters' => 'invalid',
        ]);
    }

    public function testGetCurrentItemInvalidVotersOptionArray(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches(
            '/^Argument 1 .+? must (be an instance of|implement interface) Knp\\\\Menu\\\\Matcher\\\\Voter\\\\VoterInterface, string given/'
        );

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->getCurrentItem($menu, [
            'voters' => ['invalid'],
        ]);
    }

    public function testGetCurrentItemMatcherClearedByDefault(): void
    {
        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->onlyMethods(['clear'])
            ->getMock();
        $matcher
            ->expects($this->once())
            ->method('clear');

        $menu = $this->Menu->create('main');
        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild'])->setCurrent(true);

        $this->Menu->getCurrentItem($menu, [
            'matcher' => $matcher,
        ]);
    }

    public function testGetCurrentItemDoNotClearMatcher(): void
    {
        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->onlyMethods(['clear'])
            ->getMock();
        $matcher
            ->expects($this->never())
            ->method('clear');

        $menu = $this->Menu->create('main');
        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild'])->setCurrent(true);

        $this->Menu->getCurrentItem($menu, [
            'matcher' => $matcher,
            'clearMatcher' => false,
        ]);
    }

    public function testGetCurrentItemOptionsOnly(): void
    {
        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();

        $matcher = $this
            ->getMockBuilder(Matcher::class)
            ->getMock();
        $matcher
            ->expects($this->at(0))
            ->method('addVoter')
            ->with($this->identicalTo($voter));
        $matcher
            ->expects($this->never())
            ->method('clear');

        $menu = $this->Menu->create('main');
        $menu->addChild('Parent', ['uri' => '/parent']);
        $menu['Parent']->addChild('Child', ['uri' => '/parent/child']);
        $menu['Parent']['Child']->addChild('Grandchild', ['uri' => '/parent/child/grandchild'])->setCurrent(true);

        $this->Menu->getCurrentItem([
            'matcher' => $matcher,
            'voters' => [$voter],
            'clearMatcher' => false,
        ]);
    }

    public function testGetCurrentItemInvalidOptionsType(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches(
            '/^Argument 2 passed to Icings\\\\Menu\\\\View\\\\Helper\\\\MenuHelper::getCurrentItem\(\) must be of the type array, (int|integer) given/'
        );

        $this->Menu->getCurrentItem($this->Menu->create('menu'), 123);
    }

    //endregion
}
