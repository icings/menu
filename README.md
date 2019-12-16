# Menu

[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-coverage]][link-coverage]
[![Latest Version][ico-version]][link-version]
[![Software License][ico-license]][link-license]

[ico-travis]: https://img.shields.io/travis/icings/menu/master.svg?style=flat-square
[ico-coverage]: https://img.shields.io/codecov/c/github/icings/menu.svg?style=flat-square
[ico-version]: https://img.shields.io/packagist/v/icings/menu.svg?style=flat-square&label=latest
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square

[link-travis]: https://travis-ci.org/icings/menu
[link-coverage]: https://codecov.io/github/icings/menu
[link-version]: https://packagist.org/packages/icings/menu
[link-license]: LICENSE.txt

A [KnpMenu](https://github.com/KnpLabs/KnpMenu) seasoned plugin that assists with creating menus for your
[CakePHP](https://cakephp.org) applications.


## Requirements

* CakePHP 4.0+ (use the [3.x branch](https://github.com/icings/menu/tree/3.x) of this plugin if you need CakePHP 3
  compatibility)
* KnpMenu 3.0+


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


## Usage

Detailed usage documentation can be found in the [docs](docs/index.md) folder. For those that are familiar with CakePHP
and KnpMenu, here's two examples for a quick start.

### Via the Menu helper

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


## Issues

Please use the [issue tracker](https://github.com/icings/menu/issues) to report problems.