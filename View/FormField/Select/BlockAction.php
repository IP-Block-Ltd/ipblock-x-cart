<?php
/**
 * IP Block Protection - "Block action" select field for the settings form.
 */

namespace XLite\Module\IpBlock\Protection\View\FormField\Select;

/**
 * Two-option selector: redirect (default) or HTTP 403 message.
 */
class BlockAction extends \XLite\View\FormField\Select\Regular
{
    /**
     * Available options.
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return array(
            'redirect' => 'Redirect to ip-block.com/blocked.php',
            'message'  => 'Return HTTP 403 with a message',
        );
    }
}
