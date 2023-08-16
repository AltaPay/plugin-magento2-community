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
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Framework\DataObject;
use SDM\Altapay\Model\ReconciliationIdentifierFactory;

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
     * @var ReconciliationIdentifierFactory
     */
    private $reconciliation;

    /**
     * Data constructor.
     *
     * @param ModuleListInterface             $moduleList
     * @param ProductMetadataInterface        $productMetadata
     * @param ScopeConfigInterface            $scopeConfig
     * @param Order                           $order
     * @param Item                            $taxItem
     * @param ReconciliationIdentifierFactory $reconciliation
     */
    public function __construct(
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        ScopeConfigInterface $scopeConfig,
        Order $order,
        Item $taxItem,
        ReconciliationIdentifierFactory $reconciliation
    ) {
        $this->moduleList       = $moduleList;
        $this->productMetadata  = $productMetadata;
        $this->scopeConfig      = $scopeConfig;
        $this->order            = $order;
        $this->taxItem          = $taxItem;
        $this->reconciliation   = $reconciliation;
    }

    //Method for adding transaction info

    /**
     * @param $orderId
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function transactionDetail($orderId)
    {
        $versionDetails                          = [];
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $storeName                           = $order->getStore()->getName();
            $websiteName                         = $order->getStore()->getWebsite()->getName();
            $moduleInfo                          = $this->moduleList->getOne(self::MODULE_CODE);
            $versionDetails['ecomPlatform']      = 'Magento';
            $versionDetails['ecomVersion']       = $this->productMetadata->getVersion();
            $versionDetails['ecomPluginName']    = $moduleInfo['name'];
            $versionDetails['ecomPluginVersion'] = $moduleInfo['setup_version'];
            $versionDetails['otherInfo']         = 'websiteName - ' . $websiteName . ', storeName - ' . $storeName;
        }
        
        return $versionDetails;
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
            \SDM\Altapay\Model\Method\Terminal6::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal7::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal8::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal9::METHOD_CODE,
            \SDM\Altapay\Model\Method\Terminal10::METHOD_CODE,
        ];
    }

    /**
     * @param AbstractItem $item
     * @return DataObject
     */

    public function getBuyRequestObject(AbstractItem $item)
    {
        /** @var DataObject $request */
        $request = $item->getBuyRequest();
        if (!$request && $item->getQuoteItem()) {
            $request = $item->getQuoteItem()->getBuyRequest();
        }
        if (!$request) {
            $request = new DataObject();
        }

        if (is_array($request)) {
            $request = new DataObject($request);
        }

        return $request;
    }

    /**
     * @param AbstractItem $item
     * @return bool
     */
    public function isSubscription(AbstractItem $item)
    {
        $buyRequest = $this->getBuyRequestObject($item);

        return $buyRequest->getData('subscribe') === 'subscribe';
    }

    /**
     * @param $quote
     * @return bool
     */
    public function validateQuote($data): bool
    {
        $isRecurring = false;
        $items = $data->getAllItems();

        /** @var Item $item */
        foreach ($items as $item) {
            if ($this->isSubscription($item)) {
                $isRecurring = true;
                break;
            }
        }

        return $isRecurring;
    }

    /**
     * @param string $orderId
     * @param string $identifier
     * @return mixed
     */
    public function getReconciliationData($orderId, $identifier = ''){
        $collection = $this->reconciliation->create()->getCollection()
             ->addFieldToFilter('order_id', $orderId);

        if($identifier){
            $collection->addFieldToFilter('identifier', $identifier);
        }

        return $collection;
    }
    
    /**
     * @param $post
     * @param $secret
     *
     * @return string
     */
    public function calculateCheckSum($post, $secret)
    {
        $inputData = [
            'amount' => $post['amount'],
            'currency' => $post['currency'],
            'shop_orderid' => $post['shop_orderid']
        ];
        $inputData['secret'] = $secret;
        ksort($inputData);
        $data = array();
        foreach ($inputData as $name => $value) {
            $data[] = $name . "=" . $value;
        }
        return md5(join(',', $data));
    }

    /**
     * @param $transactions
     * @param string $authType
     * @return int|string
     */
    public function getLatestTransaction($transactions, $authType = ''){
        $latestDate     = '';
        $latestTransKey = 0;
        foreach ($transactions as $key=>$value) {
            $isLatest = ($authType && $value->AuthType === $authType && $value->CreatedDate > $latestDate) ||
                (!$authType && $value->CreatedDate > $latestDate);

            if ($isLatest) {
                $latestDate     = $value->CreatedDate;
                $latestTransKey = $key;
            }
        }

        return $latestTransKey;
    }
}
