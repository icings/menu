<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Matcher;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;

/**
 * A voter pattern matcher that supports per item voters.
 *
 * Given that the original KnpMenu renderer cache and voters properties
 * are private, this class completely re-implements the original.
 */
class Matcher implements MatcherInterface
{
    /**
     * A cache that maps menu items and matching results.
     *
     * @var \SplObjectStorage
     */
    protected $_cache;

    /**
     * Collection of voters added `addVoter()`.
     *
     * @var VoterInterface[]
     */
    protected $_voters = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_cache = new \SplObjectStorage();
    }

    /**
     * @inheritDoc
     */
    public function addVoter(VoterInterface $voter): void
    {
        $this->_voters[] = $voter;
    }

    /**
     * @inheritDoc
     */
    public function isCurrent(ItemInterface $item): bool
    {
        $current = $item->isCurrent();
        if ($current !== null) {
            return $current;
        }

        if ($this->_cache->contains($item)) {
            return (bool)$this->_cache[$item];
        }

        $voters = $this->_voters;
        $itemVoters = $item->getExtra('voters');
        if ($itemVoters !== null) {
            $voters = array_merge($itemVoters, $voters);
        }

        foreach ($voters as $voter) {
            $current = $voter->matchItem($item);
            if ($current !== null) {
                break;
            }
        }

        return $this->_cache[$item] = (bool)$current;
    }

    /**
     * @inheritDoc
     */
    public function isAncestor(ItemInterface $item, ?int $depth = null): bool
    {
        if ($depth === 0) {
            return false;
        }

        if ($depth !== null) {
            $childDepth = $depth - 1;
        } else {
            $childDepth = null;
        }

        foreach ($item->getChildren() as $child) {
            if (
                $this->isCurrent($child) ||
                $this->isAncestor($child, $childDepth)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->_cache = new \SplObjectStorage();
    }
}
