# Menu plugin documentation

- [Installation](#installation)
- [Basic usage](#basic-usage)
- [Configuration](#configuration)
  - [Helper configuration](#helper-configuration)
  - [Menu configuration](#menu-configuration)
  - [Menu item configuration](#menu-item-configuration)
  - [Render configuration](#render-configuration)
- [Creating menus](#creating-menus)
- [Adding menu items](#adding-menu-items)
- [Nesting menu items](#nesting-menu-items)
- [Adding attributes](#adding-attributes)
- [Inheriting/Consuming auto-generated item classes](#inheritingconsuming-auto-generated-item-classes)
- [Changing the default HTML output](#changing-the-default-html-output)
  - [Changing the defaults for an item and its children](#changing-the-defaults-for-an-item-and-its-children)
- [Determining the active item](#determining-the-active-item)
  - [Matching against multiple URLs](#matching-against-multiple-urls)
  - [Do not match against the URL that generates the link](#do-not-match-against-the-url-that-generates-the-link)
  - [Using fuzzy route matching](#using-fuzzy-route-matching)
  - [Matching and query strings](#matching-and-query-strings)
- [Retrieving the active menu item](#retrieving-the-active-menu-item)
- [Extracting a path of menu items](#extracting-a-path-of-menu-items)
- [Advanced usage](#advanced-usage)
  - [Defining a matcher](#defining-a-matcher)
  - [Defining voters](#defining-voters)
    - [Voters for specific menu items only](#voters-for-specific-menu-items-only)
  - [Defining a renderer](#defining-a-renderer)
  - [Using the library directly](#using-the-library-directly)
- [Examples](#examples)


## Installation

1. Use [Composer](http://getcomposer.org) to add the menu plugin to your project:

   ```bash
   $ composer require icings/menu
   ```

2. Make sure that you are loading the plugin in your bootstrap, either run:

   ```bash
   $ bin/cake plugin load Icings/Menu
   ```

   or add the following call to your `Application` class' `bootstrap()` method in the `src/Application.php` file:

   ```php
   $this->addPlugin('Icings/Menu');
   ```

3. Load the helper in your `AppView` class' `initialize()` method, located in the `src/View/AppView.php` file:

   ```php
   $this->loadHelper('Icings/Menu.Menu');
   ```


## Basic usage

Build and render the menu via the helpers `create()` and `render()` methods:

```php
$menu = $this->Menu->create('main');
$menu->addChild('Home', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'home']]);
$menu->addChild('About', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'about']]);

$menu->addChild('Services', ['uri' => '#']);
$menu['Services']->addChild('Research', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'research']]);
$menu['Services']->addChild('Security', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'security']]);

$menu->addChild('Contact', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'contact']]);

echo $this->Menu->render();
```

In the default setup, this would generate the following HTML:

```html
<ul>
    <li>
        <a href="/pages/display/home">Home</a>
    </li>
    <li>
        <a href="/pages/display/about">About</a>
    </li>
    <li class="has-dropdown">
        <a href="#">Services</a>
        <ul class="dropdown">
            <li>
                <a href="/pages/display/research">Research</a>
            </li>
            <li>
                <a href="/pages/display/security">Security</a>
            </li>
        </ul>
    </li>
    <li>
        <a href="/pages/display/contact">Contact</a>
    </li>
</ul>
```


## Configuration

There are basically four levels of configuration:

1. [Helper configuration](#helper-configuration)
2. [Render configuration](#render-configuration)
3. [Menu configuration](#menu-configuration)
4. [Menu item configuration](#menu-item-configuration)

They are weighted in that exact order, which means that menu item configuration overwrites menu configuration, menu
configuration overwrites render configuration, and render configuration overwrites helper configuration.

### Helper configuration

The menu helper can be configured like any other CakePHP helper, either via the `$options` argument of
`View::loadHelper()`:

```php
$this->loadHelper('Icings/Menu.Menu', [
    'option' => 'value'
]);
```

or via `Helper::setConfig()`:

```php
$this->Menu->setConfig([
    'option' => 'value'
]);
```

#### Menu helper options

The following options also apply to `MenuHelper::render()`, and partially to `MenuHelper::create()`. Defining them in
the scope of the helper will make them the default values that can be overwritten in `MenuHelper::create()` and
`MenuHelper::render()` if necessary.

For more advanced configuration that allows to change the internally used matcher, voters or renderer, please refer to
the [Advanced usage](#advanced-usage) section.

##### Matching related options

- `matching` (`string`, defaults to `\Icings\Menu\View\Helper\MenuHelper::MATCH_URL`)  
  Defines the mode to use for matching the menu items against the current request in order to determine the active
  items.

##### Rendering related options

- `templates` (`string[]`)  
  The templates that should be used. Defaults to:
  ```php
  [
      'menu' => '<ul{{attrs}}>{{items}}</ul>',
      'nest' => '<ul{{attrs}}>{{items}}</ul>',
      'item' => '<li{{attrs}}>{{link}}{{nest}}</li>',
      'link' => '<a href="{{url}}"{{attrs}}>{{label}}</a>',
      'text' => '<span{{attrs}}>{{label}}</span>'
  ]
  ```

- `templateVars` (`mixed[]|null`, defaults to `null`)  
  An array of template variables.

- `currentClass` (`string`, defaults to `active`)  
  The class to set for the current/active items.

- `ancestorClass` (`string|null`, defaults to `active-ancestor`)  
  The class to set for ancestors of current/active items.

- `leafClass` (`string|null`, defaults to `null`)  
  The class to set for menu items that do not have children.

- `branchClass` (`string|null`, defaults to `has-dropdown`)  
  The class to set for menu items that do have children.

- `nestedMenuClass` (`string|null`, defaults to `dropdown`)  
  The class to set for the element that holds the children of a menu item.

- `menuLevelClass` (`string|null`, defaults to `null`)  
  The class prefix to use for denoting the level of a menu. The appended level is an integer starting at `1`.

- `firstClass` (`string|null`, defaults to `null`)  
  The class to set for the first child item.

- `lastClass` (`string|null`, defaults to `null`)  
  The class to set for the last child item.

- `depth` (`integer|null`, defaults to `null`)  
  The depth up to which the menu should be rendered.

- `matchingDepth` (`integer|null`, defaults to `null`)  
  The depth up to which items should be matched.

- `clearMatcher` (`boolean`, defaults to `true`)  
  Defines whether the matcher cache should be cleared after rendering the menu.

- `currentAsLink` (`boolean`, defaults to `true`)  
  Whether the active item should render a link, or a text element.

- `inheritItemClasses` (`array|boolean|null`, defaults to `null`)  
  Defines which classes should be inherited by the menu item's link and text elements. `true` will cause all classes to
  be inherited. An array is used to specify specific classes to inherit (valid class names are `currentClass`,
  `ancestorClass`, `leafClass`, `branchClass`, `firstClass`, `lastClass`). `null` or `false` will disable this feature.

- `consumeItemClasses` (`array|boolean|null`, defaults to `null`)  
  Defines which classes should be consumed by the menu item's link and text elements. `true` will cause all classes to
  be consumed. An array is used to specify specific classes to consume (see the `inheritItemClasses` option for a list
  of valid class names).  `null` or `false` will disable this feature.

### Render configuration

Rendering can be configured using the `$options` argument of `MenuHelper::render()`. It supports the exact same options
as the helper itself, and can be used to overwrite the helpers defaults.

### Menu configuration

Menus can be configured via the `$options` argument of `MenuHelper::create()`.

#### Menu options

In the default setup, the following options are supported:

- `menuAttributes` (`array`, defaults to `null`)
  The HTML attributes to apply to the menu element.

Additionally to the options listed above, all [rendering related options](#rendering-related-options) options of the
helper are supported too, and can be used to overwrite the helpers defaults.

### Menu item configuration

Menu items can be configured via the `$options` argument of `ItemInterface::addChild()`.

#### Menu item options

In the default setup, the following options are supported:

- `uri` (`array|string|null`, defaults to `null`)  
  Either a CakePHP URL array, or a string URL. This value will be used for generating the link URL of the menu item,
  as well as for matching against the current request target, in order to determine whether the current item is active.
  
  What kind of matching is being used depends on the configured matcher and voters. In the default setup the generated
  string URL will be used for matching, where the query string will be ignored.
  
  If this options is omitted or `null`, no link will be generated, but a text label.

- `routes` (`array|null`, defaults to `null`)  
  An array of CakePHP URL arrays or string URLs, which will be used for matching additionally to the one set for the
  `uri` option. 

- `addUriToRoutes` (`boolean`, defaults to `true`)  
  Defines whether the URL defined in the `uri` option should be added to the `routes` option. If set to `false`, only
  the URLs defined in the `routes` option will be used for matching. When `true`, the URL defined in `uri` will be
  matched first before the URLs defined in the `routes` option.

- `ignoreQueryString` (`boolean|null`, defaults to `null`)  
  Defines whether the query string should be ignored in the matching process. When `null`, the matching configuration
  (respectively the voter) will decide the behavior.
  
  This option only applies to the `UrlVoter`, respectively the `MenuHelper::MATCH_URL` matching mode.

- `voters` (`Knp\Menu\Matcher\Voter\VoterInterface[]|null`, defaults to `null`)  
  Defines voters that should be used for matching this menu item. The voters defined here will be used before the
  default ones.

- `templates` (`array`, defaults to `null`)  
  The templates that should be used for the menu item. For a list of available templates, refer to the
  [Configuration > Helper configuration > Menu helper options](#menu-helper-options) section.

- `defaultTemplates` (`array`, defaults to `null`)  
  The templates that should be used for the menu item _and its children_. When specifying both, `templates` as well as
  `defaultTemplates`, the latter will still apply to the current item as well, unless already specified via the former.

- `templateVars` (`array`, defaults to `null`)  
  The template variables that should be used for the menu item.

- `defaultTemplateVars` (`array`, defaults to `null`)  
  The template variables that should be used for the menu item _and its children_. When specifying both, `templateVars`
  as well as `defaultTemplateVars`, the latter will still apply to the current item as well, unless already specified
  via the former.

- `escape` (`boolean`, defaults to `true`)  
  Defines whether the attributes and the label (link and non-link text) should be escaped (passed through `h()`).

- `escapeLabel` (`boolean`, defaults to `true`) 
  Defines whether the label (link and non-link text) should be escaped (passed through `h()`).

- `label` (`string`, defaults to `null`)  
  Defines the items label. When `null`, the first argument of `addChild()` is being used as the label.
  
  There might be situations in which you need to specify different values for the items name (identifier) and its label,
  for example when you want to translate the label, and need to retain the possibility to access the item by its name
  without having to use translation functions, in such a case you should use this option to specify the label text.

- `current` (`boolean`, defaults to `null`)  
  Defines whether the item should be marked as active.

- `display` (`boolean`, defaults to `true`)  
  Defines whether the item should be displayed/rendered.

- `displayChildren` (`boolean`, defaults to `true`)  
  Defines whether the items children should be displayed/rendered.

- `attributes` (`array`, defaults to `null`)  
  The HTML attributes to apply to the menu item element.

- `linkAttributes` (`array`, defaults to `null`)  
  The HTML attributes to apply to the menu item link element.

- `textAttributes` (`array`, defaults to `null`)  
  The HTML attributes to apply to the menu item text element (that is, when no link is generated).

- `nestAttributes` (`array`, defaults to `null`)  
  The HTML attributes to apply to the element that holds the children of the menu item.

- `inheritItemClasses` (`array|boolean|null`, defaults to `null`)  
  Defines which classes should be inherited by the menu item's link and text elements. `true` will cause all classes to
  be inherited. An array is used to specify specific classes to inherit. For a list of valid classes, refer to the
  `inheritItemClasses` renderer option description in the
  [Configuration > Helper configuration > Rendering related options](#rendering-related-options) section. `false` will
  disable this feature, and `null` means that the renderer defaults will apply.

- `consumeItemClasses` (`array|boolean|null`, defaults to `null`)  
  Defines which classes should be consumed by the menu item's link and text elements. `true` will cause all classes to
  be consumed. An array is used to specify specific classes to consume. For a list of valid classes, refer to the
  `inheritItemClasses` renderer option description in the
  [Configuration > Helper configuration > Rendering related options](#rendering-related-options) section. `false` will
  disable this feature, and `null` means that the renderer defaults will apply.

## Creating menus

Menus are created via the `MenuHelper::create()` method. The method takes two arguments, the first one being the name
of the menu, which serves as an identifier when rendering or obtaining specific menus, and the second one optionally
takes an array of options that should be applied to the menu, ie. to the top level item.

The `create()` method returns an instance of `KnpMenu\ItemInterface`, so any operations known from KnpMenu that can be
applied via that interface, can be applied here too. It should be noted however that not all methods will have the 
same effect as using their option counterparts, for example unlike the `uri` option, the `setUri()` method will _not_
work with CakePHP URL arrays!

```php
$menu = $this->Menu->create('main', [
    'option' => 'value'
]);
```


## Adding menu items

Menu items are added via the `addChild()` method of the menu that was created via `MenuHelper::create()`. The method
takes two arguments, the first being the label of the menu item, the second being an optional array of options.

In the default setup, items support the `uri` option known from KnpMenu. It supports string URLs as well as CakePHP
URL arrays, which are being converted into string URLs, and are being matched against the current request target.

```php
$menu->addChild('Articles', ['uri' => ['controller' => 'Articles', 'action' => 'index']]);
```

The generated HTML would look like:

```html
<li>
    <a href="/articles">Articles</a>
</li>
```

and it would automatically be marked as active in case the current request URL matches `/articles`.

When omitting the `uri` option, the item will not render a link, but a text-label element:

```php
$menu->addChild('Label');
```

The generated HTML would look like:

```html
<li>
    <span>Label</span>
</li>
```


## Nesting menu items

Menu items can be nested by using `addChild()` on a menu item itself, either by using a variable reference:

```php
$parent = $menu->addChild('Parent', ['uri' => ['controller' => 'Controller', 'action' => 'action']]);
$parent->addChild('Child', ['uri' => ['controller' => 'Other', 'action' => 'action']]);
```

by accessing the menu item by its name:

```php
$menu->addChild('Parent', ['uri' => ['controller' => 'Controller', 'action' => 'action']]);
$menu['Parent']->addChild('Child', ['uri' => ['controller' => 'Other', 'action' => 'action']]);
```

or by using the fluid interface syntax:

```php
$menu
    ->addChild('Parent', ['uri' => ['controller' => 'Controller', 'action' => 'action']])
        ->addChild('Child', ['uri' => ['controller' => 'Other', 'action' => 'action']]);
```

The generated HTML would look like:

```html
<li class="has-dropdown">
    <a href="/controller/action">Parent</a>
    <ul class="dropdown">
        <li>  
            <a href="/other/action">Child</a>
        </li>
    </ul>
</li>
```


## Adding attributes

Attributes can be added to the menu, the individual menu items, its links, text labels, and nesting elements. The
corresponding options are `menuAttributes`, `attributes`, `linkAttributes`, `textAttributes`, and `nestAttributes`:

```php
$menu = $this->Menu->create('main', [
    'menuAttributes' => [
        'data-menu' => 'menu data'
    ]
]);
$menu->addChild('Parent', [
    'uri' => ['controller' => 'Controller', 'action' => 'action'],
    'attributes' => [
        'data-item' => 'item data'
    ],
    'linkAttributes' => [
        'data-link' => 'link data'
    ],
    'nestAttributes' => [
        'data-nest' => 'nest data'
    ]
]);
$menu['Parent']->addChild('Child', [
    'textAttributes' => [
        'data-text' => 'text data'
    ]
]);
```

In the default setup, this would generate the following HTML:

```html
<ul data-menu="menu data">
    <li data-item="item data">  
        <a data-link="link data" href="/controller/action">Parent</a>
        <ul data-nest="nest data">
            <li>
                <span data-text="text data">Child</span>
            </li>
        </ul>
    </li>
</ul>
```


## Inheriting/Consuming auto-generated item classes

In certain situations it might be required to have classes like the one for the current/active item to be set on the
`link`/`text` element, instead of the outer `item` element.

For example in the default setup, the current/active item would receive the class, and the following HTML would be
generated for the menu item:

```html
<li class="active">  
    <a href="/controller/action">Label</a>
</li>
```

In order to have the `active` class being set on the link element instead, one can use the `inheritItemClasses` or
`consumeItemClasses` option. Both options are supported as rendering options (see the
[Configuration > Helper configuration > Rendering related options](#rendering-related-options) section), or as menu item
options.

Specifying `true` will cause all auto-generated item classes to be inherited/consumed, using an array a list of specific
classes to inherit/consume can be specified (valid class names are `currentClass`, `ancestorClass`, `leafClass`,
`branchClass`, `firstClass`, `lastClass`), and `false` will disable the feature. On renderer level `null` will disable
the feature too, where as on menu item level it means that the renderer defaults should be used.

### Inheriting

"_Inheriting_" means that the classes that are being set on the item, will be set on the item's link/text elements too.
The following active menu item would generate HTML where the current class is being set on the link element too:

```php
$menu->addChild('Label', [
    'uri' => ['controller' => 'Controller', 'action' => 'action'],
    'inheritItemClasses' => [
        'currentClass'
    ]
]);
```

```html
<li class="active">  
    <a href="/controller/action" class="active">Label</a>
</li>
```

### Consuming

"_Consuming_" means that the classes are being set on the item's link/text elements _instead_ of on the item. The
following active menu item would generate HTML where the current class is being set on the link element only:

```php
$menu->addChild('Label', [
    'uri' => ['controller' => 'Controller', 'action' => 'action'],
    'consumeItemClasses' => [
        'currentClass'
    ]
]);
```

```html
<li>  
    <a href="/controller/action" class="active">Label</a>
</li>
```


## Changing the default HTML output

In the default setup, the helper uses a string template renderer, which utilizes string templates as known from the
CakePHP core helpers that can be customized via the `templates` and `templateVars` options.

Changing the default output is fairly simple, just pass the `templates` and/or `templateVars` options to either
`MenuHelper::setConfig()`, in order to change the defaults for all menus:

```php
$this->Menu->setConfig([
    'templates' => [
        'menu' => '<nav class="menu-wrapper"><ul{{attrs}}>{{items}}</ul></nav>',
        'item' => '<li data-item="{{itemVar}}"{{attrs}}>{{link}}{{nest}}</li>'
    ],
    'templateVars' => [
        'itemVar' => 'default item data'
    ]
]);
```

or pass them to `MenuHelper::create()` or `ItemInterface::addChild()`, to change the defaults for specific menus/items
only:

```php
$menu = $this->Menu->create('main', [
    'templates' => [
        'menu' => '<nav class="menu-wrapper"><ul{{attrs}}>{{items}}</ul></nav>',
        'item' => '<li data-item="{{itemVar}}"{{attrs}}>{{link}}{{nest}}</li>'
    ],
    'templateVars' => [
        'itemVar' => 'default item data'
    ]
]);
```

That would render a `<nav>` element around the menu, and it defines a `data-item` attribute for all items, which can be
fed via the custom template variable `itemVar`, and is set to a default value of `default item data`.

The generated HTML would look like:

```html
<nav class="menu-wrapper">
    <ul>
        <li data-item="default item data">...</li>
        ...
    </ul>
</nav>
```

Configuring the menu items is equally simple:

```php
$menu->addChild('Label', [
    'uri' => ['controller' => 'Controller', 'action' => 'action'],
    'templates' => [
        'link' => '<a href="{{url}}"{{attrs}}><i class="fa fa-check-square"></i> {{label}}</a>'
    ],
    'templateVars' => [
        'itemVar' => 'specific item data'
    ]
]);
```

This would render the link of this specific menu item with an additional `<i>` element, and the `data-item` attribute
with a value of `specific item data`.

The generated HTML would look like:

```html
<li data-item="specific item data">
    <a href="/controller/action"><i class="fa fa-check-square"></i> Label</a>
</li>
```

### Changing the defaults for an item and its children

Templates and template variables that are defined on a specific menu item, will apply these options to only that
specific item. If you want to change the menu defaults for a whole branch of items (ie child items), you can use the
`defaultTemplates` and `defaultTemplatVars` options.

The templates and template variables defined via these options will apply to the current item (unless already specified
via `templates` and `templateVars`), as well as all child items of the current item.

```php
$menu->addChild('Parent', [
    'uri' => ['controller' => 'Controller', 'action' => 'action'],
    'templates' => [
        'link' => '<a href="{{url}}"{{attrs}}><i class="fa fa-check-square"></i> {{label}}</a>'
    ],
    'templateVars' => [
        'itemVar' => 'specific item data'
    ]
]);
$menu['Parent']->addChild('Child', [
    'uri' => ['controller' => 'Controller', 'action' => 'action']
]);
$menu['Parent']['Child']->addChild('Grandchild', [
    'uri' => ['controller' => 'Controller', 'action' => 'action'],
    'defaultTemplates' => [
        'item' => '<li data-item-new-defaults="{{itemVar}}"{{attrs}}>{{link}}{{nest}}</li>'
    ],
    'defaultTemplateVars' => [
        'itemVar' => 'new item default data'
    ]
]);
$menu['Parent']['Child']['Grandchild']->addChild('GreatGrandchild', [
    'uri' => ['controller' => 'Controller', 'action' => 'action']
]);
```

The generated HTML would look like:

```html
<li data-item="specific item data">
    <a href="/controller/action"><i class="fa fa-check-square"></i> Parent</a>
    <ul>
        <li data-item="default item data">
            <a href="/controller/action">Child</a>
            <ul>
                <li data-item-new-defaults="new item default data">
                    <a href="/controller/action">Grandchild</a>
                    <ul>
                        <li data-item-new-defaults="new item default data">
                            <a href="/controller/action">GreatGrandchild</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>
</li>
```


## Determining the active item

In the default setup, the helper will compare the URLs generated for the menu items `uri` and `routes` options against
the current request URL, while ignoring the query string.

There are three matching modes that can be configured via the `matching` option:

- `Icings\Menu\View\Helper\MenuHelper::MATCH_URL` (default)
- `Icings\Menu\View\Helper\MenuHelper::MATCH_URL_WITH_QUERY_STRING`
- `Icings\Menu\View\Helper\MenuHelper::MATCH_FUZZY_ROUTE`

For example, if you want to change the matching to include the query string, set the `MATCH_URL_WITH_QUERY_STRING` mode
like this:

```php
use Icings\Menu\View\Helper\MenuHelper;

$this->Menu->setConfig([
    'matching' => MenuHelper::MATCH_URL_WITH_QUERY_STRING
]);
```

### Matching against multiple URLs

If you want to match not only against the URL defined in the menu items `uri` option, you can add additional URLs to
match against in the `routes` option, which takes an array of URL arrays or string URLs.

This is supported by all matching modes.

### Do not match against the URL that generates the link

In case you want to only match against the URLs provided in the `routes` option, and use the URL defined via the `uri`
option solely for generating the menu items link, you can set the `addUriToRoutes` option to `false`. Doing so will
exclude the primary URL from matching.

This is supported by all matching modes.

### Using fuzzy route matching

There might be times when you want your menu items to match multiple URLs, but you do not want to define them all one
by one. This is where fuzzy URL matching comes into play. Fuzzy route matching compares the parsed parameters of the
route that matched the current request, against the URL arrays provided via the `uri` and `routes` options.

Fuzzy route matching can be enabled by setting the `matching` option to `MATCH_FUZZY_ROUTE`:

```php
use Icings\Menu\View\Helper\MenuHelper;

$this->Menu->setConfig([
    'matching' => MenuHelper::MATCH_FUZZY_ROUTE
]);
```

The matching is fuzzy in the sense that a match is successful when all of the parameters provided in the URL array
are present in the parsed route parameters, so if the URL array for example only contains a controller, then it will be
matched successfully for all routes that connect to this controller, regardless of the action, the plugin, the prefix,
further parameters like IDs, etc.

Consider the following URL array, which is as fuzzy as it gets:

```php
['controller' => 'Articles']
```

This will successfully match all of the following (and more) routes:

```php
$routes->plugin('PluginName', function(RouteBuilder $routes) {
    $routes->fallbacks();
});
$routes->prefix('PrefixName', function(RouteBuilder $routes) {
    $routes->fallbacks();
});
$routes->connect('/:controller/:action/:id');
$routes->connect('/:controller/:action/:id');
$routes->connect('/:controller/:action');
$routes->connect('/:controller');
```

The matching can be made less fuzzy by specifying more (restrictive) parameters.

#### Adding an action:

```php
['controller' => 'Articles', 'action' => 'view']
```

From the above routes examples, now the following route will not be matched anymore, as it by default connects to the
`index` action:

```php
$routes->connect('/:controller');
```

#### Specifying an ID:

```php
['controller' => 'Articles', 'action' => 'view', 1]
```

From the above routes examples, now the following routes will not be matched anymore, as they do not define the
additional ID:

```php
$routes->connect('/:controller/:action');
$routes->connect('/:controller');
```

#### Specifying a plugin:

```php
['plugin' => 'PluginName', 'controller' => 'Articles']
```

From the above routes examples, now only the plugin routes will be matched:

```php
$routes->plugin('PluginName', function(RouteBuilder $routes) {
    $routes->fallbacks();
});
```

#### Specifying a prefix:

```php
['prefix' => 'PrefixName', 'controller' => 'Articles']
```

From the above routes examples, now only the prefix routes will be matched:

```php
$routes->prefix('PrefixName', function(RouteBuilder $routes) {
    $routes->fallbacks();
});
```

#### Fuzzy matching named routes

**Please note** that this feature requires at least CakePHP 3.5.11!

Named routes that contain elements do match in a similar fuzzy manner as the routes discussed previously, that is,
unlike with strict matching, the elements defined in the route do not need to be present.

Consider a route like this:

```php
$routes->connect(
    '/articles/view/:id/:slug',
    [
        'controller' => 'Articles',
        'action' => 'view'
    ],
    [
        'pass' => ['id', 'slug'],
        '_name' => 'routeName'
    ]
);
```

In order to match it strictly via its name, the URL array would need to contain the name, and all the elements:

```php
['_name' => 'routeName', 'id' => 123, 'slug' => 'the-article-slug']
```

With fuzzy route matching, matching will work with providing only the name:

```php
['_name' => 'routeName']
```

and with providing partial elements:

```php
['_name' => 'routeName', 'id' => 123]
```

### Matching and query strings

As mentioned initially, in the default setup possible query strings are excluded from matching, which means a URL with
query arguments can match regardless of the query string values that might be present in the request URL.

If for example the current request URL is:

```
/articles?filter=all
```

and the menu has an item defined like this:

```php
$menu->addChild('Item', ['uri' => ['controller' => 'Articles', 'action' => 'index']]);
```

then this item will match, and it will be set as the current item, even though it has no query arguments defined.
Likewise an item which _has_ query arguments defined would also match, even if the query arguments are different:

```php
$menu->addChild('Item', ['uri' => ['controller' => 'Articles', 'action' => 'index', 'filter' => 'active']]);
```

In order to change this behavior, you can change the default matching type via the `matching` option to
`MATCH_URL_WITH_QUERY_STRING`, or use fuzzy route matching, ie `MATCH_FUZZY_ROUTE` (refer to the next section for more
information):

```php
use Icings\Menu\View\Helper\MenuHelper;

$this->Menu->setConfig([
    'matching' => MenuHelper::MATCH_URL_WITH_QUERY_STRING,
]);
```

#### Fuzzy route matching and query strings

Even though query strings are generally not part of the route matching process in CakePHP, fuzzy route matching does
support it (in a fuzzy manner of course).

If the URLs of a menu item define query arguments, then they will be included in the matching process, and all query
values defined in the URL arrays must be present in order for a match to succeed. A match will be successful too if the
current request has further query arguments, additionally to the ones defined in the URL arrays of the menu item.

Imagine the following URL array for the menu item:

```php
['controller' => 'Articles', 'action' => 'index', 'filter' => 'all']
```

the following route:

```php
$routes->connect('/:controller/:action');
```

and the following request URL:

```
/articles?filter=all
```

The menu item with the above URL array will match in that situation. It will also match when providing the query values
via the special `?` key:

```php
['controller' => 'Articles', 'action' => 'index', '?' => ['filter' => 'all']]
```

and also when no query is provided at all:

```php
['controller' => 'Articles', 'action' => 'index']
```

And when won't it match? It won't match when the keys/values are different. The following URL arrays wouldn't match:

```php
['controller' => 'Articles', 'action' => 'index', 'filter' => 'active']
```
```php
['controller' => 'Articles', 'action' => 'index', 'other' => 'value']
```
```php
['controller' => 'Articles', 'action' => 'index', 'filter' => 'all', 'other' => 'value']
```


## Retrieving the active menu item

You can retrieve the current active item using the menu helper's `MenuHelper::getCurrentItem()` method, it takes an
optional menu instance/name (from which to retrieve the active item) as the first argument, and options as the second
argument. The method will return the first active item in the menu, or `null` if no active item was found.

The following options are available for retrieving the active item:

- `matching` (`string`, defaults to
  `\Icings\Menu\View\Helper\MenuHelper::MATCH_URL`)
  Defines the mode to use for matching the menu items against the current request in order
  to determine the active items. This is shorthand for passing a constructed matcher object
  via the `matcher` option.

- `matcher` (`Icings\Menu\Matcher\MatcherInterface`, defaults to
  `Icings\Menu\Matcher\Matcher`)
  The matcher object to use.

- `voters` (`Knp\Menu\Matcher\Voter\VoterInterface[]`, defaults to
  `[Icings\Menu\Matcher\Voter\FuzzyRouteVoter]`)
  The voter objects to use.

- `clearMatcher` (`boolean`, defaults to `true`)
  Defines whether the matcher cache should be cleared after searching through the menu.

It should be noted that unless you are using the same matcher for retrieving the current item and for rendering (with
`clearMatcher` disabled), the process for determining/matching the active item will run twice, once when using this
method, and again when rendering the menu!

Also note that similar to the `MenuHelper::render()` method, this method will use the helper defaults for the options if
not specified.

Retrieve the active item from the menu with the name `main`:

```php
$currentItem = $this->Menu->getCurrentItem('main');
```

Retrieve the active item from the given menu instance:

```php
$currentItem = $this->Menu->getCurrentItem($menu);
```

Retrieve the active item from the last created menu:

```php
$currentItem = $this->Menu->getCurrentItem();
```


## Extracting a path of menu items

There are situations where you need to create new menus from existing menus, for example a breadcrumb navigation based
on the main menu, this is where the ability to extract paths from a menu is useful.

Paths can be extracted using the menu helper's `MenuHelper::extractPath()` method, it takes a menu item instance (for
which to extract the path) as the first argument, and options as the second argument. The method will return an array of
menu item instances, starting at the root, up to and including the given item.

The extracted items will be clones of the original items, with their respective parent and child items removed, so that
they represent a flat list of menu items. The original item (which will include the references to its parent and child
items) is attached as an extra with the key `original`, so it can be retrieved via `$item->getExtra('original')`.

The following options are available for extracting paths:

- `includeRoot` (`bool`, defaults to `false`)
  Defines whether to include the root element in the returned path. The root element, ie
  the top most element in a menu is usually the menu itself, not an actual menu item that
  is being rendered and has a URL assigned for matching, hence it is by default excluded.

Here's an example for extracting a path and creating a breadcrumb navigation from it:

```php
// create main menu
$mainMenu = $this->Menu->create('main');
$mainMenu->addChild('Library', ['uri' => ['controller' => 'Library', 'action' => 'index']]);
$mainMenu['Library']->addChild('Web', ['uri' => ['controller' => 'Web', 'action' => 'index']]);
$mainMenu['Library']['Web']->addChild('Data', ['uri' => ['controller' => 'Data', 'action' => 'index']]);
$mainMenu->addChild('Projects', ['uri' => ['controller' => 'Projects', 'action' => 'index']]);
$mainMenu->addChild('Settings', ['uri' => ['controller' => 'Settings', 'action' => 'index']]);

// extract path based on the current/active item
$crumbs = [];
$currentItem = $this->Menu->getCurrentItem('main');
if ($currentItem) {
    $crumbs = $this->Menu->extractPath($currentItem);
}

// create new menu
$crumbsMenu = $this->Menu->create('breadcrumbs', [
    'templates' => [
        'menu' => '<ol class="breadcrumb"{{attrs}}>{{items}}</ol>',
    ],
    'currentAsLink' => false,
]);

$crumbsMenu->addChild('Home', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'home']]);
// add extracted items to the new menu
foreach ($crumbs as $crumb) {
    $crumbsMenu->addChild($crumb);
}
```

Assuming the `Data` item is the current item, the above example would render the following HTML for the main menu and
the breadcrumbs menu:

```html
<ul>
    <li class="active-ancestor has-dropdown">
        <a href="/library">Library</a>
        <ul class="dropdown">
            <li class="active-ancestor has-dropdown">
                <a href="/web">Web</a>
                <ul class="dropdown">
                    <li class="active">
                        <a href="/data">Data</a>
                    </li>
                </ul>
            </li>
        </ul>
    </li>
    <li>
        <a href="/projects">Projects</a>
    </li>
    <li>
        <a href="/settings">Settings</a>
    </li>
</ul>
```

```html
<ol class="breadcrumb">
    <li>
        <a href="/">Home</a>
    </li>
    <li>
        <a href="/library">Library</a>
    </li>
    <li>
        <a href="/web">Web</a>
    </li>
    <li class="active">
        <span>Data</span>
    </li>
</ol>
```

## Advanced usage

The menu helper is designed to abstract creating a matcher, voters, and a renderer, but if required it's possible to
hook in custom objects.

The following options are available for `MenuHelper::setConfig()` and `MenuHelper::render()`:

- `matcher` (`Icings\Menu\Matcher\MatcherInterface`, defaults to `Icings\Menu\Matcher\Matcher`)  
  The matcher object to use.

- `voters` (`Knp\Menu\Matcher\Voter\VoterInterface[]`, defaults to `[Icings\Menu\Matcher\Voter\UrlVoter]`)  
  The voter objects to use.

- `renderer` (`Knp\Menu\Renderer\RendererInterface`, defaults to `Icings\Menu\Renderer\StringTemplateRenderer`)  
  The renderer object to use.

### Defining a matcher

```php
$this->Menu->setConfig([
    'matcher' => new CustomMatcher()
]);
```

### Defining voters

```php
$this->Menu->setConfig([
    'voters' => [
        new CustomVoter(),
        new OtherCustomVoter()
    ]
]);
```

#### Voters for specific menu items only

Additionally to defining multiple voters for the helper/menu, it's also possible to define voters per item via the
`voters` option:

```php
use Icings\Menu\Matcher\Voter\FuzzyRouteVoter;

$menu->addChild('Label', [
    'uri' => ['controller' => 'Controller', 'action' => 'action'],
    'voters' => [
        new FuzzyRouteVoter($this->request),
        new CustomVoter()
    ]
]);
```

The voters defined on an item will be tested _before_ the voters defined in the helper/render configuration.

### Defining a renderer

```php
use Icings\Menu\Matcher\Matcher;

$this->Menu->setConfig([
    'renderer' => [
        new CustomRenderer($matcher)
    ]
]);
```

### Using the library directly

Aside from using the menu helper and its various configuration possibilities, it's also possible to manually utilize
the library provided by this plugin, optionally combining things with the KnpMenu library:

```php
use Icings\Menu\Integration\PerItemVotersExtension;
use Icings\Menu\Integration\RoutingExtension;
use Icings\Menu\Integration\TemplaterExtension;
use Icings\Menu\Matcher\Matcher;
use Icings\Menu\Matcher\Voter\UrlVoter;
use Icings\Menu\MenuFactory;
use Icings\Menu\Renderer\StringTemplateRenderer;

$factory = new MenuFactory();
$factory->addExtension(new RoutingExtension());
$factory->addExtension(new PerItemVotersExtension());
$factory->addExtension(new TemplaterExtension());

$menu = $factory->createItem('main');
$menu->addChild('Home', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'home']]);
$menu->addChild('About', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'about']]);
$menu->addChild('Services', ['uri' => '#']);
$menu['Services']->addChild('Research', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'research']]);
$menu['Services']->addChild('Security', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'security']]);
$menu->addChild('Contact', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'contact']]);

$matcher = new Matcher();
$matcher->addVoter(new UrlVoter($this->request));

$renderer = new StringTemplateRenderer($matcher);
echo $renderer->render($menu);
```


## Examples

### Foundation 5: Divided side navigation

```php
$menu = $this->Menu->create('main', [
    'menuAttributes' => [
        'class' => 'side-nav',
    ],
]);
$menu->addChild('Articles', ['uri' => ['controller' => 'Articles']]);
$menu->addChild('Comments', ['uri' => ['controller' => 'Comments']]);
$menu->addChild('Divider', [
    'templates' => ['text' => ''],
    'attributes' => ['class' => 'divider'],
]);
$menu->addChild('Tags', ['uri' => ['controller' => 'Tags']]);
$menu->addChild('News', ['uri' => ['controller' => 'News']]);
```

```html
<ul class="side-nav">
    <li>
        <a href="/articles">Articles</a>
    </li>
    <li>
        <a href="/comments">Comments</a>
    </li>
    <li class="divider"></li>
    <li>
        <a href="/tags">Tags</a>
    </li>
    <li>
        <a href="/news">News</a>
    </li>
</ul>
```

### Foundation 5: Sub navigation

```php
use Icings\Menu\View\Helper\MenuHelper;

$this->Menu->setConfig([
    'matching' => MenuHelper::MATCH_URL_WITH_QUERY_STRING,
]);

$menu = $this->Menu->create('main', [
    'templates' => [
        'menu' => '<dl{{attrs}}><dt>Filter:</dt>{{items}}</dl>',
        'item' => '<dd{{attrs}}>{{link}}</dd>',
    ],
    'menuAttributes' => [
        'class' => 'sub-nav',
    ],
]);
$menu->addChild('All', ['uri' => ['controller' => 'Articles', 'action' => 'index', 'filter' => 'all']]);
$menu->addChild('Active', ['uri' => ['controller' => 'Articles', 'action' => 'index', 'filter' => 'active']]);
$menu->addChild('Pending', ['uri' => ['controller' => 'Articles', 'action' => 'index', 'filter' => 'pending']]);
$menu->addChild('Suspended', ['uri' => ['controller' => 'Articles', 'action' => 'index', 'filter' => 'suspended']]);
```

```html
<dl class="sub-nav">
    <dt>Filter:</dt>
    <dd>
        <a href="/articles?filter=all">All</a>
    </dd>
    <dd>
        <a href="/articles?filter=active">Active</a>
    </dd>
    <dd>
        <a href="/articles?filter=pending">Pending</a>
    </dd>
    <dd>
        <a href="/articles?filter=suspended">Suspended</a>
    </dd>
</dl>
```

### Foundation 6: Dropdown menu

```php
$menu = $this->Menu->create('main', [
    'menuAttributes' => [
        'class' => 'dropdown menu',
        'data-dropdown-menu',
    ], 
    'nestedMenuClass' => 'menu',
    'branchClass' => 'is-dropdown-submenu-parent',
]);
$menu->addChild('Articles', ['uri' => ['controller' => 'Articles']]);
$menu['Articles']->addChild('Comments', ['uri' => ['controller' => 'Comments']]);
$menu['Articles']->addChild('Tags', ['uri' => ['controller' => 'Tags']]);
$menu->addChild('News', ['uri' => ['controller' => 'News']]);
$menu->addChild('Status', ['uri' => ['controller' => 'Pages', 'action' => 'display', 'status']]);
```

```html
<ul class="dropdown menu" data-dropdown-menu="data-dropdown-menu">
    <li class="is-dropdown-submenu-parent">
        <a href="/articles">Articles</a>
        <ul class="menu">
            <li>
                <a href="/comments">Comments</a>
            </li>
            <li>
                <a href="/tags">Tags</a>
            </li>
        </ul>
    </li>
    <li>
        <a href="/news">News</a>
    </li>
    <li>
        <a href="/pages/display/status">Status</a>
    </li>
</ul>
```

### Foundation 6: Nested vertical icon menu

```php
$menu = $this->Menu->create('main', [
    'templates' => [
        'link' => '<a href="{{url}}"{{attrs}}><i class="{{icon}}"></i> <span>{{label}}</span></a>',
    ],
    'menuAttributes' => [
        'class' => 'vertical menu',
    ], 
    'nestedMenuClass' => 'nested vertical menu',
    'branchClass' => null,
]);
$menu->addChild('Articles', [
    'uri' => ['controller' => 'Articles'],
    'templateVars' => ['icon' => 'fi-book'],
]);
$menu['Articles']->addChild('Comments', [
    'uri' => ['controller' => 'Comments'],
    'templateVars' => ['icon' => 'fi-comments'],
]);
$menu['Articles']->addChild('Tags', [
    'uri' => ['controller' => 'Tags'],
    'templateVars' => ['icon' => 'fi-pricetag-multiple'],
]);
$menu->addChild('News', [
    'uri' => ['controller' => 'News'],
    'templateVars' => ['icon' => 'fi-rss'],
]);
$menu->addChild('Status', [
    'uri' => ['controller' => 'Pages', 'action' => 'display', 'status'],
    'templateVars' => ['icon' => 'fi-shield'],
]);
```

```html
<ul class="vertical menu">
    <li>
        <a href="/articles"><i class="fi-book"></i> <span>Articles</span></a>
        <ul class="nested vertical menu">
            <li>
                <a href="/comments"><i class="fi-comments"></i> <span>Comments</span></a>
            </li>
            <li>
                <a href="/tags"><i class="fi-pricetag-multiple"></i> <span>Tags</span></a>
            </li>
        </ul>
    </li>
    <li>
        <a href="/news"><i class="fi-rss"></i> <span>News</span></a>
    </li>
    <li>
        <a href="/pages/display/status"><i class="fi-shield"></i> <span>Status</span></a>
    </li>
</ul>
```

### Bootstrap 3: Navbar menu with divided dropdown

```php
$menu = $this->Menu->create('main', [
    'menuAttributes' => [
        'class' => 'nav navbar-nav',
    ],
    'nestedMenuClass' => 'dropdown-menu',
    'branchClass' => 'dropdown',
]);
$menu->addChild('Page A', [
    'uri' => ['controller' => 'Pages', 'action' => 'display', 'page-a'],
]);
$menu->addChild('Dropdown', [
    'uri' => '#',
    'templates' => [
        'link' => '<a href="{{url}}"{{attrs}}>{{label}} <span class="caret"></span></a>',
    ],
    'linkAttributes' => [
        'class' => 'dropdown-toggle',
        'data-toggle' => 'dropdown',
        'role' => 'button',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
    ]
]);
$menu['Dropdown']->addChild('Page B', [
    'uri' => ['controller' => 'Pages', 'action' => 'display', 'page-b'],
]);
$menu['Dropdown']->addChild('Divider', [
    'templates' => ['text' => ''],
    'attributes' => ['role' => 'separator', 'class' => 'divider'],
]);
$menu['Dropdown']->addChild('Page C', [
    'uri' => ['controller' => 'Pages', 'action' => 'display', 'page-c'],
]);
$menu->addChild('Page D', [
    'uri' => ['controller' => 'Pages', 'action' => 'display', 'page-d'],
]);
```

```html
<ul class="nav navbar-nav">
    <li>
        <a href="/pages/display/page-a">Page A</a>
    </li>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown"
           role="button" aria-haspopup="true" aria-expanded="false">Dropdown <span class="caret"></span></a>
        <ul class="dropdown-menu">
            <li>
                <a href="/pages/display/page-b">Page B</a>
            </li>
            <li role="separator" class="divider"></li>
            <li>
                <a href="/pages/display/page-c">Page C</a>
            </li>
        </ul>
    </li>
    <li>
        <a href="/pages/display/page-d">Page D</a>
    </li>
</ul>
```
