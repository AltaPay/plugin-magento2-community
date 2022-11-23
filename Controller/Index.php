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
use SDM\Altapay\Model\ReconciliationIdentifierFactory;
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
     * @var ReconciliationIdentifierFactory
     */
    protected $reconciliation;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Order $order
     * @param Quote $quote
     * @param Session $checkoutSession
     * @param Generator $generator
     * @param Gateway $gateway
     * @param Logger $altapayLogger
     * @param EncryptorInterface $encryptor
     * @param Random $random
     * @param RedirectFactory $redirectFactory
     * @param ReconciliationIdentifierFactory $reconciliation
     */
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Order $order,
        Quote $quote,
        Session $checkoutSession,
        Generator $generator,
        Gateway $gateway,
        Logger $altapayLogger,
        EncryptorInterface $encryptor,
        Random $random,
        RedirectFactory $redirectFactory,
        ReconciliationIdentifierFactory $reconciliation
        
    ) {
        parent::__construct($context);
        $this->order            = $order;
        $this->quote            = $quote;
        $this->checkoutSession  = $checkoutSession;
        $this->generator        = $generator;
        $this->gateway          = $gateway;
        $this->altapayLogger    = $altapayLogger;
        $this->pageFactory      = $pageFactory;
        $this->encryptor        = $encryptor;
        $this->random           = $random;
        $this->redirectFactory  = $redirectFactory;
        $this->reconciliation   = $reconciliation;
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
     * @param $transactions
     * @param $authType
     * @return int|string
     */
    protected function getLatestTransaction($transactions) {
        $max_date = '';
        $latestTransKey = '';
        foreach ($transactions as $key=>$transaction) {
            if ($transaction['CreatedDate'] > $max_date) {
                $max_date = $transaction['CreatedDate'];
                $latestTransKey = $key;
            }
        }
        return $latestTransKey;
    }
}
