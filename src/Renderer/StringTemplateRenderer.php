<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Renderer;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;
use Cake\View\StringTemplateTrait;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Renderer\RendererInterface;

/**
 * A renderer that renders menus using string templates.
 *
 * @see StringTemplateTrait
 */
class StringTemplateRenderer implements RendererInterface
{
    use InstanceConfigTrait;
    use StringTemplateTrait;

    /**
     * The matcher to use for determining the active items.
     *
     * @var MatcherInterface
     */
    protected $_matcher;

    /**
     * The default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'templates' => [
            'menu' => '<ul{{attrs}}>{{items}}</ul>',
            'nest' => '<ul{{attrs}}>{{items}}</ul>',
            'item' => '<li{{attrs}}>{{link}}{{nest}}</li>',
            'link' => '<a href="{{url}}"{{attrs}}>{{label}}</a>',
            'text' => '<span{{attrs}}>{{label}}</span>',
        ],
        'templateVars' => null,
        'currentClass' => 'active',
        'ancestorClass' => 'active-ancestor',
        'leafClass' => null,
        'branchClass' => 'has-dropdown',
        'nestedMenuClass' => 'dropdown',
        'menuLevelClass' => null,
        'firstClass' => null,
        'lastClass' => null,
        'depth' => null,
        'matchingDepth' => null,
        'clearMatcher' => true,
        'currentAsLink' => true,
        'inheritItemClasses' => null,
        'consumeItemClasses' => null,
    ];

    /**
     * Constructor.
     *
     * ## Configuration options
     *
     * The `$config` argument supports the following keys:
     *
     * - `templates` (`string[]`)
     *   The templates that should be used. Defaults to:
     *   ```php
     *   [
     *       'menu' => '<ul{{attrs}}>{{items}}</ul>',
     *       'nest' => '<ul{{attrs}}>{{items}}</ul>',
     *       'item' => '<li{{attrs}}>{{link}}{{nest}}</li>',
     *       'link' => '<a href="{{url}}"{{attrs}}>{{label}}</a>',
     *       'text' => '<span{{attrs}}>{{label}}</span>'
     *   ]
     *   ```
     *
     * - `templateVars` (`mixed[]|null`, defaults to `null`)
     *   An array of template variables.
     *
     * - `currentClass` (`string`, defaults to `active`)
     *   The class to set for the current/active items.
     *
     * - `ancestorClass` (`string|null`, defaults to `active-ancestor`)
     *   The class to set for ancestors of current/active items.
     *
     * - `leafClass` (`string|null`, defaults to `null`)
     *   The class to set for menu items that do not have children.
     *
     * - `branchClass` (`string|null`, defaults to `has-dropdown`)
     *   The class to set for menu items that do have children.
     *
     * - `nestedMenuClass` (`string|null`, defaults to `dropdown`)
     *   The class to set for the element that holds the children of a menu item.
     *
     * - `menuLevelClass` (`string|null`, defaults to `null`)
     *   The class prefix to use for denoting the level of a menu. The appended level is an
     *   integer starting at `1`.
     *
     * - `firstClass` (`string|null`, defaults to `null`)
     *   The class to set for the first child item.
     *
     * - `lastClass` (`string|null`, defaults to `null`)
     *   The class to set for the last child item.
     *
     * - `depth` (`integer|null`, defaults to `null`)
     *   The depth up to which the menu should be rendered.
     *
     * - `matchingDepth` (`integer|null`, defaults to `null`)
     *   The depth up to which items should be matched.
     *
     * - `clearMatcher` (`boolean`, defaults to `true`)
     *   Defines whether the matcher cache should be cleared after rendering the menu.
     *
     * - `currentAsLink` (`boolean`, defaults to `true`)
     *   Whether the active item should render a link, or a text element.
     *
     * - `inheritItemClasses` (`boolean|array|null`, defaults to `null`)
     *   Defines which classes should be inherited by menu item's link and text elements.
     *   `true` will cause all classes to be inherited. An array is used to specify specific
     *   classes to inherit (valid class names are `currentClass`, `ancestorClass`, `leafClass`,
     *   `branchClass`, `firstClass`, `lastClass`).
     *
     * - `consumeItemClasses` (`boolean|array|null`, defaults to `null`)
     *   Defines which classes should be consumed by menu item's link and text elements.
     *   `true` will cause all classes to be consumed. An array is used to specify specific
     *   classes to consume (see the `inheritItemClasses` option for a list of valid class
     *   names).
     *
     * @param MatcherInterface $matcher The matcher to use for determining the active items.
     * @param array $config An array of options, see the "Configuration options" section in the
     *   method description.
     */
    public function __construct(MatcherInterface $matcher, array $config = [])
    {
        $this->_matcher = $matcher;
        $this->setConfig($config);

        $templates = $this->getConfig('templates');
        if (is_string($templates)) {
            $this->setConfig('templates', $this->templater()->getConfig());
        }
    }

