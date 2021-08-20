<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;

/**
 * Class Data for helper functions
 */
class Data extends AbstractHelper
{
    const MODULE_CODE = 'SDM_Altapay';
    /**
     * @var moduleList
     */
    protected $moduleList;
    /**
     * @var productMetadata
     */
    protected $productMetadata;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var taxItem
     */
    protected $taxItem;

    /**
     * Data constructor.
     *
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $productMetadata
     * @param ScopeConfigInterface     $scopeConfig
     * @param Order                    $order
     * @param Item                     $taxItem
     */
    public function __construct(
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        ScopeConfigInterface $scopeConfig,
        Order $order,
        Item $taxItem
    ) {
        $this->moduleList      = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->scopeConfig     = $scopeConfig;
        $this->order           = $order;
        $this->taxItem         = $taxItem;
    }

    //Method for adding transaction info

    /**
     * @param $orderId
     *
     * @return array
     */
    public function transactionDetail($orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $storeName                           = $order->getStore()->getName();
            $websiteName                         = $order->getStore()->getWebsite()->getName();
            $moduleInfo                          = $this->moduleList->getOne(self::MODULE_CODE);
            $versionDetails                      = [];
            $versionDetails['ecomPlatform']      = 'Magento';
            $versionDetails['ecomVersion']       = $this->productMetadata->getVersion();
            $versionDetails['ecomPluginName']    = $moduleInfo['name'];
            $versionDetails['ecomPluginVersion'] = $moduleInfo['setup_version'];
            $versionDetails['otherInfo']         = 'websiteName - ' . $websiteName . ', storeName - ' . $storeName;

            return $versionDetails;
        }
    }

    /**
     * @param $orderId
     *
     * @return mixed
     */
    public function getPaymentTitleTerminal($orderId)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $order      = $this->order->load($orderId);
        $storeCode  = $order->getStore()->getCode();
        $storeId    = $order->getStore()->getId();
        $payment    = $order->getPayment();
        $method     = $payment->getMethodInstance();
        $title      = $method->getConfigData('title', $storeId);
        $terminalID = $payment->getMethod();
        if ($title == null) {
            $terminalTitle = $this->scopeConfig->getValue(
                'payment/' . $terminalID . '/terminalname',
                $storeScope,
                $storeCode
            );
        } else {
            $terminalTitle = $title;
        }

        return $terminalTitle;
    }

    /**
     * @param $orderID
     *
     * @return int
     */
    public function getOrderShippingTax($orderID)
    {
        $shippingTaxPercent = 0;
        $tax_items          = $this->taxItem->getTaxItemsByOrderId($orderID);
        if (!empty($tax_items) && is_array($tax_items)) {
            foreach ($tax_items as $item) {
                if ($item['taxable_item_type'] === 'shipping') {
                    $shippingTaxPercent += $item['tax_percent'];
                }
            }
        }

        return $shippingTaxPercent;
    }

    /**
     * @return array
     */
    public function getTerminalCodes()
    {
        return [
            \SDM\Altapay\Model\Method\Terminal1::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal2::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal3::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal4::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal5::METHOD_CODE,
        ];
    }
}
