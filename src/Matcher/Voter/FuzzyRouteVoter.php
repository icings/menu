<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Matcher\Voter;

use Cake\Http\ServerRequest;
use Cake\Network\Request;
use Cake\Utility\Hash;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;

/**
 * A voter that compares URL arrays against routing parameters in a fuzzy manner.
 */
class FuzzyRouteVoter implements VoterInterface
{
    /**
     * The routing parameters to match against.
     *
     * @var array
     */
    protected $_params;

    /**
     * Returns the routing parameters to match against.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Constructor.
     *
     * @param Request|ServerRequest $request The request object from where to extract the routing
     *   parameters to match against.
     */
    public function __construct($request)
    {
        $this->_params = $this->_extractParams($request);
    }

    /**
     * {@inheritDoc}
     */
    public function matchItem(ItemInterface $item)
    {
        $routes = $item->getExtra('routes');
        if ($routes === null) {
            return null;
        }

        $params = $this->getParams();
        foreach ($routes as $route) {
            $this->_normalizeRoute($route);

            if (isset($route['?'])) {
                if (array_intersect_key($params['?'], $route['?']) !== $route['?']) {
                    continue;
                }
                unset($route['?']);
            }

            if (array_intersect_key($params, $route) === $route) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts the routing parameters from the given request.
     *
     * @param Request|ServerRequest $request The request object from where to extract the routing
     *   parameters.
     * @return array An array of routing parameters.
     */
    protected function _extractParams($request)
    {
        $attributes = $request->getAttributes();
        $params = Hash::get($attributes, 'params', []);
        $params['?'] = $request->getQuery();
        $params['_method'] = $request->getMethod();
        $params['_host'] = $request->host();
        if (!isset($params['_ext'])) {
            $params['_ext'] = null;
        }

        $pass = $request->getParam('pass', []);

        unset(
            $params['pass'],
            $params['paging'],
            $params['models'],
            $params['url'],
            $params['autoRender'],
            $params['bare'],
            $params['requested'],
            $params['return'],
            $params['_Token'],
            $params['_matchedRoute']
        );
        $params = array_merge($params, $pass);
        $params += $params['?'];

        $this->_normalizeParams($params);

        return $params;
    }

    /**
     * Normalizes a set of routing parameters.
     *
     * Normalization includes:
     *
     * - casting numeric values to strings
     * - sorting elements by key
     *
     * @param array $params The parameters to normalize.
     * @return void
     */
    protected function _normalizeParams(array &$params)
    {
        ksort($params, \SORT_STRING);
        array_walk($params, function (&$value) {
            if (is_numeric($value)) {
                $value = (string)$value;
            }
        });

        if (isset($params['?'])) {
            $this->_normalizeParams($params['?']);
        }
    }

    /**
     * Normalizes a route (URL array).
     *
     * Normalization includes:
     *
     * - removing special keys like `#`, `_base`, '_scheme', etc
     * - normalization as applied by `_normalizeParams()`
     *
     * @see _normalizeParams()
     *
     * @param array $route The route (URL array) to normalize.
     * @return void
     */
    protected function _normalizeRoute(array &$route)
    {
        unset(
            $route['#'],
            $route['_base'],
            $route['_scheme'],
            $route['_port'],
            $route['_full'],
            $route['_ssl']
        );

        $this->_normalizeParams($route);
    }
}
