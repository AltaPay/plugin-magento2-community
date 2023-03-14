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
        $cardErrorMsgConfig = $this->scopeConfig->getValue(
            'payment/sdm_altapay_config/error_message/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getCode()
        );
        try {
            $this->generator->restoreOrderFromRequest($this->getRequest());
            $post                         = $this->getRequest()->getPostValue();
            $merchantError                = '';
            $status                       = strtolower($post['status']);
            $hasCardholderError           = isset($post['cardholder_error_message']);
            $shouldShowCardholderMessage  = (!isset($post['cardholder_message_must_be_shown']) || $post['cardholder_message_must_be_shown'] == "true");
            
            if($hasCardholderError && ($shouldShowCardholderMessage || $cardErrorMsgConfig)) {
                $msg = $post['cardholder_error_message'];
            } elseif(isset($post['error_message']) ) {
                $msg = $post['error_message'];
            }
            

            if (isset($post['error_message']) && isset($post['merchant_error_message'])) {
                if ($post['error_message'] != $post['merchant_error_message']) {
                    $merchantError = $post['merchant_error_message'];
                }
            }

            if (isset($post['error_message']) && $cardHolderMessageMustBeShown == "true") {
                $msg = $post['error_message'];
            } else {
                $msg = "Error with the Payment.";
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
