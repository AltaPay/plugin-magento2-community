<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */

namespace SDM\Valitor\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use SDM\Valitor\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Class Notification
 *
 * @package SDM\Valitor\Controller\Index
 */
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
        $responseStatus = '';
        $resultRedirect = '';
        $msg            = '';

        try {
            if ($this->checkPost()) {
                $post = $this->getRequest()->getParams();
                //Set order status, if available from the payment gateway
                $merchantErrorMsg = '';
                $responseStatus   = strtolower($post['status']);
                if (isset($post['error_message'])) {
                    $msg = $post['error_message'];
                    if ($post['error_message'] != $post['merchant_error_message']) {
                        $merchantErrorMsg = $post['merchant_error_message'];
                    }
                }

                switch ($responseStatus) {
                    case "cancelled":
                        $msg = "Payment canceled";
                        $this->generator->handleCancelStatusAction($this->getRequest(), $responseStatus);
                        break;
                    case "failed":
                    case "error":
                        $this->generator->handleFailedStatusAction($this->getRequest(), $msg, $merchantErrorMsg, $responseStatus);
                        break;
                    case "success":
                    case "succeeded":
                        $this->generator->handleNotificationAction($this->getRequest());
                        break;
                    default:
                        $this->generator->handleCancelStatusAction($this->getRequest(), $responseStatus);
                }
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($responseStatus != 'success' || $responseStatus != 'succeeded') {
            $resultRedirect = $this->prepareRedirect('checkout/cart', array(), $msg);
        }

        return $resultRedirect;
    }

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
