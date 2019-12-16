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
use PHPUnit\Framework\MockObject\MockObject;

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

    public function testBuildOptionsDefaults(): void
    {
        $options = $this->TemplaterExtension->buildOptions();
        $expected = [];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTemplatesOnly(): void
    {
        $originalOptions = [
            'templates' => [
                'name' => 'value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => [
                'templates' => $originalOptions['templates'],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTemplateVarsOnly(): void
    {
        $originalOptions = [
            'templateVars' => [
                'name' => 'value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => [
                'templateVars' => $originalOptions['templateVars'],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineMenuAttributesOnly(): void
    {
        $originalOptions = [
            'menuAttributes' => [
                'id' => 'value',
                'class' => 'value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => $originalOptions['menuAttributes'],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineMenuAttributesOnlyWithExistingChildrenAttributes(): void
    {
        $originalOptions = [
            'childrenAttributes' => [
                'existing' => 'value',
                'otherExisting' => 'value',
            ],
            'menuAttributes' => [
                'name' => 'value',
                'otherExisting' => 'new value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => [
                'existing' => 'value',
                'name' => 'value',
                'otherExisting' => 'new value',
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineNestAttributesOnly(): void
    {
        $originalOptions = [
            'nestAttributes' => [
                'name' => 'value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => $originalOptions['nestAttributes'],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineNestAttributesOnlyWithExistingChildrenAttributes(): void
    {
        $originalOptions = [
            'childrenAttributes' => [
                'existing' => 'value',
                'otherExisting' => 'value',
            ],
            'nestAttributes' => [
                'name' => 'value',
                'otherExisting' => 'new value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'childrenAttributes' => [
                'existing' => 'value',
                'name' => 'value',
                'otherExisting' => 'new value',
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTextAttributesOnly(): void
    {
        $originalOptions = [
            'textAttributes' => [
                'name' => 'value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'labelAttributes' => $originalOptions['textAttributes'],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTextAttributesOnlyWithExistingChildrenAttributes(): void
    {
        $originalOptions = [
            'labelAttributes' => [
                'existing' => 'value',
                'otherExisting' => 'value',
            ],
            'textAttributes' => [
                'name' => 'value',
                'otherExisting' => 'new value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'labelAttributes' => [
                'existing' => 'value',
                'name' => 'value',
                'otherExisting' => 'new value',
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineEscapeOnly(): void
    {
        $originalOptions = [
            'escape' => true,
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions,
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineDisableEscapeOnly(): void
    {
        $originalOptions = [
            'escape' => false,
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions,
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineEscapeLabelOnly(): void
    {
        $originalOptions = [
            'escapeLabel' => true,
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions,
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineDisableEscapeLabelOnly(): void
    {
        $originalOptions = [
            'escapeLabel' => false,
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions,
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineInheritItemClassesOnly(): void
    {
        $originalOptions = [
            'inheritItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => [
                'inheritItemClasses' => $originalOptions['inheritItemClasses'],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineConsumeItemClassesOnly(): void
    {
        $originalOptions = [
            'consumeItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => [
                'consumeItemClasses' => $originalOptions['consumeItemClasses'],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineAll(): void
    {
        $originalOptions = [
            'templates' => [
                'name' => 'value',
            ],
            'templateVars' => [
                'name' => 'value',
            ],
            'defaultTemplates' => [
                'name' => 'value',
            ],
            'defaultTemplateVars' => [
                'name' => 'value',
            ],
            'menuAttributes' => [
                'name' => 'value',
            ],
            'textAttributes' => [
                'name' => 'value',
            ],
            'nestAttributes' => [
                'name' => 'value',
            ],
            'escape' => false,
            'escapeLabel' => true,
            'inheritItemClasses' => [
                'currentClass',
                'leafClass',
            ],
            'consumeItemClasses' => [
                'currentClass',
                'leafClass',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);

        unset(
            $originalOptions['menuAttributes'],
            $originalOptions['nestAttributes'],
            $originalOptions['textAttributes']
        );
        $expected = [
            'labelAttributes' => [
                'name' => 'value',
            ],
            'childrenAttributes' => [
                'name' => 'value',
            ],
            'extras' => $originalOptions,
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineTemplatesAndTemplateVars(): void
    {
        $originalOptions = [
            'templates' => [
                'name' => 'value',
            ],
            'templateVars' => [
                'name' => 'value',
            ],
        ];
        $options = $this->TemplaterExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => $originalOptions,
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildItem(): void
    {
        /** @var FactoryInterface|MockObject $factory */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();

        $item = new MenuItem('item', $factory);
        $clone = clone $item;

        $this->TemplaterExtension->buildItem($item, []);
        $this->assertEquals($item, $clone);
    }
}
