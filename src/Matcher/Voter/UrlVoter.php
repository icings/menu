<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Matcher\Voter;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;

/**
 * A voter that compares one or more flat URLs.
 */
class UrlVoter implements VoterInterface
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'ignoreQueryString' => true,
    ];

    /**
     * The request target URL to match against.
     *
     * @var string
     */
    protected $_url;

    /**
     * The request target URL to match against without query string.
     *
     * @var string
     */
    protected $_urlWithoutQuery;

    /**
     * Returns the request target URL to match against.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->_url;
    }

    /**
     * Returns the request target URL to match against without query string.
     *
     * @return string
     */
    public function getUrlWithoutQuery(): string
    {
        return $this->_urlWithoutQuery;
    }

    /**
     * Constructor.
     *
     * ## Options
     *
     * - `ignoreQueryString`: Defines whether the query string should be ignored when matching
     *   items. Defaults to `true`.
     *
     * @param ServerRequest $request The request object from where to extract the request
     *   target to match against.
     * @param array $options An array of options, see the "Options" section in the method
     *   description.
     */
    public function __construct(ServerRequest $request, array $options = [])
    {
        $this->setConfig($options);

        $this->_url = $request->getAttribute('base') . $request->getRequestTarget();
        $this->_urlWithoutQuery = $this->_stripQueryString($this->_url);
    }

    /**
     * @inheritDoc
     */
    public function matchItem(ItemInterface $item): ?bool
    {
        $routes = $item->getExtra('routes');
        if ($routes === null) {
            return null;
        }

        $ignoreQueryString = $item->getExtra('ignoreQueryString');
        if ($ignoreQueryString === null) {
            $ignoreQueryString = $this->getConfig('ignoreQueryString');
        }

        foreach ($routes as $route) {
            $url = Router::url($route);

            if (
                $ignoreQueryString &&
                $this->_stripQueryString($url) === $this->_urlWithoutQuery
            ) {
                return true;
            }

            if ($url === $this->_url) {
                return true;
            }
        }

        return null;
    }

    /**
     * Removes the query string from the given URL.
     *
     * @param string $url The URL from which to strip the query string.
     * @return string The URL without query string.
     */
    protected function _stripQueryString(string $url): string
    {
        if (strpos($url, '?') !== false) {
            return explode('?', $url, 2)[0];
        }

        return $url;
    }
}
