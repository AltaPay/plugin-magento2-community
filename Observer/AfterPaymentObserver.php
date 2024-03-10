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
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use SDM\Altapay\Model\SystemConfig;
use SDM\Altapay\Model\Gateway;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use SDM\Altapay\Helper\Data;

class AfterPaymentObserver implements ObserverInterface
{
    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * @var Helper Data
     */
    private $helper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var StateInterface
     */
    protected $inlineTranslation;
    /**
     * @var Gateway
     */
    protected $gateway;
    
    /**
     * BeforePaymentObserver constructor.
     *
     * @param SystemConfig          $systemConfig
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder      $transportBuilder
     * @param StateInterface        $inlineTranslation
     * @param ScopeConfigInterface  $scopeConfig
     * @param Data                  $helper
     * @param Gateway               $gateway
     */
    public function __construct(
        SystemConfig $systemConfig,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Gateway $gateway
        )
    {
        $this->systemConfig = $systemConfig;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->helper          = $helper;
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
        $storeId = $order->getStoreId();
        $storeScope = ScopeInterface::SCOPE_STORE;
        $email = $this->scopeConfig->getValue('trans_email/ident_sales/email', $storeScope);
        $name  = $this->scopeConfig->getValue('trans_email/ident_sales/name', $storeScope);
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $terminalCode = $method->getCode();
        if (in_array($terminalCode, $this->helper->getTerminalCodes()) && !$order->getRemoteIp()){
            $params = $this->gateway->createRequest(
                $terminalCode[strlen($terminalCode)-1],
                $order->getId()
            );

            if($params['result'] === 'success') {
                $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId);
                $templateVars = array(
                                    'store' => $order->getStore(),
                                    'customer_name' => $order->getCustomerName(),
                                    'formurl'    => $params['formurl']
                                );
                $from = array('email' => $email, 'name' => $name);
                $this->inlineTranslation->suspend();
                $to = array($order->getCustomerEmail());
                $templateId = $this->scopeConfig->getValue('payment/sdm_altapay_config/general/payment_template', $storeScope);

                $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
                                ->setTemplateOptions($templateOptions)
                                ->setTemplateVars($templateVars)
                                ->setFrom($from)
                                ->addTo($to)
                                ->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            }
        }
    }
}
