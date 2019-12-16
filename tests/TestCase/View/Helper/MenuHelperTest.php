<?php
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

    public function setUp()
    {
        parent::setUp();

        $this->View = new View();
        $this->Menu = new MenuHelper($this->View);
    }

    public function tearDown()
    {
        unset($this->Menu);
        unset($this->View);

        parent::tearDown();
    }

    // -----------------------------------------------------------------------------------------------------------------
    //region construct()
    // -----------------------------------------------------------------------------------------------------------------

    public function testConstructDefaults()
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

    public function testConstructConfiguration()
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `$name` argument must be a string, `integer` given.
     */
    public function testCreateInvalidNameArgumentType()
    {
        $this->Menu->create(123);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `$name` argument must not be empty.
     */
    public function testCreateInvalidNameArgumentContent()
    {
        $this->Menu->create('');
    }

    public function testCreateMenuReceivesOnlyMenuOptions()
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

    public function testCreateNonMenuItemOptionsAreRendererOptions()
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

        $factory = $this
            ->getMockBuilder(MenuFactoryInterface::class)
            ->getMock();
        $factory
            ->expects($this->once())
            ->method('createItem')
            ->with('name', $menuItemOptions)
            ->willReturn($menu);

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper*/
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

    public function testRenderLastCreatedMenu()
    {
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
            ->with($this->identicalTo($mainMenu));

        $this->Menu->setConfig([
            'renderer' => $renderer,
        ]);
        $this->Menu->setMenuFactory($factory);

        $this->Menu->create('other');
        $this->Menu->create('main');
        $this->Menu->render();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No menu has been created.
     */
    public function testRenderLastCreatedMenuNoMenuHasBeenCreated()
    {
        $this->Menu->render();
    }

    public function testRenderNamedMenu()
    {
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
            ->with($this->identicalTo($mainMenu));

        $this->Menu->setConfig([
            'renderer' => $renderer,
        ]);
        $this->Menu->setMenuFactory($factory);

        $this->Menu->create('other');
        $this->Menu->create('main');
        $this->Menu->render('main');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The menu with the name `non-existent` does not exist.
     */
    public function testRenderNamedMenuDoesNotExist()
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

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

    public function testRenderMenuObject()
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
            ->with($this->identicalTo($menu), $this->isType('array'));

        $this->Menu->setConfig([
            'renderer' => $renderer,
        ]);

        $this->Menu->render($menu);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `$menu` argument must be either a `Knp\Menu\ItemInterface` implementation, the name of a menu, or an array, `integer` given.
     */
    public function testRenderInvalidType()
    {
        $this->Menu->render(123);
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region render() options
    // -----------------------------------------------------------------------------------------------------------------

    public function testRenderDefaultOptions()
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
            ->with($this->identicalTo($menu));

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    public function testRenderRendererOnlyReceivesRendererOptions()
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
            ->with($this->identicalTo($menu), $rendererOptions);

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    public function renderMergeWithHelperAndCreateOptionsTestSetup()
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
            ]);

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

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    public function testRenderMergeWithHelperAndCreateOptionsNoMenuArgument()
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render(null, $test['renderOptions']);
    }

    public function testRenderMergeWithHelperAndCreateOptionsRenderOptionsViaMenuArgument()
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render($test['renderOptions']);
    }

    public function testRenderMergeWithHelperAndCreateOptionsNamedMenu()
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render($test['menu']->getName(), $test['renderOptions']);
    }

    public function testRenderMergeWithHelperAndCreateOptionsMenuInstance()
    {
        $test = $this->renderMergeWithHelperAndCreateOptionsTestSetup();
        $test['helper']->render($test['menu'], $test['renderOptions']);
    }

    public function testRenderDefaultMatchingOption()
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

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    public function testRenderUrlWithQueryStringMatchingOption()
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

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    public function testRenderFuzzyRouteMatchingOption()
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

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `matching` option must be one of the `Icings\Menu\View\Helper\MenuHelper::MATCH_*` constant values, `'invalid'` given.
     */
    public function testRenderInvalidMatchingOption()
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'matching' => 'invalid',
        ]);
    }

    public function testRenderMatcherOption()
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
            ->with($this->identicalTo($menu));

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `matcher` option must be a `Icings\Menu\Matcher\MatcherInterface` implementation, `string` given.
     */
    public function testRenderInvalidMatcherOption()
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'matcher' => 'invalid',
        ]);
    }

    public function testRenderVotersOption()
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
            ->with($this->identicalTo($menu));

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `voters` option must be an array, `string` given.
     */
    public function testRenderInvalidVotersOption()
    {
        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'voters' => 'invalid',
        ]);
    }

    public function testRenderInvalidVotersOptionArray()
    {
        $this->skipIf(PHP_MAJOR_VERSION < 7);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageRegExp(
            '/^Argument 1 .+? must (be an instance of|implement interface) Knp\\\\Menu\\\\Matcher\\\\Voter\\\\VoterInterface, string given/'
        );

        $menu = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $this->Menu->render($menu, [
            'voters' => ['invalid'],
        ]);
    }

    public function testRenderRendererOptionWithObject()
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
            ->with($this->identicalTo($menu));

        /** @var MenuHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The `renderer` option must be a `Knp\Menu\Renderer\RendererInterface` implementation, `string` given.
     */
    public function testRenderInvalidRendererOption()
    {
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

    public function testRenderOutput()
    {
        $menu = $this->Menu->create('menu');
        $menu->addChild('Home', ['uri' => '/uri']);

        $expected = '<ul><li><a href="/uri">Home</a></li></ul>';
        $this->assertEquals($expected, $this->Menu->render());
    }

    //endregion
}
