<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu;

use Knp\Menu\MenuFactory as KnpMenuFactory;

/**
 * A factory implementation for creating menus (top level items).
 */
class MenuFactory extends KnpMenuFactory implements MenuFactoryInterface
{
}
