<?php
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

class MatcherTest extends TestCase
{
    public function testIsCurrentPerItemVoters()
    {
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();

        $itemVoterInvoked = false;

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

    public function testIsCurrentCache()
    {
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->exactly(3))
            ->method('isCurrent')
            ->willReturn(null);

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

    public function testIsAncestor()
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

    public function testIsAncestorDepthLimit()
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

    public function currentFlagDataProvider()
    {
        return [
            [true, true],
            [false, false],
            [null, false]
        ];
    }

    /**
     * @dataProvider currentFlagDataProvider
     * @param boolean|null $flag
     * @param boolean $expected
     */
    public function testItemFlag($flag, $expected)
    {
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

    public function testFlagOverwritesCache()
    {
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

    public function matchingResultDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider matchingResultDataProvider
     * @param boolean $value
     */
    public function testFlagWinsOverVoter($value)
    {
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->once())
            ->method('isCurrent')
            ->will($this->returnValue($value));

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
     * @param boolean $value
     */
    public function testFirstVoterWins($value)
    {
        $item = $this
            ->getMockBuilder(ItemInterface::class)
            ->getMock();
        $item
            ->expects($this->once())
            ->method('isCurrent')
            ->will($this->returnValue(null));

        $voter1 = $this
            ->getMockBuilder(VoterInterface::class)
            ->getMock();
        $voter1
            ->expects($this->once())
            ->method('matchItem')
            ->with($this->identicalTo($item))
            ->will($this->returnValue($value));

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
