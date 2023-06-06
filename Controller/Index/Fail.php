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
use SDM\Altapay\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Fail extends Index implements CsrfAwareActionInterface
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
        $status = '';
        try {
            $this->generator->restoreOrderFromRequest($this->getRequest());
            $post          = $this->getRequest()->getPostValue();
            $status        = strtolower($post['status']);
            $merchantError = $this->handleMerchantErrorMessage();
            $msg           = $this->handleErrorMessage();
        
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
            if (!empty($secret) && !empty($post['checksum'])) {
                $checksumData =
                    $this->helper->calculateCheckSum($post, $secret);
                if ($post['checksum'] != $checksumData) {
                    $this->altapayLogger->addCriticalLog('Exception',
                        'Checksum validation failed!');
                    return;
                }
            }
            
            //Set order status, if available from the payment gateway
            switch ($status) {
                case "cancelled":
                    //TODO: Overwrite the message
                    $msg = "Payment canceled";
                    $this->generator->handleCancelStatusAction($this->getRequest(), $status);
                    break;
                case "failed":
                case "error":
                case "incomplete":
                    $this->generator->handleFailedStatusAction($this->getRequest(), $msg, $merchantError, $status);
                    break;
                default:
                    $this->generator->handleOrderStateAction($this->getRequest());
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($status == 'failed' || $status == 'error' || $status == 'cancelled' || $status == 'incomplete') {
            $resultRedirect = $this->prepareRedirect('checkout/cart', [], $msg);
        } else {
            $resultRedirect = $this->prepareRedirect('checkout', ['_fragment' => 'payment'], $msg);
        }

        return $resultRedirect;
    }

    /**
     * @param        $routePath
     * @param null   $routeParams
     * @param string $message
     *
     * @return mixed
     */
    protected function prepareRedirect($routePath, $routeParams = null, $message = '')
    {
        if (!empty($message)) {
            $this->messageManager->addErrorMessage(__($message));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->_url->getUrl($routePath, $routeParams));

        return $resultRedirect;
    }
}
