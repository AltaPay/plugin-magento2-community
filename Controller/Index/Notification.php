<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use SDM\Valitor\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Notification extends Index implements CsrfAwareActionInterface
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
     * @return string
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->writeLog();
        $status         = '';
        $resultRedirect = '';
        $msg            = '';

        try {
            if ($this->checkPost()) {
                $post = $this->getRequest()->getParams();
                //Set order status, if available from the payment gateway
                $merchantError                = '';
                $status                       = strtolower($post['status']);
                $cardHolderMessageMustBeShown = false;

                if (isset($post['cardholder_message_must_be_shown'])) {
                    $cardHolderMessageMustBeShown = $post['cardholder_message_must_be_shown'];
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

                if ($status == "cancelled") {
                    $msg = "Payment canceled";
                }
                $this->handleNotification($status, $msg, $merchantError);
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($status != 'success' || $status != 'succeeded') {
            $resultRedirect = $this->prepareRedirect('checkout/cart', [], $msg);
        }

        return $resultRedirect;
    }

    /**
     * @param $status
     * @param $msg
     * @param $merchantError
     *
     * @throws \Exception
     */
    private function handleNotification($status, $msg, $merchantError)
    {
        switch ($status) {
            case "cancelled":
                $this->generator->handleCancelStatusAction($this->getRequest(), $status);
                break;
            case "error":
            case "failed":
                $this->generator->handleFailedStatusAction($this->getRequest(), $msg, $merchantError, $status);
                break;
            case "succeeded":
            case "success":
                $this->generator->handleNotificationAction($this->getRequest());
                break;
            default:
                $this->generator->handleCancelStatusAction($this->getRequest(), $status);
        }
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
        if ($message != '') {
            $this->messageManager->addErrorMessage(__($message));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->_url->getUrl($routePath, $routeParams));

        return $resultRedirect;
    }
}
