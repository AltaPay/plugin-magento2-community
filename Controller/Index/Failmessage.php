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
 * Class Failmessage
 * @package SDM\Altapay\Controller\Index
 */
class Failmessage extends Index
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
        $msg = $this->getRequest()->getParam('msg');
        $this->logger->addDebugLog('messageManager - Error message', $msg);
        $this->messageManager->addErrorMessage($msg);

        return $this->_redirect('checkout', ['_fragment' => 'payment']);
    }
}
