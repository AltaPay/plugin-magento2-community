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
use Magento\Framework\Controller\ResultFactory;
use SDM\Valitor\Controller\Index;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

/**
 * Class Fail
 * @package SDM\Valitor\Controller\Index
 */
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

        try {
            $this->generator->restoreOrderFromRequest($this->getRequest());
            $post = $this->getRequest()->getPostValue();
            $merchantErrorMsg = '';
            $responseStatus = strtolower($post['status']);
            if (isset($post['error_message'])) {
                $msg = $post['error_message'];
                if ($post['error_message'] != $post['merchant_error_message']) {
                    $merchantErrorMsg = $post['merchant_error_message'];
                }
                $responseStatus = $post['status'];
            } else {
                $msg = 'Unknown response';
            }

            //Set order status, if available from the payment gateway
            switch ($post['status']) {
                case 'cancelled':
                    //TODO: Overwrite the message
                    $msg = "Payment canceled";
                    $this->generator->handleCancelStatusAction($this->getRequest(), $responseStatus);
                    break;
                case 'failed':
                case 'error':
                    $this->generator->handleFailedStatusAction($this->getRequest(), $msg, $merchantErrorMsg, $responseStatus);
                    break;

                default:
                    $this->generator->handleOrderStateAction($this->getRequest());
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($post['status'] == 'failed' || $post['status'] == 'error') {
            $resultRedirect = $this->prepareRedirect('checkout/cart', array(), $msg);
        } else {
            $resultRedirect = $this->prepareRedirect('checkout', array('_fragment' => 'payment'), $msg);
        }

        return $resultRedirect;
    }

    /**
     * @param $routePath
     * @param null $routeParams
     * @param string $message
     * @return mixed
     */
    protected function prepareRedirect($routePath, $routeParams = null, $message = '')
    {
        if ($message != '') {
            $this->messageManager->addErrorMessage(__($message));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $customerRedirUrl = $this->_url->getUrl($routePath, $routeParams);
        $resultRedirect->setPath($customerRedirUrl);

        return $resultRedirect;
    }
}
