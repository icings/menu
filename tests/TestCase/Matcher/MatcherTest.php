<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Test\TestCase\Matcher;

use Cake\TestSuite\TestCase;
use Icings\Menu\Matcher\Matcher;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\MockObject\MockObject;

class MatcherTest extends TestCase
{
    public function testIsCurrentPerItemVoters(): void
    {
        /** @var ItemInterface|MockObject $item */
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $itemVoterInvoked = false;

        /** @var VoterInterface|MockObject $globalVoter */
        $globalVoter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $globalVoter
            ->expects($this->once())
            ->method('matchItem')
            ->with($this->identicalTo($item))
            ->willReturnCallback(function () use (&$itemVoterInvoked) {
                if ($itemVoterInvoked === false) {
                    $this->fail('Expected the item voter to be invoked first.');
                }

                return null;
            });

        $itemVoter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $itemVoter
            ->expects($this->once())
            ->method('matchItem')
            ->with($this->identicalTo($item))
            ->willReturnCallback(function () use (&$itemVoterInvoked) {
                $itemVoterInvoked = true;

                return null;
            });

        $item
            ->expects($this->at(0))
            ->method('isCurrent')
            ->will($this->returnValue(null));
        $item
            ->expects($this->at(1))
            ->method('getExtra')
            ->with('voters')
            ->willReturn([$itemVoter]);

        $matcher = new Matcher();
        $matcher->addVoter($globalVoter);

        $matcher->isCurrent($item);
    }

    public function testIsCurrentCache(): void
    {
        /** @var ItemInterface|MockObject $item */
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->exactly(3))
            ->method('isCurrent')
            ->willReturn(null);

        /** @var VoterInterface|MockObject $voter1 */
        $voter1 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter1
            ->expects($this->exactly(2))
            ->method('matchItem')
            ->with($this->identicalTo($item))
            ->willReturn(true);

        $matcher = new Matcher();
        $matcher->addVoter($voter1);

        $matcher->isCurrent($item);
        $matcher->isCurrent($item);
        $matcher->clear();
        $matcher->isCurrent($item);
    }

    public function testIsAncestor(): void
    {
        $factory = $this
            ->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $grandChild = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['GrandChild', $factory])
            ->getMock();
        $grandChild
            ->expects($this->once())
            ->method('getChildren')
            ->willReturn([]);

        $child = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['Child', $factory])
            ->getMock();
        $child
            ->expects($this->once())
            ->method('getChildren')
            ->willReturn([$grandChild]);

        /** @var MenuItem|MockObject $parent */
        $parent = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['Parent', $factory])
            ->getMock();
        $parent
            ->expects($this->once())
            ->method('getChildren')
            ->willReturn([$child]);

        $matcher = new Matcher();
        $matcher->isAncestor($parent);
    }

    public function testIsAncestorDepthLimit(): void
    {
        $factory = $this
            ->getMockBuilder(FactoryInterface::class)
            ->getMock();

        $child = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['Child', $factory])
            ->getMock();
        $child
            ->expects($this->never())
            ->method('getChildren');

        /** @var MenuItem|MockObject $parent */
        $parent = $this
            ->getMockBuilder(MenuItem::class)
            ->setConstructorArgs(['Parent', $factory])
            ->getMock();
        $parent
            ->expects($this->once())
            ->method('getChildren')
            ->willReturn([$child]);

        $matcher = new Matcher();
        $matcher->isAncestor($parent, 1);
    }

    // -----------------------------------------------------------------------------------------------------------------
    //region Adapted KnpMenu vendor tests
    // -----------------------------------------------------------------------------------------------------------------

    public function currentFlagDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
            [null, false],
        ];
    }

    /**
     * @dataProvider currentFlagDataProvider
     * @param bool|null $flag
     * @param bool $expected
     */
    public function testItemFlag($flag, $expected): void
    {
        /** @var ItemInterface|MockObject $item */
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->once())
            ->method('isCurrent')
            ->will($this->returnValue($flag));

        $matcher = new Matcher();

        $this->assertSame($expected, $matcher->isCurrent($item));
    }

    public function testFlagOverwritesCache(): void
    {
        /** @var ItemInterface|MockObject $item */
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->exactly(2))
            ->method('isCurrent')
            ->willReturnOnConsecutiveCalls(true, false);

        $matcher = new Matcher();

        $this->assertTrue($matcher->isCurrent($item));
        $this->assertFalse($matcher->isCurrent($item));
    }

    public function matchingResultDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider matchingResultDataProvider
     * @param bool $value
     */
    public function testFlagWinsOverVoter($value): void
    {
        /** @var ItemInterface|MockObject $item */
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->once())
            ->method('isCurrent')
            ->will($this->returnValue($value));

        /** @var VoterInterface|MockObject $voter */
        $voter = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter
            ->expects($this->never())
            ->method('matchItem');

        $matcher = new Matcher();
        $matcher->addVoter($voter);

        $this->assertSame($value, $matcher->isCurrent($item));
    }

    /**
     * @dataProvider matchingResultDataProvider
     * @param bool $value
     */
    public function testFirstVoterWins($value): void
    {
        /** @var ItemInterface|MockObject $item */
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->once())
            ->method('isCurrent')
            ->will($this->returnValue(null));

        /** @var VoterInterface|MockObject $voter1 */
        $voter1 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter1
            ->expects($this->once())
            ->method('matchItem')
            ->with($this->identicalTo($item))
            ->will($this->returnValue($value));

        /** @var VoterInterface|MockObject $voter2 */
        $voter2 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter2
            ->expects($this->never())
            ->method('matchItem');

        $matcher = new Matcher();
        $matcher->addVoter($voter1);
        $matcher->addVoter($voter2);

        $this->assertSame($value, $matcher->isCurrent($item));
    }

    //endregion
}
