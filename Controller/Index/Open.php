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
use SDM\Altapay\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Store\Model\ScopeInterface;

class Open extends Index implements CsrfAwareActionInterface
{

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
        $this->writeLog();
        $post         = $this->getRequest()->getPostValue();
        $orderId      = $post['shop_orderid'];
        $order        = $this->order->loadByIncrementId($orderId);
        $storeCode    = $order->getStore()->getCode();
        $storeScope   = ScopeInterface::SCOPE_STORE;
        $payment      = $order->getPayment();
        $terminalCode = $payment->getMethod();
        // Retrieve the value of the secret from the store's configuration
        $secret = $this->scopeConfig->getValue(
            'payment/' . $terminalCode . '/terminalsecret',
            $storeScope,
            $storeCode
        );
        // Verify if the secret matches with the gateway
        if (!$this->validateChecksum($post, $secret)) return;
        
        return $this->_redirect('checkout/onepage/success');
    }
}
