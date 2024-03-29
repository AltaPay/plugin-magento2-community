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
use Magento\Framework\Exception\LocalizedException;

class Ok extends Index implements CsrfAwareActionInterface
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
     * @throws \Exception
     */
    /**
     * @return void
     */
    public function execute()
    {
        $this->writeLog();
        $checkAvs     = false;
        $checkFraud   = false;
        $post         = $this->getRequest()->getPostValue();
        $orderId      = $post['shop_orderid'];
        $order        = $this->order->loadByIncrementId($orderId);
        $storeCode    = $order->getStore()->getCode();
        $storeScope   = ScopeInterface::SCOPE_STORE;
        $payment      = $order->getPayment();
        $terminalCode = $payment->getMethod();
        // Retrieve the value of the secret from the store's configuration
        $secret       = $this->scopeConfig->getValue(
            'payment/' . $terminalCode . '/terminalsecret',
            $storeScope,
            $storeCode
        );
        // Verify if the secret matches with the gateway
        if (!$this->validateChecksum($post, $secret)) return;
        
        if (isset($post['avs_code']) && isset($post['avs_text'])) {
            $checkAvs = $this->generator->avsCheck(
                $this->getRequest(),
                strtolower($post['avs_code']),
                strtolower($post['avs_text'])
            );
        }
        
        if (isset($post['fraud_recommendation']) && isset($post['fraud_explanation'])) {
            $checkFraud = $this->generator->fraudCheck(
                $this->getRequest(),
                strtolower($post['fraud_recommendation']),
                $post['fraud_explanation']
            );
        }
        
        if ($this->checkPost() && $checkAvs == false && $checkFraud == false) {
            $isSuccessful = $this->generator->handleOkAction($this->getRequest());
            if (strtolower($post['type']) === "verifycard") {
                $response = $this->gateway->createRequest(
                    $terminalCode[strlen($terminalCode) - 1],
                    $orderId
                );
                if ($response['result'] === 'success') {
                    return $this->setSuccessPath($orderId);
                } else {
                    return $this->redirectToCheckoutPage();
                }
            }
            
            if (isset($isSuccessful) && !$isSuccessful) {
                return $this->redirectToCheckoutPage();
            } else {
                return $this->setSuccessPath($orderId);
            }
        } elseif ($checkFraud) {
            $this->redirectToCheckoutPage();
        } else {
            return $this->_redirect('checkout/cart');
        }
    }
    
    /**
     * @return mixed
     */
    private function redirectToCheckoutPage()
    {
        $this->_eventManager->dispatch('order_cancel_after', ['order' => $this->order]);
        $this->generator->restoreOrderFromRequest($this->getRequest());
        
        return $this->_redirect('checkout/cart');
    }
}
