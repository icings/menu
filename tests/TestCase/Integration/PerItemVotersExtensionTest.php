<?php
declare(strict_types=1);

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
use PHPUnit\Framework\MockObject\MockObject;

class PerItemVotersExtensionTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Icings\Menu\Integration\PerItemVotersExtension
     */
    public PerItemVotersExtension $PerItemVotersExtension;

    public function setUp(): void
    {
        parent::setUp();
        $this->PerItemVotersExtension = new PerItemVotersExtension();
    }

    public function tearDown(): void
    {
        unset($this->PerItemVotersExtension);

        parent::tearDown();
    }

    public function testBuildOptionsDefaults(): void
    {
        $options = $this->PerItemVotersExtension->buildOptions();
        $expected = [];
        $this->assertEquals($expected, $options);
    }

    public function testBuildOptionsDefineVoters(): void
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

    public function testBuildItem(): void
    {
        /** @var FactoryInterface|MockObject $factory */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();

        $item = new MenuItem('item', $factory);
        $clone = clone $item;

        $this->PerItemVotersExtension->buildItem($item, []);
        $this->assertEquals($item, $clone);
    }
}
