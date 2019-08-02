<?php

namespace SDM\Valitor\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\Order;

class Data extends AbstractHelper
{
    const MODULE_CODE = 'SDM_Valitor';
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

    public function __construct(
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        Order $order
    ) {
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->order = $order;
    }
    //Method for adding transaction info
    public function transactionDetail($orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            $storeName = $order->getStore()->getName();
            $websiteName = $order->getStore()->getWebsite()->getName();
            $versionDetails = array();
            $magentoVersion = $this->productMetadata->getVersion();
            $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);
            $versionDetails['ecomPlatform'] = 'Magento';
            $versionDetails['ecomVersion'] = $magentoVersion;
            $versionDetails['valitorPluginName'] = $moduleInfo['name'];
            $versionDetails['valitorPluginVersion'] = $moduleInfo['setup_version'];
            $versionDetails['otherInfo'] = 'websiteName - '.$websiteName.', storeName - '.$storeName;
            return $versionDetails;
        }
    }
}
