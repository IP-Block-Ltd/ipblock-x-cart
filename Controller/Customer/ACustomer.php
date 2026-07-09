<?php
/**
 * IP Block Protection - storefront request guard (decorator).
 */

namespace XLite\Module\IpBlock\Protection\Controller\Customer;

/**
 * Decorates the base customer controller so the IP screen runs as early as
 * possible on EVERY storefront request. Because only the customer-area base
 * controller is decorated, the admin back office (\XLite\Controller\Admin\*)
 * is never affected - the operator can never be locked out.
 */
abstract class ACustomer extends \XLite\Controller\Customer\ACustomer implements \XLite\Base\IDecorator
{
    /**
     * Entry point for handling a storefront request. We screen the visitor
     * before the normal controller logic runs, then hand off to the parent.
     *
     * @return void
     */
    public function handleRequest()
    {
        \XLite\Module\IpBlock\Protection\Core\IpBlockChecker::getInstance()->guard();

        parent::handleRequest();
    }
}
