<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Controller;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use SDM\Altapay\Logger\Logger;
use SDM\Altapay\Model\Generator;
use SDM\Altapay\Model\Gateway;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use SDM\Altapay\Helper\Data;

/**
 * Class Index
 */
abstract class Index extends Action
{

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var Generator
     */
    protected $generator;

    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @var Logger
     */
    protected $altapayLogger;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var Random
     */
    protected $random;
    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;
    /**
     * @var Data
     */
    protected $helper;
    
    /**
     * Index constructor.
     *
     * @param Context               $context
     * @param PageFactory           $pageFactory
     * @param Order                 $order
     * @param Quote                 $quote
     * @param Session               $checkoutSession
     * @param Generator             $generator
     * @param Gateway               $gateway
     * @param Logger                $altapayLogger
     * @param EncryptorInterface    $encryptor
     * @param Random                $random
     * @param RedirectFactory       $redirectFactory
     * @param ScopeConfigInterface  $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Data                  $helper
     */
    public function __construct(
        Context               $context,
        PageFactory           $pageFactory,
        Order                 $order,
        Quote                 $quote,
        Session               $checkoutSession,
        Generator             $generator,
        Gateway               $gateway,
        Logger                $altapayLogger,
        EncryptorInterface    $encryptor,
        Random                $random,
        RedirectFactory       $redirectFactory,
        ScopeConfigInterface  $scopeConfig,
        StoreManagerInterface $storeManager,
        Data                  $helper
    
    ) {
        parent::__construct($context);
        $this->order           = $order;
        $this->quote           = $quote;
        $this->checkoutSession = $checkoutSession;
        $this->generator       = $generator;
        $this->gateway         = $gateway;
        $this->altapayLogger   = $altapayLogger;
        $this->pageFactory     = $pageFactory;
        $this->encryptor       = $encryptor;
        $this->random          = $random;
        $this->redirectFactory = $redirectFactory;
        $this->scopeConfig     = $scopeConfig;
        $this->storeManager    = $storeManager;
        $this->helper          = $helper;
    }

    /**
     * @return mixed
     */
    public function checkPost()
    {
        return $this->getRequest()->isPost();
    }

    /**
     * Write the logs to the valitoe logger.
     */
    protected function writeLog()
    {
        $calledClass = get_called_class();
        $this->altapayLogger->addDebugLog('- BEGIN', $calledClass);
        if (method_exists($this->getRequest(), 'getPostValue')) {
            $this->altapayLogger->addDebugLog('-- PostValue --', $this->getRequest()->getPostValue());
        }
        $this->altapayLogger->addDebugLog('-- Params --', $this->getRequest()->getParams());
        $this->altapayLogger->addDebugLog('- END', $calledClass);
    }

    /**
     * @param string $orderId
     *
     * @return mixed
     */
    protected function setSuccessPath($orderId)
    {
        $resultRedirect = $this->redirectFactory->create();
        if ($orderId) {
            $order = $this->order->loadByIncrementId($orderId);
            $uniqueHash = $this->random->getUniqueHash();
            $order->setAltapayOrderHash($uniqueHash);
            $order->getResource()->save($order);
            $resultRedirect->setPath('checkout/onepage/success',['success_token' => $uniqueHash]);
        } else {
            $resultRedirect->setPath('checkout/onepage/success');
        }

        return $resultRedirect;
    }
    
    /**
     * Returns the merchant error message
     *
     * @return string
     */
    protected function handleMerchantErrorMessage(): string
    {
        $post  = $this->getRequest()->getPostValue();
        if (isset($post['error_message']) && isset($post['merchant_error_message'])) {
            if ($post['error_message'] != $post['merchant_error_message']) {
                return $post['merchant_error_message'];
            }
        }
        return "";
    }
    
    /**
     * Returns the error_message or cardholder error message
     *
     * @return mixed|string|null
     */
    protected function handleErrorMessage() {
        $msg = "Error with the Payment.";
        $post  = $this->getRequest()->getPostValue();
        $cardholderErrorMessage = $this->generator->getCardHolderErrorMessage($this->getRequest());
        $shouldShowCardholderMessage = (bool)($this->getRequest()->getPost('cardholder_message_must_be_shown') === "true");
        $cardErrorMsgConfig = (bool)$this->scopeConfig->getValue(
            'payment/sdm_altapay_config/error_message/enable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getCode()
        );
        if (isset($post['error_message']) && $cardholderErrorMessage === null) {
            $msg = $post['error_message'];
        } elseif ($cardholderErrorMessage && ($shouldShowCardholderMessage || $cardErrorMsgConfig)) {
            $msg = $cardholderErrorMessage;
        }
        
        return $msg;
    }
    
    /**
     * @param $post
     * @param $secret
     *
     * @return bool
     */
    protected function validateChecksum($post, $secret) {
        if (!empty($secret) && !empty($post['checksum'])) {
            $checksumData = $this->helper->calculateCheckSum($post, $secret);
            if ($post['checksum'] != $checksumData) {
                $this->altapayLogger->addCriticalLog('Exception', 'Checksum validation failed!');
                return false;
            }
        }
        return true;
    }
}
