<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\FactoryInterface;

/**
 * An interface that describes the contract for factories that create menus (top level items).
 */
interface MenuFactoryInterface extends FactoryInterface
{
    /**
     * Adds a factory extension.
     *
     * @param ExtensionInterface $extension The extension to add.
     * @param int $priority The priority to assign to the extension.
     * @return void
     */
    public function addExtension(ExtensionInterface $extension, int $priority = 0): void;
}
