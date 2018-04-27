<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Matcher\Voter;

use Cake\Core\InstanceConfigTrait;
use Cake\Http\ServerRequest;
use Cake\Network\Request;
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
        'ignoreQueryString' => true
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
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * Returns the request target URL to match against without query string.
     *
     * @return string
     */
    public function getUrlWithoutQuery()
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
     * @param Request|ServerRequest $request The request object from where to extract the request
     *   target to match against.
     * @param array $options An array of options, see the "Options" section in the method
     *   description.
     */
    public function __construct($request, array $options = [])
    {
        $this->setConfig($options);

        $this->_url = $this->_urlWithoutQuery = $request->getAttribute('base') . $request->getRequestTarget();
        if (strpos($this->_urlWithoutQuery, '?') !== false) {
            $this->_urlWithoutQuery = explode('?', $this->_urlWithoutQuery, 2)[0];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function matchItem(ItemInterface $item)
    {
        $urls = $item->getExtra('urls');
        if ($urls === null) {
            return null;
        }

        $ignoreQueryString = $item->getExtra('ignoreQueryString');
        if ($ignoreQueryString === null) {
            $ignoreQueryString = $this->getConfig('ignoreQueryString');
        }

        if ($ignoreQueryString) {
            return in_array($this->_urlWithoutQuery, $urls['withoutQuery'], true);
        }

        return in_array($this->_url, $urls['original'], true);
    }
}
