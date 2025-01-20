<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use SDM\Altapay\Logger\Logger;

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
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;
    
    /**
     * @var Logger
     */
    private $altapayLogger;

    /**
     *  ApplePayResponse constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param Order $order
     * @param QuoteFactory $quoteFactory
     * @param OrderManagementInterface $orderManagement
     * @param Logger $altapayLogger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Order $order,
        QuoteFactory $quoteFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        Logger $altapayLogger
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->order            = $order;
        $this->_quoteFactory   = $quoteFactory;
        $this->orderManagement = $orderManagement;
        $this->_orderRepository = $orderRepository;
        $this->altapayLogger = $altapayLogger;
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
        $orderId = $this->_checkoutSession->getLastOrderId();
        if ($orderId) {
            $order = $this->_orderRepository->get($orderId);
            $order->addStatusHistoryComment("Apple Pay payment status - Pending");
            $order->setStatus(Order::STATE_PENDING_PAYMENT);
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
        } else {
            $this->altapayLogger->addDebugLog('Exception', 'OrderId does not exist'); 
        }
    }
}
