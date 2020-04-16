<?php
declare(strict_types=1);

/**
 * A KnpMenu seasoned menu plugin for CakePHP.
 *
 * @see https://github.com/icings/menu
 */

namespace Icings\Menu\Integration;

use Knp\Menu\Factory\ExtensionInterface;
use Knp\Menu\ItemInterface;

/**
 * An extension that complements the string template renderer functionality.
 */
class TemplaterExtension implements ExtensionInterface
{
    /**
     * @inheritDoc
     */
    public function buildOptions(array $options = []): array
    {
        if (!empty($options['templates'])) {
            $options['extras']['templates'] = $options['templates'];
            unset($options['templates']);
        }
        if (!empty($options['defaultTemplates'])) {
            $options['extras']['defaultTemplates'] = $options['defaultTemplates'];
            unset($options['defaultTemplates']);
        }

        if (!empty($options['templateVars'])) {
            $options['extras']['templateVars'] = $options['templateVars'];
            unset($options['templateVars']);
        }
        if (!empty($options['defaultTemplateVars'])) {
            $options['extras']['defaultTemplateVars'] = $options['defaultTemplateVars'];
            unset($options['defaultTemplateVars']);
        }

        if (isset($options['menuAttributes'])) {
            if (!isset($options['childrenAttributes'])) {
                $options['childrenAttributes'] = $options['menuAttributes'];
            } else {
                $options['childrenAttributes'] = $options['menuAttributes'] + $options['childrenAttributes'];
            }
            unset($options['menuAttributes']);
        }

        if (isset($options['textAttributes'])) {
            if (!isset($options['labelAttributes'])) {
                $options['labelAttributes'] = $options['textAttributes'];
            } else {
                $options['labelAttributes'] = $options['textAttributes'] + $options['labelAttributes'];
            }
            unset($options['textAttributes']);
        }

        if (isset($options['nestAttributes'])) {
            if (!isset($options['childrenAttributes'])) {
                $options['childrenAttributes'] = $options['nestAttributes'];
            } else {
                $options['childrenAttributes'] = $options['nestAttributes'] + $options['childrenAttributes'];
            }
            unset($options['nestAttributes']);
        }

        if (isset($options['escape'])) {
            $options['extras']['escape'] = $options['escape'];
            unset($options['escape']);
        }
        if (isset($options['escapeLabel'])) {
            $options['extras']['escapeLabel'] = $options['escapeLabel'];
            unset($options['escapeLabel']);
        }

        if (isset($options['inheritItemClasses'])) {
            $options['extras']['inheritItemClasses'] = $options['inheritItemClasses'];
            unset($options['inheritItemClasses']);
        }
        if (isset($options['consumeItemClasses'])) {
            $options['extras']['consumeItemClasses'] = $options['consumeItemClasses'];
            unset($options['consumeItemClasses']);
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
