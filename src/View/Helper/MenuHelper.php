<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\View\Helper;

use Cake\Error\Debugger;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Cake\View\View;
use Icings\Menu\Integration\PerItemVotersExtension;
use Icings\Menu\Integration\RoutingExtension;
use Icings\Menu\Integration\TemplaterExtension;
use Icings\Menu\Matcher\Matcher;
use Icings\Menu\Matcher\MatcherInterface;
use Icings\Menu\Matcher\Voter\FuzzyRouteVoter;
use Icings\Menu\Matcher\Voter\UrlVoter;
use Icings\Menu\MenuFactory;
use Icings\Menu\MenuFactoryInterface;
use Icings\Menu\Renderer\StringTemplateRenderer;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Knp\Menu\Renderer\RendererInterface;

/**
 * Menu helper
 */
class MenuHelper extends Helper
{
    /**
     * The fuzzy route matching mode.
     *
     * @var string
     */
    public const MATCH_FUZZY_ROUTE = 'matchFuzzyRoute';

    /**
     * The URL matching mode.
     *
     * @var string
     */
    public const MATCH_URL = 'matchUrl';

    /**
     * The URL matching mode that includes query strings.
     *
     * @var string
     */
    public const MATCH_URL_WITH_QUERY_STRING = 'matchUrlWithQueryString';

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'matching' => null,
        'matcher' => null,
        'voters' => null,
        'renderer' => null,
    ];

    /**
     * Collection of menu items created via `create()`.
     *
     * @var ItemInterface[]
     */
    protected $_menus = [];

    /**
     * Storage for menu configurations assigned to menus via `create()`.
     *
     * @var \SplObjectStorage
     */
    protected $_menuConfigurations;

    /**
     * The factory to use for creating menu items.
     *
     * @var MenuFactoryInterface
     */
    protected $_factory;

    /**
     * Sets the menu factory to use for creating menu items.
     *
     * @param MenuFactoryInterface $factory The factory to assign.
     * @return $this
     */
    public function setMenuFactory(MenuFactoryInterface $factory)
    {
        $this->_factory = $factory;

        return $this;
    }

    /**
     * Gets the menu factory to use for creating menu items.
     *
     * @return MenuFactoryInterface
     */
    public function getMenuFactory(): MenuFactoryInterface
    {
        return $this->_factory;
    }

    /**
     * Constructor.
     *
     * ## Configuration options
     *
     * The `$config` argument supports the following keys:
     *
     * - `matching` (`string`, defaults to
     *   `\Icings\Menu\View\Helper\MenuHelper::MATCH_URL`)
     *   Defines the mode to use for matching the menu items against the current request in order
     *   to determine the active items. This is shorthand for passing a constructed matcher object
     *   via the `matcher` option.
     *
     * - `matcher` (`Icings\Menu\Matcher\MatcherInterface`, defaults to
     *   `Icings\Menu\Matcher\Matcher`)
     *   The matcher object to use.
     *
     * - `voters` (`Knp\Menu\Matcher\Voter\VoterInterface[]`, defaults to
     *   `[Icings\Menu\Matcher\Voter\FuzzyRouteVoter]`)
     *   The voter objects to use.
     *
     * - `renderer` (`Knp\Menu\Renderer\RendererInterface`, defaults to
     *   `Icings\Menu\Renderer\StringTemplateRenderer`)
     *    The renderer object to use.
     *
     * Additional options that do not match the ones listed above, will be passed as options to the
     * renderer, which by default is the `StringTemplateRenderer`. See the class' API docs for
     * information on the supported options.
     *
     * @see MatcherInterface
     * @see VoterInterface
     * @see FuzzyRouteVoter
     * @see RendererInterface
     * @see StringTemplateRenderer::$_defaultConfig
     * @see StringTemplateRenderer::__construct()
     * @see create()
     * @see render()
     * @param View $View The View this helper is being attached to.
     * @param array $config An array of options, see the "Configuration options" section in the
     *   method description.
     */
    public function __construct(View $View, array $config = [])
    {
        $config += [
            'matching' => static::MATCH_URL,
        ];
        parent::__construct($View, $config);

        $this->_menuConfigurations = new \SplObjectStorage();

        $factory = new MenuFactory();
        $factory->addExtension(new PerItemVotersExtension());
        $factory->addExtension(new RoutingExtension());
        $factory->addExtension(new TemplaterExtension());

        $this->setMenuFactory($factory);
    }

    /**
     * Creates a menu.
     *
     * ## Options
     *
     * The `$options` argument supports the following keys:
     *
     * - `menuAttributes`: The HTML attributes to apply to the menu element. Defaults to `null`.
     *
     * Similar to the constructor, additional options that do not match the ones listed above will be
     * passed as options to the renderer.
     *
     * @see __construct()
     * @throws \InvalidArgumentException In case the `$name` argument is not a string, or is empty.
     * @param string $name The name of the menu. The name serves as an identifier for retrieving
     *   and rendering specific menus.
     * @param array $options An array of options, see the "Options" section in the method
     *   description.
     * @return ItemInterface
     */
    public function create(string $name, array $options = []): ItemInterface
    {
        if (strlen(trim($name)) === 0) {
            throw new \InvalidArgumentException('The `$name` argument must not be empty.');
        }

        $menu = $this->getMenuFactory()->createItem($name, $this->_extractMenuOptions($options));
        $this->_addMenu($menu, $this->_extractRendererOptions($options));

        return $menu;
    }

    /**
     * Renders a menu.
     *
     * ## Options
     *
     * This method supports all the options that the constructor supports.
     *
     * @see __construct()
     * @throws \RuntimeException In case no menu object is passed, and no menu has been created via
     *  the `create()` method yet.
     * @throws \InvalidArgumentException In case the menu with name given in the `$menu` argument
     *  does not exist.
     * @throws \InvalidArgumentException In case the `$menu` argument is neither a
     *   `Knp\Menu\ItemInterface` implementation, the name of a menu, nor an array.
     * @throws \InvalidArgumentException In case the `matcher` option is not a
     *  `Icings\Menu\Matcher\MatcherInterface` implementation.
     * @throws \InvalidArgumentException In case the `matching` option is not one of
     *  `Icings\Menu\View\Helper\MenuHelper::MATCH_*` constant vales.
     * @throws \InvalidArgumentException In case the `voters` option is not an array.
     * @throws \InvalidArgumentException In case the `renderer` option is not a
     *  `Knp\Menu\Renderer\RendererInterface` implementation.
     * @param ItemInterface|string|array|null $menu Either an `\Knp\Menu\ItemInterface` implementation,
     *  the name of a menu created via `create()`, or an array of options to use instead of the
     *  `$options` argument. If omitted or an array, the menu that was last created via `create()`
     *  will be used.
     * @param array $options An array of options, see the "Options" section in the method
     *  description.
     * @return string The rendered menu.
     */
    public function render($menu = null, array $options = []): string
    {
        if (is_array($menu)) {
            $options = $menu;
            $menu = null;
        }

        $menu = $this->_getMenu($menu);

        $createOptions = [];
        if (isset($this->_menuConfigurations[$menu])) {
            $createOptions = $this->_menuConfigurations[$menu];
        }

        /** @psalm-suppress TooManyArguments */
        $options = Hash::merge($this->getConfig(), $options, $createOptions);
        $rendererOptions = $this->_extractRendererOptions($options);

        $renderer = $this->_getRenderer($options);

        return $renderer->render($menu, $rendererOptions);
    }

    /**
     * Extracts a path of items, from the root up to and including the given item.
     *
     * The extracted items will be clones of the original items, with their respective parent and
     * child items removed. The original item is attached as an extra with the key `original`, so
     * it can be retrieved via `$item->getExtra('original')`.
     *
     * ## Options
     *
     * The `$options` argument supports the following keys:
     *
     * - `includeRoot` (`bool`, defaults to `false`)
     *   Defines whether to include the root element in the returned path. The root element, ie
     *   the top most element in a menu is usually the menu itself, not an actual menu item that
     *   is being rendered and has a URL assigned for matching, hence it is by default excluded.
     *
     * @param ItemInterface $item The item from which to extract the path from.
     * @param array $options An array of options, see the "Options" section in the method description.
     * @return ItemInterface[] A set of items representing the path from the root item.
     */
    public function extractPath(ItemInterface $item, array $options = []): array
    {
        $options += [
            'includeRoot' => false,
        ];

        $path = [$item];
        while ($item = $item->getParent()) {
            $path[] = $item;
        }

        if (!$options['includeRoot']) {
            \array_pop($path);
        }

        foreach ($path as $key => $item) {
            $clone = clone $item;
            $clone->setParent(null);
            $clone->setChildren([]);
            $clone->setExtra('original', $item);

            $path[$key] = $clone;
        }

        return \array_reverse($path);
    }

    /**
     * Returns the first item which matches as current.
     *
     * ## Options
     *
     * The `$options` argument supports the following keys:
     *
     * - `matching` (`string`, defaults to
     *   `\Icings\Menu\View\Helper\MenuHelper::MATCH_URL`)
     *   Defines the mode to use for matching the menu items against the current request in order
     *   to determine the active items. This is shorthand for passing a constructed matcher object
     *   via the `matcher` option.
     *
     * - `matcher` (`Icings\Menu\Matcher\MatcherInterface`, defaults to
     *   `Icings\Menu\Matcher\Matcher`)
     *   The matcher object to use.
     *
     * - `voters` (`Knp\Menu\Matcher\Voter\VoterInterface[]`, defaults to
     *   `[Icings\Menu\Matcher\Voter\FuzzyRouteVoter]`)
     *   The voter objects to use.
     *
     * - `clearMatcher` (`boolean`, defaults to `true`)
     *   Defines whether the matcher cache should be cleared after searching through the menu.
     *
     * Similar to the `render()` method, this method will use the helper defaults for the
     * options if not specified.
     *
     * @param ItemInterface|string|array|null $menu The menu (item) to search through. Either an
     * `\Knp\Menu\ItemInterface` implementation, the name of a menu created via `create()`, or
     *  an array of options to use instead of the `$options` argument. If omitted or an array,
     *  the menu that was last created via `create()` will be used.
     * @param array $options An array of options, see the "Options" section in the method
     *  description.
     * @return ItemInterface|null The first current item or `null` if no current item could be found.
     */
    public function getCurrentItem($menu = null, array $options = []): ?ItemInterface
    {
        if (is_array($menu)) {
            $options = $menu;
            $menu = null;
        }

        $menu = $this->_getMenu($menu);

        /** @psalm-suppress TooManyArguments */
        $options = Hash::merge($this->getConfig(), $options);
        $options += [
            'clearMatcher' => true,
        ];

        $matcher = $this->_getMatcher($options);
        $voters = $this->_getVoters($options);

        foreach ($voters as $voter) {
            $matcher->addVoter($voter);
        }

        $currentItem = $this->_findCurrentItem($menu, $matcher);

        if ($options['clearMatcher']) {
            $matcher->clear();
        }

        return $currentItem;
    }

    /**
     * Returns a menu instance based on the given parameters.
     *
     * @throws \RuntimeException In case no menu object is passed, and no menu has been created via
     *  the `create()` method yet.
     * @throws \InvalidArgumentException In case the menu with name given in the `$menu` argument
     *  does not exist.
     * @throws \InvalidArgumentException In case the `$menu` argument is neither a
     *   `Knp\Menu\ItemInterface` implementation, the name of a menu, nor an array.
     * @param ItemInterface|string|null $menu Either an `\Knp\Menu\ItemInterface` implementation, or
     *  the name of a menu created via `create()`. If omitted, the menu that was last created via
     * `create()` will be obtained.
     * @return ItemInterface
     */
    protected function _getMenu($menu = null): ItemInterface
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($menu === null) {
            if (empty($this->_menus)) {
                throw new \RuntimeException('No menu has been created.');
            }

            /** @var ItemInterface $menu */
            $menu = end($this->_menus);
        } elseif (is_string($menu)) {
            if (!isset($this->_menus[$menu])) {
                throw new \InvalidArgumentException(
                    sprintf('The menu with the name `%s` does not exist.', $menu)
                );
            }

            $menu = $this->_menus[$menu];
        } elseif (!($menu instanceof ItemInterface)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The `$menu` argument must be either a `Knp\Menu\ItemInterface` implementation, ' .
                    'the name of a menu, or an array, `%s` given.',
                    Debugger::getType($menu)
                )
            );
        }

        return $menu;
    }

    /**
     * Returns a matcher instance based on the given options.
     *
     * ## Options
     *
     * The `$options` argument supports the following keys:
     *
     * - `matcher` (`Icings\Menu\Matcher\MatcherInterface`, defaults to
     *   `Icings\Menu\Matcher\Matcher`)
     *   The matcher object to use.
     *
     * @throws \InvalidArgumentException In case the `matcher` option is not a
     *  `Icings\Menu\Matcher\MatcherInterface` implementation.
     * @param array $options An array of options, see the "Options" section in the method
     *  description.
     * @return MatcherInterface
     */
    protected function _getMatcher(array $options): MatcherInterface
    {
        if (!isset($options['matcher'])) {
            $matcher = $this->_createDefaultMatcher();
        } else {
            if (!($options['matcher'] instanceof MatcherInterface)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The `matcher` option must be a `Icings\Menu\Matcher\MatcherInterface` ' .
                        'implementation, `%s` given.',
                        Debugger::getType($options['matcher'])
                    )
                );
            }

            $matcher = $options['matcher'];
        }

        return $matcher;
    }

    /**
     * Returns an array of voter instances based on the given options.
     *
     * ## Options
     *
     * The `$options` argument supports the following keys:
     *
     * - `matching` (`string`, defaults to
     *   `\Icings\Menu\View\Helper\MenuHelper::MATCH_URL`)
     *   Defines the mode to use for matching the menu items against the current request in order
     *   to determine the active items. This is shorthand for passing a constructed matcher object
     *   via the `matcher` option.
     *
     * - `voters` (`Knp\Menu\Matcher\Voter\VoterInterface[]`, defaults to
     *   `[Icings\Menu\Matcher\Voter\FuzzyRouteVoter]`)
     *   The voter objects to use.
     *
     * @throws \InvalidArgumentException In case the `matching` option is not one of
     *  `Icings\Menu\View\Helper\MenuHelper::MATCH_*` constant vales.
     * @throws \InvalidArgumentException In case the `voters` option is not an array.
     * @param array $options An array of options, see the "Options" section in the method
     *  description.
     * @return VoterInterface[]
     */
    protected function _getVoters(array $options): array
    {
        if (!isset($options['voters'])) {
            $voters = $this->_createDefaultVoters($options['matching']);
            if (!is_array($voters)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The `matching` option must be one of the `Icings\Menu\View\Helper\MenuHelper::MATCH_*` ' .
                        'constant values, `%s` given.',
                        Debugger::exportVar($options['matching'])
                    )
                );
            }
        } else {
            if (!is_array($options['voters'])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The `voters` option must be an array, `%s` given.',
                        Debugger::getType($options['voters'])
                    )
                );
            }

            $voters = $options['voters'];
        }

        return $voters;
    }

    /**
     * Returns a renderer instance based on the given options.
     *
     * ## Configuration options
     *
     * The `$options` argument supports the following keys:
     *
     * - `renderer` (`Knp\Menu\Renderer\RendererInterface`, defaults to
     *   `Icings\Menu\Renderer\StringTemplateRenderer`)
     *    The renderer object to use.
     *
     * @throws \InvalidArgumentException In case the `renderer` option is not a
     *  `Knp\Menu\Renderer\RendererInterface` implementation.
     * @param array $options An array of options, see the "Options" section in the method
     *  description.
     * @return RendererInterface
     */
    protected function _getRenderer(array $options): RendererInterface
    {
        if ($options['renderer'] === null) {
            $matcher = $this->_getMatcher($options);
            $voters = $this->_getVoters($options);

            foreach ($voters as $voter) {
                $matcher->addVoter($voter);
            }

            $renderer = $this->_createDefaultRenderer($matcher);
        } else {
            if (!($options['renderer'] instanceof RendererInterface)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The `renderer` option must be a `Knp\Menu\Renderer\RendererInterface` ' .
                        'implementation, `%s` given.',
                        Debugger::getType($options['renderer'])
                    )
                );
            }

            $renderer = $options['renderer'];
        }

        return $renderer;
    }

    /**
     * Searches for and returns the first item which matches as current.
     *
     * @param ItemInterface $item The menu (item) to search through.
     * @param MatcherInterface $matcher The matcher to use for matching the current item.
     * @return ItemInterface|null The current item, or `null` if no item matches are current.
     */
    protected function _findCurrentItem(ItemInterface $item, MatcherInterface $matcher): ?ItemInterface
    {
        if ($matcher->isCurrent($item)) {
            return $item;
        }

        if ($item->hasChildren()) {
            foreach ($item->getChildren() as $child) {
                $current = $this->_findCurrentItem($child, $matcher);
                if ($current) {
                    return $current;
                }
            }
        }

        return null;
    }

    /**
     * Stores a menu and associates a set of configuration options with it for later use
     * with `render()`.
     *
     * @param ItemInterface $menu The menu to store.
     * @param array $options The options to associate with it.
     * @return void
     */
    protected function _addMenu(ItemInterface $menu, array $options): void
    {
        $this->_menus[$menu->getName()] = $menu;
        $this->_menuConfigurations->attach($menu, $options);
    }

    /**
     * Extracts the menu options from an options array.
     *
     * Menu options are:
     *
     * - `templates`
     * - `templateVars`
     * - `menuAttributes`
     *
     * @param array $options The options array from which to extract (and remove) the menu options.
     * @return array The extracted renderer options.
     */
    protected function _extractMenuOptions(array &$options): array
    {
        /** @psalm-suppress PossiblyNullArgument */
        $menuOptions = array_intersect_key(
            $options,
            array_flip([
                'templates',
                'templateVars',
                'menuAttributes',
            ])
        );

        $options = array_diff_key($options, $menuOptions);

        return $menuOptions;
    }

    /**
     * Extracts the renderer options from an options array.
     *
     * Renderer options are all options except for:
     *
     * - `menuAttributes`
     * - `matching`
     * - `matcher`
     * - `voters`
     * - `renderer`
     *
     * @param array $options The options array from which to extract (and remove) the renderer
     *   options.
     * @return array The extracted renderer options.
     */
    protected function _extractRendererOptions(array &$options): array
    {
        /** @psalm-suppress PossiblyNullArgument */
        $rendererOptions = array_diff_key(
            $options,
            array_flip([
                'menuAttributes',
                'matching',
                'matcher',
                'voters',
                'renderer',
            ])
        );

        $options = array_diff_key($options, $rendererOptions);

        return $rendererOptions;
    }

    /**
     * Creates the default matcher.
     *
     * @return MatcherInterface
     */
    protected function _createDefaultMatcher(): MatcherInterface
    {
        return new Matcher();
    }

    /**
     * Creates default voters for the given type.
     *
     * @param string $type The type of the voters to create.
     * @return VoterInterface[]|bool An array holding the created voters, or `false` for unsupported
     *   types.
     */
    protected function _createDefaultVoters(string $type)
    {
        switch ($type) {
            case static::MATCH_FUZZY_ROUTE:
                return [
                    new FuzzyRouteVoter($this->getView()->getRequest()),
                ];
            case static::MATCH_URL:
                return [
                    new UrlVoter($this->getView()->getRequest()),
                ];
            case static::MATCH_URL_WITH_QUERY_STRING:
                return [
                    new UrlVoter($this->getView()->getRequest(), [
                        'ignoreQueryString' => false,
                    ]),
                ];
        }

        return false;
    }

    /**
     * Creates the default renderer.
     *
     * @param MatcherInterface $matcher The matcher to pass to the renderer.
     * @return RendererInterface
     */
    protected function _createDefaultRenderer(MatcherInterface $matcher): RendererInterface
    {
        return new StringTemplateRenderer($matcher);
    }
}
