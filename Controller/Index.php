<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Controller;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use SDM\Valitor\Logger\Logger;
use SDM\Valitor\Model\Generator;
use SDM\Valitor\Model\Gateway;

/**
 * Class Index
 */
abstract class Index extends Action
{

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Generator
     */
    protected $generator;

    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @var Logger
     */
    protected $valitorLogger;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * Index constructor.
     *
     * @param Context     $context
     * @param PageFactory $pageFactory
     * @param Order       $order
     * @param Quote       $quote
     * @param Session     $checkoutSession
     * @param Generator   $generator
     * @param Gateway     $gateway
     * @param Logger      $valitorLogger
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Order $order,
        Quote $quote,
        Session $checkoutSession,
        Generator $generator,
        Gateway $gateway,
        Logger $valitorLogger
    ) {
        parent::__construct($context);
        $this->order           = $order;
        $this->quote           = $quote;
        $this->checkoutSession = $checkoutSession;
        $this->generator       = $generator;
        $this->gateway         = $gateway;
        $this->valitorLogger   = $valitorLogger;
        $this->pageFactory     = $pageFactory;
    }

    /**
     * @return mixed
     */
    public function checkPost()
    {
        return $this->getRequest()->isPost();
    }

    /**
     * Write the logs to the valitoe logger.
     */
    protected function writeLog()
    {
        $calledClass = get_called_class();
        $this->valitorLogger->addDebugLog('- BEGIN', $calledClass);
        if (method_exists($this->getRequest(), 'getPostValue')) {
            $this->valitorLogger->addDebugLog('-- PostValue --', $this->getRequest()->getPostValue());
        }
        $this->valitorLogger->addDebugLog('-- Params --', $this->getRequest()->getParams());
        $this->valitorLogger->addDebugLog('- END', $calledClass);
    }
}
