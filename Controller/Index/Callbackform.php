<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use SDM\Altapay\Helper\Data;
use SDM\Altapay\Logger\Logger;

class Callbackform extends Action implements CsrfAwareActionInterface
{
    
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    
    /**
     * @var Order
     */
    protected $order;
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var Data
     */
    protected $helper;
    
    /**
     * @var Logger
     */
    protected $altapayLogger;
    
    /**
     * Callbackform constructor
     *
     * @param Context              $context
     * @param PageFactory          $resultPageFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Data                 $helper
     * @param Order                $order
     * @param Logger               $altapayLogger
     */
    public function __construct(
        Context              $context,
        PageFactory          $resultPageFactory,
        ScopeConfigInterface $scopeConfig,
        Data                 $helper,
        Logger               $altapayLogger,
        Order                $order
    ) {
        $this->scopeConfig       = $scopeConfig;
        $this->order             = $order;
        $this->resultPageFactory = $resultPageFactory;
        $this->helper            = $helper;
        $this->altapayLogger     = $altapayLogger;
        parent::__construct($context);
    }

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
        $storeScope   = ScopeInterface::SCOPE_STORE;
        $post         = $this->getRequest()->getPostValue();
        $orderId      = $post['shop_orderid'];
        $order        = $this->order->loadByIncrementId($orderId);
        $storeCode    = $order->getStore()->getCode();
        $payment      = $order->getPayment();
        $terminalCode = $payment->getMethod();
        $secret       = $this->scopeConfig->getValue(
            'payment/' . $terminalCode . '/terminalsecret',
            $storeScope,
            $storeCode
        );
        // Verify if the secret matches with the gateway
        if (!empty($secret) && !empty($post['checksum'])) {
             $checksumData = $this->helper->calculateCheckSum($post, $secret);
            if ($post['checksum'] != $checksumData) {
                $this->altapayLogger->addCriticalLog('Exception', 'Checksum validation failed!');
                return;
            }
        }
        
        return $this->resultPageFactory->create();
    }
}
