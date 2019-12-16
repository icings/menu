<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Renderer;

use Icings\Menu\Integration\PerItemVotersExtension;
use Icings\Menu\Integration\RoutingExtension;
use Icings\Menu\Integration\TemplaterExtension;
use Icings\Menu\Matcher\Matcher;
use Icings\Menu\Renderer\StringTemplateRenderer;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Knp\Menu\Renderer\RendererInterface;
use PHPUnit\Framework\MockObject\MockObject;

class StringTemplateRendererTest extends KnpAbstractRendererTest
{
    public function assertTrimmedHtml(string $expected, string $actual, string $message = ''): void
    {
        $expected = preg_split('/$\R?/m', $expected);
        array_walk($expected, function (&$value) {
            $value = trim($value);
        });
        $expected = array_filter($expected);
        $expected = implode('', $expected);

        $this->assertEquals($expected, $actual, $message);
    }

    // -----------------------------------------------------------------------------------------------------------------
    //region construct()
    // -----------------------------------------------------------------------------------------------------------------

    public function testConstructMissingRequiredArguments(): void
    {
        if (PHP_MAJOR_VERSION < 7) {
            $this->markTestSkipped();
        }

        $this->expectException(\Error::class);
        $this->expectExceptionMessageRegExp(
            '/^(Argument 1 .+? must implement interface Knp\\\\Menu\\\\Matcher\\\\MatcherInterface, none given|Too few arguments .+? at least 1 expected)/'
        );

        new StringTemplateRenderer();
    }

    public function testConstructInvalidMatcherArgumentType(): void
    {
        if (PHP_MAJOR_VERSION < 7) {
            $this->markTestSkipped();
        }

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageRegExp(
            '/^Argument 1 .+? must implement interface Knp\\\\Menu\\\\Matcher\\\\MatcherInterface, string given/'
        );

        new StringTemplateRenderer('invalid');
    }

    public function testConstructInvalidConfigArgumentType(): void
    {
        if (PHP_MAJOR_VERSION < 7) {
            $this->markTestSkipped();
        }

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageRegExp(
            '/^Argument 2 .+? must be of the type array, string given/'
        );

        new StringTemplateRenderer(new Matcher(), 'invalid');
    }

    public function testConstructWithoutConfig(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $expected = [
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
        $this->assertEquals($expected, $renderer->getConfig());
    }

    public function testConstructWithOptions(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'templates' => [
                'menu' => '{{items}}',
            ],
            'nonExistent' => 'option',
        ]);

        $expected = [
            'templates' => [
                'menu' => '{{items}}',
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
            'nonExistent' => 'option',
        ];
        $this->assertEquals($expected, $renderer->getConfig());
    }

