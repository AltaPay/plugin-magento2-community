<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use SDM\Altapay\Model\Gateway;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use SDM\Altapay\Api\Payments\ApplepayWalletAuthorize;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Math\Random;

class ApplepayResponse extends Action implements CsrfAwareActionInterface
{
    /**
     * @var Order
     */
    protected $order;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $orderRepository,
        Gateway $gateway,
        RedirectFactory $redirectFactory,
        Order $order,
        Random $random,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->gateway         = $gateway;
        $this->redirectFactory = $redirectFactory;
        $this->order           = $order;
        $this->random          = $random;
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

                echo json_encode($params);
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
     * @param string $orderId
     *
     * @return mixed
     */
    protected function setSuccessPath($orderId)
    {
        $writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/logging.log');
$logger = new \Laminas\Log\Logger();
$logger->addWriter($writer);
$logger->info(print_r("orderId ".$orderId." orderId",true));

        $resultRedirect = $this->redirectFactory->create();
        if ($orderId) {
            $order = $this->order->loadByIncrementId($orderId);
            $uniqueHash = $this->random->getUniqueHash();
            $order->setAltapayOrderHash($uniqueHash);
            $order->getResource()->save($order);
            $resultRedirect->setPath('checkout/onepage/success',['success_token' => $uniqueHash]);
        } else {
            $resultRedirect->setPath('checkout/onepage/success');
        }

        $logger->info(print_r("resultRedirect",true));

        return $resultRedirect;
    }

}
