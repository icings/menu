<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Matcher;

use Knp\Menu\Matcher\MatcherInterface as BaseMatcherInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;

/**
 * An interface that describes the contract for voter pattern matchers.
 */
interface MatcherInterface extends BaseMatcherInterface
{
    /**
     * Adds a voter in the matcher.
     *
     * @param VoterInterface $voter The voter to add.
     * @return void
     */
    public function addVoter(VoterInterface $voter): void;
}
