<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use SDM\Altapay\Model\Handler\CreatePaymentHandler;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\QuoteFactory;

class Cancel extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Session
     */
    protected $_checkoutSession;
    /**
     * @var Order
     */
    protected $order;
    /**
     * @var CreatePaymentHandler
     */
    protected $paymentHandler;
    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     *  ApplePayResponse constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param Order $order
     * @param CreatePaymentHandler $paymentHandler
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Order $order,
        CreatePaymentHandler $paymentHandler,
        QuoteFactory $quoteFactory
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->order            = $order;
        $this->paymentHandler   = $paymentHandler;
        $this->_quoteFactory   = $quoteFactory;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $this->paymentHandler->setCustomOrderStatus($order, Order::STATE_CANCELED, 'cancel');
        $order->addStatusHistoryComment("ApplePay payment status - ". $order->getStatus());
        $order->setIsNotified(false);
        $order->getResource()->save($order);

        $quote = $this->_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
        if ($quote->getId()) {
            $quote->setIsActive(1)->setReservedOrderId(null)->save();
            $this->_checkoutSession->replaceQuote($quote);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }
    }
}
