<?php
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
     * {@inheritDoc}
     */
    public function buildOptions(array $options = [])
    {
        if (!empty($options['uri'])) {
            $uri = $options['uri'];
            $options['uri'] = Router::url($uri);

            if (isset($options['ignoreQueryString'])) {
                $options['extras']['ignoreQueryString'] = $options['ignoreQueryString'];
            }
            unset($options['ignoreQueryString']);

            $routes = [];
            $hasRoutes = !empty($options['routes']);
            if ($hasRoutes) {
                $options['extras']['urls'] = [
                    'original' => [],
                    'withoutQuery' => []
                ];

                foreach ($options['routes'] as $route) {
                    if (is_array($route)) {
                        $routes[] = $route;
                    }
                    $url = Router::url($route);
                    $options['extras']['urls']['original'][] = $url;
                    $options['extras']['urls']['withoutQuery'][] = $this->_stripQueryString($url);
                }

                unset($options['routes']);
            }

            if (!isset($options['addUriToRoutes']) ||
                $options['addUriToRoutes'] === true
            ) {
                if (!$hasRoutes) {
                    $options['extras']['urls']['original'] = [$options['uri']];
                    $options['extras']['urls']['withoutQuery'] = [$this->_stripQueryString($options['uri'])];
                } else {
                    array_unshift($options['extras']['urls']['original'], $options['uri']);
                    array_unshift($options['extras']['urls']['withoutQuery'], $this->_stripQueryString($options['uri']));
                }

                if (is_array($uri)) {
                    if (empty($routes)) {
                        $routes = [$uri];
                    } else {
                        array_unshift($routes, $uri);
                    }
                }
            }
            unset($options['addUriToRoutes']);

            if (!empty($routes)) {
                $options['extras']['routes'] = $routes;
            }
        }

        return $options;
    }

    /**
     * Removes the query string from the given URL.
     *
     * @param string $url The URL from which to strip the query string.
     * @return string The URL without query string.
     */
    protected function _stripQueryString($url)
    {
        if (strpos($url, '?') !== false) {
            return explode('?', $url, 2)[0];
        }

        return $url;
    }

    /**
     * {@inheritDoc}
     */
    public function buildItem(ItemInterface $item, array $options)
    {
        return null;
    }
}
