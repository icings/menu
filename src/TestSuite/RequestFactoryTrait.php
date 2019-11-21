<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\TestSuite;

use Cake\Http\ServerRequest;
use Cake\Http\ServerRequestFactory;
use Cake\Routing\Router;

/**
 * Supplies easily reusable request factory functionality.
 */
trait RequestFactoryTrait
{
    /**
     * Creates a request object.
     *
     * @param string $requestUri The request URI for which to create a request object for.
     * @return ServerRequest
     */
    public static function createRequest(string $requestUri): ServerRequest
    {
        $query = static::_extractQuery($requestUri);

        $request = ServerRequestFactory::fromGlobals(
            [
                'PHP_SELF' => '/index.php',
                'REQUEST_METHOD' => 'GET',
                'HTTP_HOST' => 'localhost',
                'REQUEST_URI' => $requestUri,
                'QUERY_STRING' => $query['string'],
            ],
            $query['arguments']
        );
        Router::setRequest($request);

        return $request->withAttribute(
            'params',
            Router::parseRequest($request)
        );
    }

    /**
     * Extracts the query part from a URI.
     *
     * @param string $uri The URI from which to extract the query part.
     * @return array
     */
    protected static function _extractQuery(string $uri): array
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
}
