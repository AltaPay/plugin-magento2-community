<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use SDM\Altapay\Model\SystemConfig;
use SDM\Altapay\Model\Gateway;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class AfterPaymentObserver implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * BeforePaymentObserver constructor.
     *
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        SystemConfig $systemConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        Gateway $gateway
        )
    {
        $this->systemConfig = $systemConfig;
        $this->storeManager = $storeManager;    
        $this->scopeConfig = $scopeConfig; 
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->gateway      = $gateway;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $email = $this->scopeConfig->getValue('trans_email/ident_sales/email',ScopeInterface::SCOPE_STORE);
        $name  = $this->scopeConfig->getValue('trans_email/ident_sales/name',ScopeInterface::SCOPE_STORE);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $terminalCode = $method->getCode();
        $params = $this->gateway->createRequest(
            $terminalCode[strlen($terminalCode)-1],
            $order->getId()
        );

        if($params['result'] === 'success') {
            $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
            $templateVars = array(
                                'store' => $this->storeManager->getStore(),
                                'customer_name' => $order->getCustomerName(),
                                'message'    => $params['formurl']
                            );
            $from = array('email' => $email, 'name' => $name);
            $this->inlineTranslation->suspend();
            $to = array($order->getCustomerEmail());
            $transport = $this->transportBuilder->setTemplateIdentifier('payment_template')
                            ->setTemplateOptions($templateOptions)
                            ->setTemplateVars($templateVars)
                            ->setFrom($from)
                            ->addTo($to)
                            ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();

        } else {
            
        }
    }
}
