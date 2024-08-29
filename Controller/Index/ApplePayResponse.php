<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use SDM\Altapay\Model\Gateway;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Math\Random;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;

class ApplePayResponse extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * @var Random
     */
    protected $random;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var Gateway
     */
    protected $gateway;
    /**
     * @var QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * ApplePayResponse constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Gateway $gateway
     * @param RedirectFactory $redirectFactory
     * @param Order $order
     * @param Random $random
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        Gateway $gateway,
        RedirectFactory $redirectFactory,
        Order $order,
        QuoteFactory $quoteFactory,
        Random $random
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->gateway          = $gateway;
        $this->redirectFactory  = $redirectFactory;
        $this->order            = $order;
        $this->random           = $random;
        $this->_orderRepository = $orderRepository;
        $this->_quoteFactory   = $quoteFactory;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
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

    public function execute()
    {
        $orderId = $this->_checkoutSession->getLastOrderId();

        if (empty($orderId)) {
            $result = ['status' => 'error', 'message' => 'Invalid order ID'];

            return $this->createJsonResponse($result);
        }

        if ($this->checkPost()) {
            $params = $this->gateway->createRequestApplepay(
                $this->getRequest()->getParam('paytype'),
                $orderId,
                $this->getRequest()->getParam('providerData')
            );

            $status = isset($params->Result) ? strtolower($params->Result) : 'error';

            if ($status === 'error') {
                $message = (is_array($params) && isset($params['message'])) ? $params['message'] : 'error occured';
                $order = $this->_orderRepository->get($orderId);
                $orderStatus = Order::STATE_CANCELED;
                $order->setState($orderStatus)->setStatus($orderStatus);
                $order->addStatusHistoryComment($message);
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

            return $this->createJsonResponse(['status' => $status]);
        }
    }

    /**
     * @return mixed
     */
    public function checkPost()
    {
        return $this->getRequest()->isPost();
    }

    /**
     * @param $data
     * @return mixed
     */
    private function createJsonResponse($data)
    {
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($data);
    }
}
