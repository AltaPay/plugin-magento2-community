<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SDM\Valitor\Model\Handler\RestoreQuote;

class CheckoutCartIndex implements ObserverInterface
{

    /** @var \Magento\Checkout\Model\Session */
    private $restoreQuote;

    /**
     * CheckoutCartIndex Constructor
     *
     * @param RestoreQuote $restoreQuote
     */
    public function __construct(RestoreQuote $restoreQuote)
    {
        $this->restoreQuote = $restoreQuote;
    }

    /**
     * @param Observer                          $observer
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->restoreQuote->handleQuote();
    }
}
