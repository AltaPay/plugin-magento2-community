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
use Magento\Framework\DataObject;
use SDM\Altapay\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\Order;

class Request extends Index
{
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
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->writeLog();
        $order       = $this->order->load($this->getRequest()->getParam('orderid'));
        $payment     = $order->getPayment();
        $paymentType = $this->getRequest()->getParam('paytype');
        if (empty($paymentType)) {
            $paymentType = $payment->getMethod();
        }
        
        if ($this->checkPost()) {
            $params = $this->gateway->createRequest(
                $paymentType,
                $this->getRequest()->getParam('orderid')
            );
            
            $result = new DataObject();
            $result->addData($params);
            $response = $this->getResponse();
            
            return $response->representJson($result->toJson());
        }
    }
}
