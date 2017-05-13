<?php
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
    const MATCH_FUZZY_ROUTE = 'matchFuzzyRoute';

    /**
     * The URL matching mode.
     *
     * @var string
     */
    const MATCH_URL = 'matchUrl';

    /**
     * The URL matching mode that includes query strings.
     *
     * @var string
     */
    const MATCH_URL_WITH_QUERY_STRING = 'matchUrlWithQueryString';

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'matching' => null,
        'matcher' => null,
        'voters' => null,
        'renderer' => null
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
    protected $_factory = null;

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
    public function getMenuFactory()
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
     * default renderer, the `StringTemplateRenderer`. See the class' API docs for information on
     * the supported options.
     *
     * @see MatcherInterface
     * @see VoterInterface
     * @see FuzzyRouteVoter
     * @see RendererInterface
     * @see StringTemplateRenderer::$_defaultConfig
     * @see StringTemplateRenderer::__construct()
     * @see create()
     * @see render()
     *
     * @param View $View The View this helper is being attached to.
     * @param array $config An array of options, see the "Configuration options" section in the
     *   method description.
     */
    public function __construct(View $View, array $config = [])
    {
        $config += [
            'matching' => static::MATCH_URL
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
     * Additionally to the options listed above, this method supports all the options that the
     * constructor supports.
     *
     * @see __construct()
     *
     * @throws \InvalidArgumentException In case the `$name` argument is not a string, or is empty.
     *
     * @param string $name The name of the menu. The name serves as an identifier for retrieving
     *   and rendering specific menus.
     * @param array $options An array of options, see the "Options" section in the method
     *   description.
     * @return ItemInterface
     */
    public function create($name, array $options = [])
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(sprintf(
                'The `$name` argument must be a string, `%s` given.',
                Debugger::getType($name)
            ));
        }

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
     *
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
     *
     * @param ItemInterface|string|array $menu Either an `\Knp\Menu\ItemInterface` implementation,
     *  the name of a menu created via `create()`, or an array of options to use instead of the
     *  `$options` argument. If omitted or an array, the menu that was last created via `create()`
     *  will be used.
     * @param array $options An array of options, see the "Options" section in the method
     *  description.
     * @return string The rendered menu.
     */
    public function render($menu = null, array $options = [])
    {
        if (is_array($menu)) {
            $options = $menu;
            $menu = null;
        }

        $createOptions = [];
        if ($menu === null) {
            if (empty($this->_menus)) {
                throw new \RuntimeException('No menu has been created.');
            }

            $menu = end($this->_menus);
            $createOptions = $this->_menuConfigurations[$menu];
        } elseif (is_string($menu)) {
            if (!isset($this->_menus[$menu])) {
                throw new \InvalidArgumentException(
                    sprintf('The menu with the name `%s` does not exist.', $menu)
                );
            }

            $menu = $this->_menus[$menu];
            $createOptions = $this->_menuConfigurations[$menu];
        } elseif (!($menu instanceof ItemInterface)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The `$menu` argument must be either a `Knp\Menu\ItemInterface` implementation, ' .
                    'the name of a menu, or an array, `%s` given.',
                    Debugger::getType($menu)
                )
            );
        }

        $options = Hash::merge($this->config(), $options, $createOptions);
        $rendererOptions = $this->_extractRendererOptions($options);

        if ($options['renderer'] === null) {
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

            if (!isset($options['voters'])) {
                $voters = $this->_createDefaultVoters($options['matching']);
                if ($voters === false) {
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

        return $renderer->render($menu, $rendererOptions);
    }

    /**
     * Stores a menu and associates a set of configuration options with it for later use
     * with `render()`.
     *
     * @param ItemInterface $menu The menu to store.
     * @param array $options The options to associate with it.
     * @return void
     */
    protected function _addMenu(ItemInterface $menu, array $options)
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
    protected function _extractMenuOptions(array &$options)
    {
        $menuOptions = array_intersect_key(
            $options,
            array_flip([
                'templates',
                'templateVars',
                'menuAttributes'
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
    protected function _extractRendererOptions(array &$options)
    {
        $rendererOptions = array_diff_key(
            $options,
            array_flip([
                'menuAttributes',
                'matching',
                'matcher',
                'voters',
                'renderer'
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
    protected function _createDefaultMatcher()
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
    protected function _createDefaultVoters($type)
    {
        switch ($type) {
            case static::MATCH_FUZZY_ROUTE:
                return [
                    new FuzzyRouteVoter($this->request)
                ];
            case static::MATCH_URL:
                return [
                    new UrlVoter($this->request)
                ];
            case static::MATCH_URL_WITH_QUERY_STRING:
                return [
                    new UrlVoter($this->request, [
                        'ignoreQueryString' => false
                    ])
                ];
        }

        return false;
    }

    /**
     * Creates the default renderer.
     *
     * @param MatcherInterface $matcher The matcher to pass to the renderer.
     * @return StringTemplateRenderer
     */
    protected function _createDefaultRenderer(MatcherInterface $matcher)
    {
        return new StringTemplateRenderer($matcher);
    }
}
