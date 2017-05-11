<?php
/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

return [
    'menu' => '<nav><ul{{attrs}}>{{items}}</ul></nav>',
    'nest' => '<ul data-ul{{attrs}}>{{items}}</ul>',
    'item' => '<li data-li{{attrs}}>{{link}}{{nest}}</li>',
    'link' => '<a data-a href="{{url}}"{{attrs}}>{{label}}</a>',
    'text' => '<span data-span{{attrs}}>{{label}}</span>'
];
