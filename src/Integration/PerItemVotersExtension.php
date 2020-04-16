<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Integration;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

/**
 * An extension that complements the per item voter functionality.
 */
class PerItemVotersExtension implements ExtensionInterface
{
    /**
     * @inheritDoc
     */
    public function buildOptions(array $options = []): array
    {
        if (!empty($options['voters'])) {
            $options['extras']['voters'] = $options['voters'];
            unset($options['voters']);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function buildItem(ItemInterface $item, array $options): void
    {
    }
}
