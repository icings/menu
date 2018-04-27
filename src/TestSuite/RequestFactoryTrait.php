<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\TestSuite;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Network\Request;
use Cake\Routing\Router;

/**
 * Supplies version-aware request factory functionality.
 */
trait RequestFactoryTrait
{
    /**
     * Creates a request object.
     *
     * @param string $requestUri The request URI for which to create a request object for.
     * @return Request|ServerRequest
     */
    public static function createRequest($requestUri)
    {
        if ((float)Configure::version() >= 3.4) {
            return static::_createServerRequest($requestUri);
        }

        return static::_createLegacyRequest($requestUri);
    }

    /**
     * Extracts the query part from a URI.
     *
     * @param string $uri The URI from which to extract the query part.
     * @return array
     */
    protected static function _extractQuery($uri)
    {
        $arguments = [];
        $string = '';
        if (strpos($uri, '?') !== false) {
            $string = explode('?', $uri, 2)[1];
            parse_str($string, $arguments);
            $string = '?' . $string;
        }

        return compact('arguments', 'string');
    }

    /**
     * Creates a legacy request object (pre CakePHP 3.3).
     *
     * @param string $requestUri The request URI for which to create a request object for.
     * @return Request
     */
    protected static function _createLegacyRequest($requestUri)
    {
        $query = static::_extractQuery($requestUri);

        $_GET = $query['arguments'];
        $_SERVER['PHP_SELF'] = '/index.php';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = $requestUri;
        $_SERVER['QUERY_STRING'] = $query['string'];

        $request = ServerRequestFactory::fromGlobals();
        $params = Router::parseRequest($request);
        foreach ($params as $k => $v) {
            $request = $request->withParam($k, $v);
        }

        Router::setRequestInfo($request);

        return $request;
    }

    /**
     * Creates a server request object.
     *
     * @param string $requestUri The request URI for which to create a request object for.
     * @return ServerRequest
     */
    protected static function _createServerRequest($requestUri)
    {
        $query = static::_extractQuery($requestUri);

        $request = ServerRequestFactory::fromGlobals(
            [
                'PHP_SELF' => '/index.php',
                'REQUEST_METHOD' => 'GET',
                'HTTP_HOST' => 'localhost',
                'REQUEST_URI' => $requestUri,
                'QUERY_STRING' => $query['string']
            ],
            $query['arguments']
        );
        Router::setRequestContext($request);

        return $request->withAttribute(
            'params',
            Router::parseRequest($request)
        );
    }
}