    /**
     * Renders a menu.
     *
     * ## Options
     *
     * This method supports all the options that the constructor supports.
     *
     * @see __construct()
     * @param ItemInterface $item The menu to render.
     * @param array $options An array of options, see the "Options" section in the method
     *   description.
     * @return string The rendered menu.
     */
    public function render(ItemInterface $item, array $options = []): string
    {
        $options = Hash::merge($this->getConfig(), $options);

        $rendered = $this->_renderMenu($item, $options);

        if ($options['clearMatcher']) {
            $this->_matcher->clear();
        }

        return $rendered;
    }

    /**
     * Renders the root menu item.
     *
     * @param ItemInterface $item The menu item to render.
     * @param array $options The rendering options.
     * @return string The rendered menu item.
     */
    protected function _renderMenu(ItemInterface $item, array $options): string
    {
        if (
            !$item->hasChildren() ||
            $options['depth'] === 0 ||
            !$item->getDisplayChildren()
        ) {
            return '';
        }

        $templater = $this->templater();

        $newTemplates =
            (array)$item->getExtra('templates') +
            (array)$options['templates'];
        if ($newTemplates) {
            $templater->push();
            $templater->add($newTemplates);
        }
        $options['defaultTemplates'] = $templater->getConfig();
        unset($options['templates']);

        $templateVars =
            (array)$item->getExtra('templateVars') +
            (array)$options['templateVars'];
        if (empty($templateVars)) {
            $templateVars = null;
        }
        $options['defaultTemplateVars'] = $templateVars;
        unset($options['templateVars']);

        $rendered = (string)$templater->format('menu', [
            'attrs' => $this->_formatAttributes($item->getChildrenAttributes(), $item),
            'templateVars' => $templateVars,
            'items' => $this->_renderChildren($item, $options),
        ]);

        if ($newTemplates) {
            $templater->pop();
        }

        return $rendered;
    }

    /**
     * Renders the menu items children in a to be nested element.
     *
     * ## Options
     *
     * - `attributes`: The HTML attributes to apply to the element.
     * - `templateVars`: The template variables to use for the element.
     * - `childrenTemplates`: The templates to use for the children of the element.
     *
     * @param ItemInterface $item The menu whose children to render.
     * @param array $options The rendering options, see the "Options" section in the method
     *   description for specific nesting element options.
     * @return string The rendered element.
     */
    protected function _renderNested(ItemInterface $item, array $options): string
    {
        if (
            !$item->hasChildren() ||
            $options['depth'] === 0 ||
            !$item->getDisplayChildren()
        ) {
            return '';
        }

        $templater = $this->templater();

        $attributes = $this->_formatAttributes($options['attributes'], $item);
        unset($options['attributes']);

        $templateVars = $options['templateVars'];
        unset($options['templateVars']);

        $newTemplates = $options['childrenTemplates'];
        unset($options['childrenTemplates']);
        if ($newTemplates) {
            $templater->push();
            $templater->add($newTemplates);
        }

        $items = $this->_renderChildren($item, $options);

        if ($newTemplates) {
            $templater->pop();
        }

        return (string)$templater->format('nest', [
            'attrs' => $attributes,
            'templateVars' => $templateVars,
            'items' => $items,
        ]);
    }

    /**
     * Renders the menu items children.
     *
     * @param ItemInterface $item The item whose children to render.
     * @param array $options The rendering options.
     * @return string[] The rendered children.
     */
    protected function _renderChildren(ItemInterface $item, array $options): array
    {
        if ($options['depth'] !== null) {
            $options['depth'] = $options['depth'] - 1;
        }

        if (
            $options['matchingDepth'] !== null &&
            $options['matchingDepth'] > 0
        ) {
            $options['matchingDepth'] = $options['matchingDepth'] - 1;
        }

        $children = [];
        foreach ($item->getChildren() as $child) {
            $children[] = $this->_renderItem($child, $options);
        }

        return $children;
    }

