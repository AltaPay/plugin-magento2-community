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

class ApplePayResponse extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * ApplePayResponse constructor.
     *
     * @param Context         $context
     * @param Session         $checkoutSession
     * @param Order           $orderRepository
     * @param Gateway         $gateway
     * @param RedirectFactory $redirectFactory
     * @param Order           $order
     * @param Random          $random
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Order $orderRepository,
        Gateway $gateway,
        RedirectFactory $redirectFactory,
        Order $order,
        Random $random
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->gateway          = $gateway;
        $this->redirectFactory  = $redirectFactory;
        $this->order            = $order;
        $this->random           = $random;
        $this->_orderRepository = $orderRepository;
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
        if ($this->checkPost()) {
            $params = $this->gateway->createRequestApplepay(
                $this->getRequest()->getParam('paytype'),
                $orderId,
                $this->getRequest()->getParam('providerData')
            );
            $response = $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($params);
            
            return $response;
        }
    }

    /**
     * @return mixed
     */
    public function checkPost()
    {
        return $this->getRequest()->isPost();
    }

}
