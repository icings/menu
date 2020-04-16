<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Integration;

use Cake\Routing\Router;
use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

/**
 * An extension that complements the CakePHP routing support.
 */
class RoutingExtension implements ExtensionInterface
{
    /**
     * @inheritDoc
     */
    public function buildOptions(array $options = []): array
    {
        if (!empty($options['uri'])) {
            $uri = $options['uri'];
            $options['uri'] = Router::url($uri);

            if (isset($options['ignoreQueryString'])) {
                $options['extras']['ignoreQueryString'] = $options['ignoreQueryString'];
            }
            unset($options['ignoreQueryString']);

            $routes = [];
            if (!empty($options['routes'])) {
                $routes = $options['routes'];
            }
            unset($options['routes']);

            if (
                !isset($options['addUriToRoutes']) ||
                $options['addUriToRoutes'] === true
            ) {
                array_unshift($routes, $uri);
            }
            unset($options['addUriToRoutes']);

            if (!empty($routes)) {
                $options['extras']['routes'] = $routes;
            }
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