    /**
     * Renders the menu item and its children.
     *
     * @param ItemInterface $item The menu item to render.
     * @param array $options The rendering options.
     * @return string The rendered menu item.
     */
    protected function _renderItem(ItemInterface $item, array $options): string
    {
        if (!$item->isDisplayed()) {
            return '';
        }

        $classMap = [];

        if ($this->_matcher->isCurrent($item)) {
            $classMap['currentClass'] = $options['currentClass'];
        } elseif (
            isset($options['ancestorClass']) &&
            $this->_matcher->isAncestor($item, $options['matchingDepth'])
        ) {
            $classMap['ancestorClass'] = $options['ancestorClass'];
        }

        if (
            isset($options['firstClass']) &&
            $item->actsLikeFirst()
        ) {
            $classMap['firstClass'] = $options['firstClass'];
        }
        if (
            isset($options['lastClass']) &&
            $item->actsLikeLast()
        ) {
            $classMap['lastClass'] = $options['lastClass'];
        }

        $hasChildren = $item->hasChildren();
        if (
            $hasChildren &&
            $options['depth'] !== 0
        ) {
            if (
                $options['branchClass'] !== null &&
                $item->getDisplayChildren()
            ) {
                $classMap['branchClass'] = $options['branchClass'];
            }
        } elseif ($options['leafClass'] !== null) {
            $classMap['leafClass'] = $options['leafClass'];
        }

        $class = (array)$item->getAttribute('class');

        $inheritClasses = $item->getExtra('inheritItemClasses');
        if ($inheritClasses === null) {
            $inheritClasses = $options['inheritItemClasses'];
        }
        $consumeClasses = $item->getExtra('consumeItemClasses');
        if ($consumeClasses === null) {
            $consumeClasses = $options['consumeItemClasses'];
        }

        $elementClasses = [];
        if (!empty($classMap)) {
            if ($consumeClasses) {
                if ($consumeClasses === true) {
                    $elementClasses = array_values($classMap);
                    $classMap = [];
                } elseif (is_array($consumeClasses)) {
                    foreach ($consumeClasses as $inheritClass) {
                        if (isset($classMap[$inheritClass])) {
                            $elementClasses[] = $classMap[$inheritClass];
                            unset($classMap[$inheritClass]);
                        }
                    }
                }
            }

            if (
                !empty($classMap) &&
                $inheritClasses
            ) {
                if ($inheritClasses === true) {
                    $elementClasses = array_merge($elementClasses, array_values($classMap));
                } elseif (is_array($inheritClasses)) {
                    foreach ($inheritClasses as $inheritClass) {
                        if (isset($classMap[$inheritClass])) {
                            $elementClasses[] = $classMap[$inheritClass];
                        }
                    }
                }
            }

            $class = array_merge($class, array_values($classMap));
        }

        $attributes = $item->getAttributes();
        if (!empty($class)) {
            sort($class);
            $attributes['class'] = implode(' ', $class);
        }

        $templater = $this->templater();

        $attributes = $this->_formatAttributes($attributes, $item);

        $templates = (array)$item->getExtra('templates');
        $defaultTemplates = (array)$item->getExtra('defaultTemplates');
        $newTemplates = $templates + $defaultTemplates;
        if ($newTemplates) {
            $templater->push();
            $templater->add($newTemplates);
        }

        $templateVars = (array)$item->getExtra('templateVars');
        $defaultTemplateVars = (array)$item->getExtra('defaultTemplateVars');
        $currentDefaultTemplateVars = (array)$options['defaultTemplateVars'];
        $templateVars =
            $templateVars +
            $defaultTemplateVars +
            $currentDefaultTemplateVars;
        if (empty($templateVars)) {
            $templateVars = null;
        }
        $options['templateVars'] = $templateVars;

        $linkOptions = $options;
        if (!empty($elementClasses)) {
            sort($elementClasses);
            $linkOptions['class'] = array_unique($elementClasses);
        }
        $link = $this->_renderLink($item, $linkOptions);

        if ($hasChildren) {
            $nestedClass = (array)$item->getChildrenAttribute('class');
            if ($options['menuLevelClass'] !== null) {
                $nestedClass[] = $options['menuLevelClass'] . $item->getLevel();
            }
            if ($options['nestedMenuClass'] !== null) {
                $nestedClass[] = $options['nestedMenuClass'];
            }
            $nestedAttributes = $item->getChildrenAttributes();
            $nestedAttributes['class'] = implode(' ', $nestedClass);
            $options['attributes'] = $nestedAttributes;

            $options['childrenTemplates'] = null;
            if ($newTemplates) {
                // item defines new templates that must be ignored by the children
                $options['childrenTemplates'] = $options['defaultTemplates'];
            }
            if ($defaultTemplates) {
                // item defines default templates that must be used by the children
                $options['childrenTemplates'] = $defaultTemplates;
            }

            if ($defaultTemplateVars) {
                // item defines default template vars that must be used by the children
                $options['defaultTemplateVars'] =
                    $defaultTemplateVars +
                    $currentDefaultTemplateVars;
            }

            $nested = $this->_renderNested($item, $options);
        } else {
            $nested = '';
        }

        $rendered = (string)$templater->format('item', [
            'attrs' => $attributes,
            'templateVars' => $templateVars,
            'link' => $link,
            'nest' => $nested,
        ]);

        if ($newTemplates) {
            $templater->pop();
        }

        return $rendered;
    }