    public function testConstructWithTemplatesFile(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'templates' => 'renderer_templates',
        ]);

        $expected = [
            'templates' => [
                'menu' => '<nav><ul{{attrs}}>{{items}}</ul></nav>',
                'nest' => '<ul data-ul{{attrs}}>{{items}}</ul>',
                'item' => '<li data-li{{attrs}}>{{link}}{{nest}}</li>',
                'link' => '<a data-a href="{{url}}"{{attrs}}>{{label}}</a>',
                'text' => '<span data-span{{attrs}}>{{label}}</span>',
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
        $this->assertEquals($expected, $renderer->getConfig());
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region render()
    // -----------------------------------------------------------------------------------------------------------------

    public function testRenderDefaults(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);
        $menu->addChild('Text');

        $expected = '
            <ul>
              <li class="active-ancestor has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active">
                    <a href="/nested">Nested</a>
                  </li>
                </ul>
              </li>
              <li>
                <span>Text</span>
              </li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderCustomTemplates(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'templates' => [
                'menu' => '<div><ul{{attrs}}>{{items}}</ul></div>',
                'nest' => '<ol{{attrs}}>{{items}}</ol>',
                'item' => '<li{{attrs}}><i>before</i>{{link}}{{nest}}</li>',
                'link' => '<a href="{{url}}"{{attrs}}>{{label}}</a><i>after</i>',
                'text' => '<p{{attrs}}>{{label}}</p>',
            ],
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested']);
        $menu->addChild('Text');

        $expected = '
            <div>
              <ul>
                <li class="has-dropdown">
                  <i>before</i>
                  <a href="/link">Link</a>
                  <i>after</i>
                  <ol class="dropdown">
                    <li>
                      <i>before</i>
                      <a href="/nested">Nested</a>
                      <i>after</i>
                    </li>
                  </ol>
                </li>
                <li>
                  <i>before</i>
                  <p>Text</p>
                </li>
              </ul>
            </div>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderCustomTemplatesPerRender(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested']);
        $menu->addChild('Text');

        $expected = '
            <div>
              <ul>
                <li data-var="default var" class="has-dropdown">
                  <i>before</i>
                  <a href="/link">Link</a>
                  <i>after</i>
                  <ol class="dropdown">
                    <li data-var="default var">
                      <i>before</i>
                      <a href="/nested">Nested</a>
                      <i>after</i>
                    </li>
                  </ol>
                </li>
                <li data-var="default var">
                  <i>before</i>
                  <p>Text</p>
                </li>
              </ul>
            </div>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu, [
            'templates' => [
                'menu' => '<div><ul{{attrs}}>{{items}}</ul></div>',
                'nest' => '<ol{{attrs}}>{{items}}</ol>',
                'item' => '<li data-var="{{someVar}}"{{attrs}}><i>before</i>{{link}}{{nest}}</li>',
                'link' => '<a href="{{url}}"{{attrs}}>{{label}}</a><i>after</i>',
                'text' => '<p{{attrs}}>{{label}}</p>',
            ],
            'templateVars' => [
                'someVar' => 'default var',
            ],
        ]));
    }

    public function testRenderCustomTemplatesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $factory = new MenuFactory();
        $factory->addExtension(new PerItemVotersExtension());
        $factory->addExtension(new RoutingExtension());
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test', [
            'templates' => [
                'menu' => '<div><ul{{attrs}}>{{items}}</ul></div>',
                'item' => '<li{{attrs}}><i data-var="{{someVar}}">before</i>{{link}}{{nest}}</li>',
            ],
            'templateVars' => [
                'someVar' => 'default var',
            ],
        ]);
        $menu->addChild('Link', [
            'uri' => '/link',
        ]);
        $menu['Link']->addChild('Nested', [
            'templates' => [
                'item' => '<li data-specific-item{{attrs}}><i data-var="{{someVar}}">before</i>{{link}}{{nest}}</li>',
            ],
            'templateVars' => [
                'someVar' => 'item var',
            ],
        ]);
        $menu['Link']['Nested']->addChild('EvenDeeper', [
            'uri' => '/even-deeper',
        ]);
        $menu->addChild('Text', [
            'templates' => [
                'text' => '<p{{attrs}}>{{label}}</p>',
            ],
        ]);

        $expected = '
            <div>
              <ul>
                <li class="has-dropdown">
                  <i data-var="default var">before</i>
                  <a href="/link">Link</a>
                  <ul class="dropdown">
                    <li data-specific-item class="has-dropdown">
                      <i data-var="item var">before</i>
                      <span>Nested</span>
                      <ul class="dropdown">
                        <li>
                          <i data-var="default var">before</i>
                          <a href="/even-deeper">EvenDeeper</a>
                        </li>
                      </ul>
                    </li>
                  </ul>
                </li>
                <li>
                  <i data-var="default var">before</i>
                  <p>Text</p>
                </li>
              </ul>
            </div>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderCustomTemplatesPerRenderAndPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $factory = new MenuFactory();
        $factory->addExtension(new PerItemVotersExtension());
        $factory->addExtension(new RoutingExtension());
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test', [
            'templates' => [
                'menu' => '<div><ul{{attrs}}>{{items}}</ul></div>',
                'item' => '<li data-menu-var="{{someVar}}"{{attrs}}>{{link}}{{nest}}</li>',
            ],
            'templateVars' => [
                'someVar' => 'menu default var',
            ],
        ]);
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested');
        $menu->addChild('Text');

        $expected = '
            <div>
              <ul>
                <li data-menu-var="menu default var" class="has-dropdown">
                  <a href="/link">Link</a>
                  <ol class="dropdown">
                    <li data-menu-var="menu default var">
                      <span>Nested</span>
                    </li>
                  </ol>
                </li>
                <li data-menu-var="menu default var">
                  <span>Text</span>
                </li>
              </ul>
            </div>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu, [
            'templates' => [
                'nest' => '<ol{{attrs}}>{{items}}</ol>',
                'item' => '<li data-render-var="{{someVar}}"{{attrs}}>{{link}}{{nest}}</li>',
                'link' => '<a href="{{url}}"{{attrs}}>{{label}}</a>',
            ],
            'templateVars' => [
                'someVar' => 'render default var',
            ],
        ]));
    }

    public function testRenderCustomDefaultTemplatesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $factory = new MenuFactory();
        $factory->addExtension(new PerItemVotersExtension());
        $factory->addExtension(new RoutingExtension());
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test', [
            'templates' => [
                'menu' => '<div><ul{{attrs}}>{{items}}</ul></div>',
                'item' => '<li{{attrs}}><i data-var="{{someVar}}">before</i>{{link}}{{nest}}</li>',
            ],
            'templateVars' => [
                'someVar' => 'default var',
            ],
        ]);
        $menu->addChild('Parent', [
            'uri' => '/parent',
        ]);
        $menu['Parent']->addChild('Child', [
            'uri' => '/child',
            'templates' => [
                'item' => '<li data-no-before data-var="{{someVar}}"{{attrs}}>{{link}}{{nest}}</li>',
                'nest' => '<ul data-specific-nested{{attrs}}>{{items}}</ul>',
            ],
            'templateVars' => [
                'someVar' => 'item var',
            ],
        ]);
        $menu['Parent']['Child']->addChild('Grandchild', [
            'uri' => '/grand-child',
        ]);
        $menu['Parent']['Child']['Grandchild']->addChild('GreatGrandchild', [
            'uri' => '/great-grandchild',
            'defaultTemplates' => [
                'item' => '<li data-new-defaults data-var="{{someVar}}"{{attrs}}>{{link}}{{nest}}</li>',
                'nest' => '<ul data-new-defaults{{attrs}}>{{items}}</ul>',
            ],
            'defaultTemplateVars' => [
                'someVar' => 'new default var',
            ],
        ]);
        $menu['Parent']['Child']['Grandchild']['GreatGrandchild']->addChild('SecondGreatGrandchild', [
            'uri' => '/second-great-grandchild',
        ]);
        $menu->addChild('Text');

        $expected = '
            <div>
              <ul>
                <li class="has-dropdown">
                  <i data-var="default var">before</i>
                  <a href="/parent">Parent</a>
                  <ul class="dropdown">
                    <li data-no-before data-var="item var" class="has-dropdown">
                      <a href="/child">Child</a>
                      <ul data-specific-nested class="dropdown">
                        <li class="has-dropdown">
                          <i data-var="default var">before</i>
                          <a href="/grand-child">Grandchild</a>
                          <ul class="dropdown">
                            <li data-new-defaults data-var="new default var" class="has-dropdown">
                              <a href="/great-grandchild">GreatGrandchild</a>
                              <ul data-new-defaults class="dropdown">
                                <li data-new-defaults data-var="new default var">
                                  <a href="/second-great-grandchild">SecondGreatGrandchild</a>
                                </li>
                              </ul>
                            </li>
                          </ul>
                        </li>
                      </ul>
                    </li>
                  </ul>
                </li>
                <li>
                  <i data-var="default var">before</i>
                  <span>Text</span>
                </li>
              </ul>
            </div>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderCustomDefaultTemplatesPerItemDoNotOverwriteCustomTemplatesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $factory = new MenuFactory();
        $factory->addExtension(new PerItemVotersExtension());
        $factory->addExtension(new RoutingExtension());
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test', [
            'templates' => [
                'menu' => '<div><ul{{attrs}}>{{items}}</ul></div>',
                'item' => '<li{{attrs}}><i data-var="{{someVar}}">before</i>{{link}}{{nest}}</li>',
            ],
            'templateVars' => [
                'someVar' => 'default var',
            ],
        ]);
        $menu->addChild('Parent', [
            'uri' => '/parent',
        ]);
        $menu['Parent']->addChild('Child', [
            'uri' => '/child',
            'templates' => [
                'item' => '<li data-do-not-overwrite data-var="{{itemVar}}"{{attrs}}>{{link}}{{nest}}</li>',
            ],
            'templateVars' => [
                'itemVar' => 'do not overwrite',
            ],
            'defaultTemplates' => [
                'item' => '<li data-new-defaults data-var="{{someVar}}"{{attrs}}>{{link}}{{nest}}</li>',
                'nest' => '<ul data-new-defaults{{attrs}}>{{items}}</ul>',
            ],
            'defaultTemplateVars' => [
                'itemVar' => 'new value',
                'someVar' => 'new default var',
            ],
        ]);
        $menu['Parent']['Child']->addChild('Grandchild', [
            'uri' => '/grand-child',
        ]);

        $expected = '
            <div>
              <ul>
                <li class="has-dropdown">
                  <i data-var="default var">before</i>
                  <a href="/parent">Parent</a>
                  <ul class="dropdown">
                    <li data-do-not-overwrite data-var="do not overwrite" class="has-dropdown">
                      <a href="/child">Child</a>
                      <ul data-new-defaults class="dropdown">
                        <li data-new-defaults data-var="new default var">
                          <a href="/grand-child">Grandchild</a>
                        </li>
                      </ul>
                    </li>
                  </ul>
                </li>
              </ul>
            </div>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAllClassesByDefault(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => true,
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeAllClassesByDefault(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'consumeItemClasses' => true,
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li>
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeAllClassesByDefault(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => true,
            'consumeItemClasses' => true,
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li>
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritSpecificClassesByDefault(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested" class="active leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeSpecificClassesByDefault(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'consumeItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="first last"><a href="/nested" class="active leaf">Nested</a></li>
                </ul>
              </li>
              <li class="last"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeSpecificClassesByDefault(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => [
                'currentClass',
                'leafClass',
            ],
            'consumeItemClasses' => [
                'currentClass',
                'lastClass',
            ],
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="first leaf"><a href="/nested" class="active last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="leaf"><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAllClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => true,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span>Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeAllClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'consumeItemClasses' => true,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span>Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeAllClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => true,
                'consumeItemClasses' => true,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span>Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritSpecificClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => [
                    'currentClass',
                    'leafClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested" class="active leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span>Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeSpecificClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'consumeItemClasses' => [
                    'currentClass',
                    'leafClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="first last"><a href="/nested" class="active leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span>Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeSpecificClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => [
                    'currentClass',
                    'leafClass',
                ],
                'consumeItemClasses' => [
                    'currentClass',
                    'lastClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="first leaf"><a href="/nested" class="active last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span>Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAllClassesByDefaultOverrideInheritClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => true,
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => [
                    'currentClass',
                    'leafClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested" class="active leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeAllClassesByDefaultOverrideConsumeClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'consumeItemClasses' => true,
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'consumeItemClasses' => [
                    'currentClass',
                    'leafClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li>
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li class="first last"><a href="/nested" class="active leaf">Nested</a></li>
                </ul>
              </li>
              <li><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeAllClassesByDefaultOverrideInheritAndConsumeClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => true,
            'consumeItemClasses' => true,
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => [
                    'currentClass',
                    'leafClass',
                ],
                'consumeItemClasses' => [
                    'currentClass',
                    'leafClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li>
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li class="first last"><a href="/nested" class="active leaf">Nested</a></li>
                </ul>
              </li>
              <li><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritSpecificClassesByDefaultOverrideInheritSpecificClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => [
                    'firstClass',
                    'lastClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested" class="first last">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeSpecificClassesByDefaultOverrideConsumeSpecificClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'consumeItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'consumeItemClasses' => [
                    'firstClass',
                    'lastClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active leaf"><a href="/nested" class="first last">Nested</a></li>
                </ul>
              </li>
              <li class="last"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeSpecificClassesByDefaultOverrideInheritAndConsumeSpecificClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => [
                'currentClass',
            ],
            'consumeItemClasses' => [
                'leafClass',
            ],
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => [
                    'firstClass',
                ],
                'consumeItemClasses' => [
                    'lastClass',
                ],
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active first leaf"><a href="/nested" class="first last">Nested</a></li>
                </ul>
              </li>
              <li class="active last"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritSpecificClassesByDefaultOverrideInheritAllClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => true,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeSpecificClassesByDefaultOverrideConsumeAllClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'consumeItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'consumeItemClasses' => true,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="last"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeSpecificClassesByDefaultOverrideInheritAndConsumeAllClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => [
                'currentClass',
            ],
            'consumeItemClasses' => [
                'leafClass',
            ],
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => true,
                'consumeItemClasses' => true,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li><a href="/nested" class="active first last leaf">Nested</a></li>
                </ul>
              </li>
              <li class="active last"><span class="active leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAllClassesByDefaultOverrideInheritNoClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => true,
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => false,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li class="active-ancestor first has-dropdown">
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested">Nested</a></li>
                </ul>
              </li>
              <li class="active last leaf"><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderConsumeAllClassesByDefaultOverrideConsumeNoClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'consumeItemClasses' => true,
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'consumeItemClasses' => false,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li>
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested">Nested</a></li>
                </ul>
              </li>
              <li><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderInheritAndConsumeAllClassesByDefaultOverrideInheritAndConsumeNoClassesPerItem(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'inheritItemClasses' => true,
            'consumeItemClasses' => true,
        ]);

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']
            ->addChild('Nested', [
                'uri' => '/nested',
                'inheritItemClasses' => false,
                'consumeItemClasses' => false,
            ])
            ->setCurrent(true);
        $menu->addChild('Text')->setCurrent(true);

        $expected = '
            <ul>
              <li>
                <a href="/link" class="active-ancestor first has-dropdown">Link</a>
                <ul class="dropdown">
                  <li class="active first last leaf"><a href="/nested">Nested</a></li>
                </ul>
              </li>
              <li><span class="active last leaf">Text</span></li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderDoClearMatcher(): void
    {
        /** @var MatcherInterface|MockObject $matcher */
        $matcher = $this
            ->getMockBuilder(MatcherInterface::class)
            ->getMock();
        $matcher
            ->expects($this->once())
            ->method('clear');

        $renderer = new StringTemplateRenderer($matcher);

        $renderer->render($this->menu);
    }

    public function testRenderDoNotClearMatcher(): void
    {
        /** @var MatcherInterface|MockObject $matcher */
        $matcher = $this
            ->getMockBuilder(MatcherInterface::class)
            ->getMock();
        $matcher
            ->expects($this->never())
            ->method('clear');

        $renderer = new StringTemplateRenderer($matcher);

        $renderer->render($this->menu, [
            'clearMatcher' => false,
        ]);
    }

    public function testRenderNoAncestorClass(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher(), [
            'ancestorClass' => null,
        ]);

        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('Link', ['uri' => '/link']);
        $menu['Link']->addChild('Nested', ['uri' => '/nested'])->setCurrent(true);

        $expected = '
            <ul>
              <li class="has-dropdown">
                <a href="/link">Link</a>
                <ul class="dropdown">
                  <li class="active">
                    <a href="/nested">Nested</a>
                  </li>
                </ul>
              </li>
            </ul>';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderEscapeLabel(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('<b>Escaped</b>', [
            'attributes' => [
                'escaped' => '"escaped"',
            ],
        ]);
        $menu->addChild('<b>Unescaped</b>', [
            'escapeLabel' => false,
            'attributes' => [
                'escaped' => '"escaped"',
            ],
        ]);

        $expected = '
            <ul>
              <li escaped="&quot;escaped&quot;"><span>&lt;b&gt;Escaped&lt;/b&gt;</span></li>
              <li escaped="&quot;escaped&quot;"><span><b>Unescaped</b></span></li>
            </ul>
        ';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    public function testRenderEscapeAttributesAndLabel(): void
    {
        $renderer = new StringTemplateRenderer(new Matcher());

        $factory = new MenuFactory();
        $factory->addExtension(new TemplaterExtension());

        $menu = $factory->createItem('test');
        $menu->addChild('<b>Escaped</b>', [
            'attributes' => [
                'escaped' => '"escaped"',
            ],
        ]);
        $menu->addChild('<b>Unescaped</b>', [
            'escape' => false,
            'attributes' => [
                'unescaped' => '"unescaped"',
            ],
        ]);

        $expected = '
            <ul>
              <li escaped="&quot;escaped&quot;"><span>&lt;b&gt;Escaped&lt;/b&gt;</span></li>
              <li unescaped=""unescaped""><span><b>Unescaped</b></span></li>
            </ul>
        ';
        $this->assertTrimmedHtml($expected, $renderer->render($menu));
    }

    //endregion

    // -----------------------------------------------------------------------------------------------------------------
    //region Adapted KnpMenu vendor tests
    // -----------------------------------------------------------------------------------------------------------------

    protected function createRenderer(MatcherInterface $matcher): RendererInterface
    {
        $renderer = new StringTemplateRenderer($matcher, [
            'currentClass' => 'current',
            'ancestorClass' => 'current_ancestor',
            'nestedMenuClass' => null,
            'menuLevelClass' => 'menu_level_',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'branchClass' => null,
        ]);

        return $renderer;
    }

    public function testRenderLinkWithSpecialAttributes(): void
    {
        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('About', ['uri' => '/about', 'linkAttributes' => ['default' => true]]);

        $expected = '<ul><li class="first last"><a href="/about" default="default">About</a></li></ul>';
        $this->assertEquals($expected, $this->renderer->render($menu));
    }

    public function testRenderChildrenWithSpecialAttributes(): void
    {
        $menu = new MenuItem('test', new MenuFactory());
        $about = $menu->addChild('About');
        $about->addChild('Us');
        $about->setChildrenAttribute('default', true);

        $expected = '<ul><li class="first last"><span>About</span><ul default="default" class="menu_level_1"><li class="first last"><span>Us</span></li></ul></li></ul>';
        $this->assertEquals($expected, $this->renderer->render($menu));
    }

    public function testRenderLabelWithSpecialAttributes(): void
    {
        $menu = new MenuItem('test', new MenuFactory());
        $menu->addChild('About', ['labelAttributes' => ['default' => true]]);

        $expected = '<ul><li class="first last"><span default="default">About</span></li></ul>';
        $this->assertEquals($expected, $this->renderer->render($menu));
    }

    public function testRenderSafeLabel(): void
    {
        $factory = new MenuFactory();
        $factory->addExtension(new PerItemVotersExtension());
        $factory->addExtension(new RoutingExtension());
        $factory->addExtension(new TemplaterExtension());

        $menu = new MenuItem('test', $factory);
        $menu->addChild('About', ['label' => 'Encode " me']);
        $menu->addChild('Safe', ['label' => 'Encode " me again', 'extras' => ['escapeLabel' => false]]);
        $menu->addChild('Escaped', ['label' => 'Encode " me too', 'extras' => ['escapeLabel' => true]]);

        $expected = '<ul><li class="first"><span>Encode &quot; me</span></li><li><span>Encode " me again</span></li><li class="last"><span>Encode &quot; me too</span></li></ul>';
        $this->assertEquals($expected, $this->renderer->render($menu));
    }

    public function testRenderWithClassAndTitle(): void
    {
        $this->pt2->setAttribute('class', 'parent2_class');
        $this->pt2->setAttribute('title', 'parent2 title');
        $rendered = '<ul class="root"><li class="first"><span>Parent 1</span><ul class="menu_level_1"><li class="first"><span>Child 1</span></li><li><span>Child 2</span></li><li class="last"><span>Child 3</span></li></ul></li><li class="last parent2_class" title="parent2 title"><span>Parent 2</span><ul class="menu_level_1"><li class="first last"><span>Child 4</span><ul class="menu_level_2"><li class="first last"><span>Grandchild 1</span></li></ul></li></ul></li></ul>';
        $this->assertEquals($rendered, $this->renderer->render($this->menu));
    }

    public function testLeafAndBranchRendering(): void
    {
        $expected = '<ul class="root"><li class="branch first"><span>Parent 1</span><ul class="menu_level_1"><li class="first leaf"><span>Child 1</span></li><li class="leaf"><span>Child 2</span></li><li class="last leaf"><span>Child 3</span></li></ul></li><li class="branch last"><span>Parent 2</span><ul class="menu_level_1"><li class="first last leaf"><span>Child 4</span></li></ul></li></ul>';

        $this->assertEquals($expected, $this->renderer->render($this->menu, [
            'depth' => 2,
            'leafClass' => 'leaf',
            'branchClass' => 'branch',
        ]));
    }

    //endregion
}
