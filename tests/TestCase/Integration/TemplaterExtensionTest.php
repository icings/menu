<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Integration;

use Cake\TestSuite\TestCase;
use Icings\Menu\Integration\TemplaterExtension;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;

class TemplaterExtensionTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Icings\Menu\Integration\TemplaterExtension
     */
    public $TemplaterExtension;

    public function setUp(): void
    {
        parent::setUp();
        $this->TemplaterExtension = new TemplaterExtension();
    }

    public function tearDown(): void
    {
        unset($this->TemplaterExtension);

        parent::tearDown();
    }

    public function testBuildOptionsDefaults()
    {
        $options = $this->TemplaterExtension->buildOptions();
        $expected = [];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTemplatesOnly()
    {
        $originalOptions = [
            'templates' => [
                'name' => 'value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => [
                'templates' => $originalOptions['templates']
            ]
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTemplateVarsOnly()
    {
        $originalOptions = [
            'templateVars' => [
                'name' => 'value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => [
                'templateVars' => $originalOptions['templateVars']
            ]
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineMenuAttributesOnly()
    {
        $originalOptions = [
            'menuAttributes' => [
                'id' => 'value',
                'class' => 'value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => $originalOptions['menuAttributes']
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineMenuAttributesOnlyWithExistingChildrenAttributes()
    {
        $originalOptions = [
            'childrenAttributes' => [
                'existing' => 'value',
                'otherExisting' => 'value'
            ],
            'menuAttributes' => [
                'name' => 'value',
                'otherExisting' => 'new value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => [
                'existing' => 'value',
                'name' => 'value',
                'otherExisting' => 'new value'
            ]
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineNestAttributesOnly()
    {
        $originalOptions = [
            'nestAttributes' => [
                'name' => 'value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => $originalOptions['nestAttributes']
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineNestAttributesOnlyWithExistingChildrenAttributes()
    {
        $originalOptions = [
            'childrenAttributes' => [
                'existing' => 'value',
                'otherExisting' => 'value'
            ],
            'nestAttributes' => [
                'name' => 'value',
                'otherExisting' => 'new value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => [
                'existing' => 'value',
                'name' => 'value',
                'otherExisting' => 'new value'
            ]
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTextAttributesOnly()
    {
        $originalOptions = [
            'textAttributes' => [
                'name' => 'value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'labelAttributes' => $originalOptions['textAttributes']
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTextAttributesOnlyWithExistingChildrenAttributes()
    {
        $originalOptions = [
            'labelAttributes' => [
                'existing' => 'value',
                'otherExisting' => 'value'
            ],
            'textAttributes' => [
                'name' => 'value',
                'otherExisting' => 'new value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'labelAttributes' => [
                'existing' => 'value',
                'name' => 'value',
                'otherExisting' => 'new value'
            ]
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineEscapeOnly()
    {
        $originalOptions = [
            'escape' => true
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineDisableEscapeOnly()
    {
        $originalOptions = [
            'escape' => false
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineEscapeLabelOnly()
    {
        $originalOptions = [
            'escapeLabel' => true
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineDisableEscapeLabelOnly()
    {
        $originalOptions = [
            'escapeLabel' => false
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineAll()
    {
        $originalOptions = [
            'templates' => [
                'name' => 'value'
            ],
            'templateVars' => [
                'name' => 'value'
            ],
            'defaultTemplates' => [
                'name' => 'value'
            ],
            'defaultTemplateVars' => [
                'name' => 'value'
            ],
            'menuAttributes' => [
                'name' => 'value'
            ],
            'textAttributes' => [
                'name' => 'value',
            ],
            'nestAttributes' => [
                'name' => 'value',
            ],
            'escape' => false,
            'escapeLabel' => true
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);

        unset(
            $originalOptions['menuAttributes'],
            $originalOptions['nestAttributes'],
            $originalOptions['textAttributes']
        );
        $expected = [
            'labelAttributes' => [
                'name' => 'value'
            ],
            'childrenAttributes' => [
                'name' => 'value'
            ],
            'extras' => $originalOptions
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTemplatesAndTemplateVars()
    {
        $originalOptions = [
            'templates' => [
                'name' => 'value'
            ],
            'templateVars' => [
                'name' => 'value'
            ]
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildItem()
    {
        $item = new MenuItem('item', $this->getMockBuilder(FactoryInterface::class)->getMock());
        $this->assertNull($this->TemplaterExtension->buildItem($item, []));
    }
}