    /**
     * Renders the link for a menu item.
     *
     * ## Options
     *
     * - `currentAsLink`: Whether to render active items as links, or as text elements.
     *
     * @param ItemInterface $item The menu item for which to render a link.
     * @param array $options The rendering options, see the "Options" section in the method
     *   description specific link options.
     * @return string The rendered link.
     */
    protected function _renderLink(ItemInterface $item, array $options): string
    {
        if (
            $item->getUri() &&
            (
                !$this->_matcher->isCurrent($item) ||
                $options['currentAsLink']
            )
        ) {
            return $this->_renderLinkElement($item, $options);
        } else {
            return $this->_renderTextElement($item, $options);
        }
    }

    /**
     * Renders the menu item as a link element.
     *
     * ## Options
     *
     * - `class`: The class(es) to set on the link element.
     * - `templateVars`: The template variables to use for the link element.
     *
     * @param ItemInterface $item The menu item which to render as a link element.
     * @param array $options The rendering options, see the "Options" section in the method
     *   description specific link options.
     * @return string The rendered link element.
     */
    protected function _renderLinkElement(ItemInterface $item, array $options): string
    {
        if (isset($options['class'])) {
            $class = (array)$item->getLinkAttribute('class');
            $class = array_merge($class, (array)$options['class']);
            $item->setLinkAttribute('class', implode(' ', $class));
        }

        return (string)$this->templater()->format('link', [
            'attrs' => $this->_formatAttributes($item->getLinkAttributes(), $item),
            'templateVars' => $options['templateVars'],
            'url' => h($item->getUri()),
            'label' => $this->_renderLabel($item),
        ]);
    }

    /**
     * Renders the menu item label as text (non-link).
     *
     * ## Options
     *
     * - `class`: The class(es) to set on the text element.
     * - `templateVars`: The template variables to use for the text element.
     *
     * @param ItemInterface $item The item whose text to render.
     * @param array $options The rendering options, see the "Options" section in the method
     *   description specific text options.
     * @return string The rendered text.
     */
    protected function _renderTextElement(ItemInterface $item, array $options): string
    {
        if (isset($options['class'])) {
            $class = (array)$item->getLabelAttribute('class');
            $class = array_merge($class, (array)$options['class']);
            $item->setLabelAttribute('class', implode(' ', $class));
        }

        return (string)$this->templater()->format('text', [
            'attrs' => $this->_formatAttributes($item->getLabelAttributes(), $item),
            'templateVars' => $options['templateVars'],
            'label' => $this->_renderLabel($item),
        ]);
    }

    /**
     * Renders a menu item label.
     *
     * By default the label is being HTML entity encoded, unless explicitly disabled for the given
     * menu item via the `escape` or `escapeLabel` option.
     *
     * @param ItemInterface $item The item whose label to render.
     * @return string The rendered label.
     */
    protected function _renderLabel(ItemInterface $item): string
    {
        if (
            !$item->getExtra('escapeLabel', true) ||
            !$item->getExtra('escape', true)
        ) {
            return (string)$item->getLabel();
        }

        return (string)h($item->getLabel());
    }

    /**
     * Formats an array of attributes as a string of HTML attributes.
     *
     * @param array $attributes The attributes to format.
     * @param ItemInterface $item The menu item that defined the attributes.
     * @return string A formatted string of HTML attributes.
     */
    protected function _formatAttributes(array $attributes, ItemInterface $item): string
    {
        if (!empty($attributes)) {
            return $this->templater()->formatAttributes(
                $attributes + ['escape' => $item->getExtra('escape', true)]
            );
        }

        return '';
    }
}
