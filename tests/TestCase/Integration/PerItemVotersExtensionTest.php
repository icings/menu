<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Integration;

use Cake\TestSuite\TestCase;
use Icings\Menu\Integration\PerItemVotersExtension;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;

class PerItemVotersExtensionTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Icings\Menu\Integration\PerItemVotersExtension
     */
    public $PerItemVotersExtension;

    public function setUp()
    {
        parent::setUp();
        $this->PerItemVotersExtension = new PerItemVotersExtension();
    }

    public function tearDown()
    {
        unset($this->PerItemVotersExtension);

        parent::tearDown();
    }

    public function testBuildOptionsDefaults()
    {
        $options = $this->PerItemVotersExtension->buildOptions();
        $expected = [];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineVoters()
    {
        $originalOptions = [
            'voters' => [
                'voter1',
                'voter2',
            ],
        ];
        $options = $this->PerItemVotersExtension->buildOptions($originalOptions);
        $expected = [
            'extras' => [
                'voters' => $originalOptions['voters'],
            ],
        ];
        $this->assertEquals($expected, $options);
    }

    public function testBuildItem()
    {
        $item = new MenuItem('item', $this->getMockBuilder(FactoryInterface::class)->getMock());
        $this->assertNull($this->PerItemVotersExtension->buildItem($item, []));
    }
}
