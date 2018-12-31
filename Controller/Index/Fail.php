<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */
namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use SDM\Altapay\Controller\Index;

/**
 * Class Fail
 * @package SDM\Altapay\Controller\Index
 */
class Fail extends Index
{

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->writeLog();
        $url = '';
        try {
            $this->generator->restoreOrderFromRequest($this->getRequest());
            $post = $this->getRequest()->getPostValue();
            if (isset($post['error_message'])) {
                $msg = $post['error_message'];
            } else {
                $msg = 'Unknown response';
            }

            switch ($post['status']) {
                case 'cancelled':
                    $msg = "Payment canceled";
                    $this->generator->handleCancelStatusAction($this->getRequest());
                    break;
                case ('failed' || 'error'):
                    $this->generator->handleFailedStatusAction($this->getRequest());
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
    private function prepareRedirect($routePath, $routeParams = null, $message = '')
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
