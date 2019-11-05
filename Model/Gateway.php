<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */
namespace SDM\Valitor\Model;

use SDM\Valitor\Api\GatewayInterface;
use SDM\Valitor\Api\OrderLoaderInterface;
use SDM\Valitor\Model\ConstantConfig;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Framework\UrlInterface;
use SDM\Valitor\Request\Address;
use SDM\Valitor\Request\Customer;
use SDM\Valitor\Request\Config;
use SDM\Valitor\Api\Ecommerce\PaymentRequest;
use SDM\Valitor\Api\Test\TestAuthentication;
use SDM\Valitor\Request\OrderLine;
use SDM\Valitor\Exceptions\ClientException;
use SDM\Valitor\Exceptions\ResponseHeaderException;
use SDM\Valitor\Exceptions\ResponseMessageException;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Catalog\Helper\Data as Taxhelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use SDM\Valitor\Helper\Data;
use SDM\Valitor\Logger\Logger;
use Magento\SalesRule\Model\RuleFactory;
use \Magento\Sales\Model\ResourceModel\Order\Tax\Item;

/**
 * Class Gateway
 * @package SDM\Valitor\Model
 */
class Gateway implements GatewayInterface
{
    const MODULE_CODE = 'SDM_Altapay';
    /**
     * @var ModuleListInterface
     */
    private $moduleList;
    /**
     * @var Helper Data
     */
    private $helper;
    /**
     * @var productRepository
     */
    protected $productRepository; 
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Taxhelper
     */
    private $taxHelper;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;
    /**
     * @var Order
     */
    private $order;

    /**
     * @var SystemConfig
     */
    private $systemConfig;
    /**
     * @var OrderLoaderInterface
     */
    private $orderLoader;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var UrlInterface
     */
    private $urlInterface;
    /**
    * @var Quote
    */
    private $quote;
    /**
     * @var Logger
     */
    protected $valitorLogger;
    /**
     * @var rule
     */
    protected $rule;
    /**
     * @var taxItem
     */
    protected $taxItem;

    /**
     * Gateway constructor.
     * @param Session $checkoutSession
     * @param UrlInterface $urlInterface
     * @param Order $order
     * @param SystemConfig $systemConfig
     * @param OrderLoaderInterface $orderLoader
     * @param Quote $quote
     */
    public function __construct(
        Session $checkoutSession,
        UrlInterface $urlInterface,
        Order $order,
        SystemConfig $systemConfig,
        OrderLoaderInterface $orderLoader,
        Quote $quote,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $productMetadata,
        ProductRepositoryInterface $productRepository,
        Taxhelper $taxHelper,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        Logger $valitorLogger,
        RuleFactory $rule,
        Item $taxItem
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->urlInterface = $urlInterface;
        $this->order = $order;
        $this->systemConfig = $systemConfig;
        $this->orderLoader = $orderLoader;
        $this->quote = $quote;
        $this->moduleList = $moduleList;
        $this->productMetadata = $productMetadata;
        $this->productRepository = $productRepository;
        $this->taxHelper = $taxHelper;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->valitorLogger = $valitorLogger;
        $this->rule = $rule;
        $this->taxItem = $taxItem;
    }

    /**
      * Createrequest to valitor
      * @param int $terminalId
      * @param string $orderId 
      * @return array
      */
    public function createRequest($terminalId, $orderId)
    {
        $order = $this->order->load($orderId);
        if ($order->getId()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $storeCode = $order->getStore()->getCode();
            $couponCode = $order->getDiscountDescription();
            $appliedRule = $order->getAppliedRuleIds();
            $couponCodeAmount = number_format($order->getDiscountAmount(), 2, '.', ''); 
            //Test the conn with the Payment Gateway
            $auth = $this->systemConfig->getAuth($storeCode);
            $api = new TestAuthentication($auth);
            $response = $api->call();

            $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', $storeScope, $storeCode);
            if (! $response) {
                $this->restoreOrderFromOrderId($order->getIncrementId());
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = __(ConstantConfig::AUTH_MESSAGE);
                return $requestParams;
            }
            //Transaction Info
            $transactionDetail = $this->helper->transactionDetail($orderId);
            
            $request = new PaymentRequest($auth);
            $request
                ->setTerminal($terminalName)
                ->setShopOrderId($order->getIncrementId())
                //Grand total formated to avoid the 3 digits error in total
                ->setAmount((float) number_format($order->getGrandTotal(), 2, '.', '')) 
                ->setCurrency($order->getOrderCurrencyCode())
                ->setCustomerInfo($this->setCustomer($order))
                ->setConfig($this->setConfig())
                ->setTransactionInfo($transactionDetail);

            if ($fraud = $this->systemConfig->getTerminalConfig($terminalId, 'fraud', $storeScope, $storeCode)) {
                $request->setFraudService($fraud);
            }

            if ($lang = $this->systemConfig->getTerminalConfig($terminalId, 'language', $storeScope, $storeCode)) {
                $langArr = explode('_', $lang, 2);
                if (isset($langArr[0])) {
                    $language = $langArr[0];
                    $request->setLanguage($language);
                }
            }
            
            $autoCaptureEnable =  $this->systemConfig->getTerminalConfig($terminalId, 'capture', $storeScope, $storeCode);
            if ($autoCaptureEnable) {
                $request->setType('paymentAndCapture');
            }

            $orderlines = [];
            $sendShipment = false;
            //get shipping information
            $compAmount = $order->getShippingDiscountTaxCompensationAmount();
            $shippingTax = $order->getShippingTaxAmount();
            $shippingAmount = $order->getShippingAmount();
            $shippingTaxPercent = $this->getOrderShippingTax($order->getId());

            $shippingDiscounts = array();
            $itemDiscount = 0;
            $discountOnAllItems = $this->allItemsHaveDiscount($order->getAllVisibleItems());


            /** @var \Magento\Sales\Model\Order\Item $item */
            foreach ($order->getAllVisibleItems() as $item) {
                $product_type = $item->getProductType();
                $productOriginalPrice = number_format($item->getBaseOriginalPrice(), 2, '.', '');
                $taxPercent = $item->getTaxPercent();
				$taxRate = (1 + $taxPercent/100);
                $priceIncTax = false;
                $quantity = $item->getQtyOrdered();
                $appliedRule = $item->getAppliedRuleIds();

                if(!empty($appliedRule)){
                    $appliedRuleArr = explode(",",$appliedRule);
                    $discountPercentage = array();
                    foreach($appliedRuleArr as $ruleId){
                        $couponCodeData = $this->rule->create()->load($ruleId); 
                        $simpleAction  = $couponCodeData->getData('simple_action');
                        $discountAmount  = $couponCodeData->getData('discount_amount');
                        $applyToShipping  = $couponCodeData->getData('apply_to_shipping');
                        if($applyToShipping){
                            if(!in_array($ruleId,$shippingDiscounts)){
                                $shippingDiscounts[] = $ruleId;
                            }
                        }
                        if($simpleAction == 'by_percent'){
                            $discountPercentage[] = ($discountAmount/100);
                        }
                    }

                    if(count($discountPercentage) == 1){
                        $itemDiscount = array_shift($discountPercentage);
                        $itemDiscount = $itemDiscount*100;
                    }else if(count($discountPercentage) > 1){
                        $discountSum = array_sum($discountPercentage);
                        $discountProduct = array_product($discountPercentage);
                        $itemDiscount = ($discountSum - $discountProduct)*100;
                    }                    
                }

                if ((int) $this->scopeConfig->getValue('tax/calculation/price_includes_tax', $storeScope) === 1) {
                    $unitPriceWithoutTax = $productOriginalPrice/$taxRate;
                    $unitPrice = number_format($unitPriceWithoutTax, 2, '.', '');
                    $priceIncTax = true;
                }else{
                    $unitPrice = $productOriginalPrice;
                }
                $orderline = new OrderLine(
                    $item->getName(),
                    $item->getSku(),
                    $item->getQtyOrdered(),
                    $unitPrice
                );
                if ($product_type != 'virtual' && $product_type != 'downloadable') {
                    $sendShipment = true;
                }
                $orderline->setGoodsType('item');
                //in case of cart rule discount, send tax after discount
                if ($priceIncTax) {
                    $dataForPrice = $this->returnDataForPriceIncTax($item, $unitPrice, $couponCode, $taxPercent, $quantity, $itemDiscount);
                }else {
                    $dataForPrice = $this->returnDataForPriceExcTax($item, $unitPrice, $couponCode, $taxPercent, $quantity, $itemDiscount, $discountOnAllItems);
                }
                
                $taxAmount = number_format($dataForPrice["rawTaxAmount"], 2, '.', '');

                if($discountOnAllItems){
                    $orderline->discount = 0;
                }else{
                    $orderline->discount = $dataForPrice["discount"];
                }
                $orderline->taxAmount = $taxAmount + $item->getWeeeTaxAppliedRowAmount();
                $orderlines[] = $orderline;
            }
            /* Code for shipment */
            if ($sendShipment) {
                $shippingaddress = $order->getShippingMethod(true);
                $method = isset($shippingaddress['method']) ? $shippingaddress['method'] : '';
                $carrier_code = isset($shippingaddress['carrier_code']) ? $shippingaddress['carrier_code'] : '';

                //add shipping tax amount in separate column of request
                $discountPercentage = array();
                $itemDiscount = 0;

                if(!empty($shippingDiscounts)){
                    foreach($shippingDiscounts as $ruleId){
                        $couponCodeData = $this->rule->create()->load($ruleId); 
                        $simpleAction  = $couponCodeData->getData('simple_action');
                        $discountAmount  = $couponCodeData->getData('discount_amount');
                        if($simpleAction == 'by_percent'){
                            $discountPercentage[] = ($discountAmount/100);
                        }
                    }   
                    if(count($discountPercentage) == 1){
                        $itemDiscount = array_shift($discountPercentage);
                        $itemDiscount = $itemDiscount*100;
                    }else if(count($discountPercentage) > 1){
                        $discountSum = array_sum($discountPercentage);
                        $discountProduct = array_product($discountPercentage);
                        $itemDiscount = ($discountSum - $discountProduct)*100;
                    }
                }

                $compAmountDiscount = 0;
                if($compAmount > 0){
                    /* add discount rate*/
                    $compAmountDiscount = $compAmount + ($compAmount*($itemDiscount/100));
                    /*Add tax percentage in compensation amount*/
                    $compAmountDiscount = $compAmountDiscount + ($compAmountDiscount*($shippingTaxPercent/100));
                    $compAmountDiscount = number_format($compAmountDiscount, 2, '.', '');
                }

              if($discountOnAllItems){
                $totalShipAmount = $shippingAmount + $compAmount;
              }else{
                $totalShipAmount = $shippingAmount + $compAmountDiscount;
              }
              
              $totalShipAmount = number_format($totalShipAmount, 2, '.', '');
                
                //after discount tax case
                if (!empty($shippingaddress)) {
                    $orderline = new OrderLine(
                        $method,
                        $carrier_code,
                        1,
                        $totalShipAmount
                );
                
                if($discountOnAllItems){
                    $orderline->discount = 0;
                    $orderline->taxAmount = $shippingTax;
                }else{
                    $orderline->discount = $itemDiscount;
                    if($shippingTaxPercent > 0){
                        $shippingAmount = $shippingAmount*($shippingTaxPercent/100);
                        $orderline->taxAmount = number_format($shippingAmount, 2, '.', '');
                    }else{
                        $orderline->taxAmount = 0;
                    }
                }

                $orderline->setGoodsType('shipment');
                $orderlines[] = $orderline;

                }
            }

            if ($discountOnAllItems == true && ((abs($couponCodeAmount) > 0) || !(empty($appliedRules)))) {
                if(empty($couponCode)){
                    $couponCode = 'Cart Price Rule';
                }
                // Handling price reductions
                $orderline = new OrderLine(
                    $couponCode,
                    'discount',
                    1,
                    $couponCodeAmount
                );
                $orderline->setGoodsType('handling');
                $orderlines[] = $orderline;
            }
          
            $request->setOrderLines($orderlines);

            try {
                /** @var \Valitor\Response\PaymentRequestResponse $response */
                $response = $request->call();
                $requestParams['result'] = __(ConstantConfig::SUCCESS);
                $requestParams['formurl'] = $response->Url;
                // set before payment status
                $orderStatusBefore = $this->systemConfig->getStatusConfig('before', $storeScope, $storeCode);
                if ($orderStatusBefore) {
                    $this->setCustomOrderStatus($order, Order::STATE_NEW, 'before');
                }
                // set notification
                $order->addStatusHistoryComment(__(ConstantConfig::REDIRECT_TO_VALITOR) . $response->PaymentRequestId);
                $extensionAttribute = $order->getExtensionAttributes();
                if ($extensionAttribute && $extensionAttribute->getValitorPaymentFormUrl()) {
                    $extensionAttribute->setValitorPaymentFormUrl($response->Url);
                }

                $order->setValitorPaymentFormUrl($response->Url);

                $order->getResource()->save($order);

                //set flag if customer redirect to Valitor
                $this->checkoutSession->setValitorCustomerRedirect(true);

                return $requestParams;
            } catch (ClientException $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getResponse()->getBody();
            } catch (ResponseHeaderException $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getHeader()->ErrorMessage;
            } catch (ResponseMessageException $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getMessage();
            } catch (\Exception $e) {
                $requestParams['result'] = __(ConstantConfig::ERROR);
                $requestParams['message'] = $e->getMessage();
            }

            $this->restoreOrderFromOrderId($order->getIncrementId());
            return $requestParams;
        }

        $this->restoreOrderFromOrderId($order->getIncrementId());
        $requestParams['result']  = __(ConstantConfig::ERROR);
        $requestParams['message'] = __(ConstantConfig::ERROR_MESSAGE);
        return $requestParams;
    }
	/**
	 * @returns returnDataForPriceIncTax[]
	 */
     private function returnDataForPriceIncTax($item, $unitPrice, $couponCode, $taxPercent, $quantity, $itemDiscount)
     {
        $data["discount"] =	0;
        $data["rawTaxAmount"] = 0;
        $priceAfterDiscount = 0;
        $productID = $item->getProductId();
        $product_type = $item->getProductType();
        $_product = $this->productRepository->getById($productID);
        //If product type is configurable get price after discount
        if($product_type == "configurable") {
            $priceAfterDiscount = $item->getRowTotal()/$quantity;
        }else {
            $priceAfterDiscount = $_product->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount();
        }    
        $priceAfterDiscount = number_format($priceAfterDiscount, 2, '.', '');
        $data["rawTaxAmount"] = (($taxPercent/100) * $unitPrice) *  $quantity;
        if ($priceAfterDiscount != null && $unitPrice > $priceAfterDiscount && empty($couponCode)) {
            $discountAmount = (($unitPrice-$priceAfterDiscount)/$unitPrice)*100;
            $data["discount"] = number_format($discountAmount, 2, '.', '');
            $taxBeforeDiscount = ($unitPrice * $taxPercent)/100;
            //In case of catalog rule discount, send tax before discount
            $data["rawTaxAmount"] = $taxBeforeDiscount * $quantity;
        }else{
            $data["discount"] = $itemDiscount;
        }
        return $data;
    }
    /**
     * @returns returnDataForPriceExcTax[]
     */
	private function returnDataForPriceExcTax($item, $unitPrice, $couponCode, $taxPercent, $quantity, $itemDiscount, $discountOnAllItems)
	{
        $data["discount"] =	0;

        if ($discountOnAllItems) {
			$data["rawTaxAmount"] = $item->getTaxAmount();
		} else {
			$data["rawTaxAmount"] = ($unitPrice * ($taxPercent / 100)) * $quantity;
		}
		$productSpecialPrice = number_format($item->getPrice(), 2, '.', '');
			if($productSpecialPrice != null && $unitPrice > $productSpecialPrice && empty($couponCode)){
				$discount = (($unitPrice-$productSpecialPrice)/$unitPrice)*100;
				//In case of catalog rule discount, send tax before discount
				$taxBeforeDiscount = ($unitPrice * $item->getTaxPercent())/100;
				$data["discount"] = $discount;
				$data["rawTaxAmount"] = $taxBeforeDiscount * $quantity;
        }else{
            $data["discount"] = $itemDiscount;
        }
        return $data;
    }
    /**
      * @param $orderId
      * @throws \Exception
      * @throws \Magento\Framework\Exception\AlreadyExistsException
      */
    public function restoreOrderFromOrderId($orderId)
    {
        $order = $this->orderLoader->getOrderByOrderIncrementId($orderId);
        if ($order->getId()) {
            $quote = $this->quote->loadByIdWithoutStore($order->getQuoteId());
            $quote
                ->setIsActive(1)
                ->setReservedOrderId(null);
            $quote->getResource()->save($quote);
            $this->checkoutSession->replaceQuote($quote);
        }
    }

    /**
      * @param Order $order
      * @return Customer
      */
    private function setCustomer(Order $order)
    {
        $billingAddress = new Address();
        if ($order->getBillingAddress()) {
            $address = $order->getBillingAddress()->convertToArray();
            $billingAddress->Email = $order->getBillingAddress()->getEmail();
            $billingAddress->Firstname = $address['firstname'];
            $billingAddress->Lastname = $address['lastname'];
            $billingAddress->Address = $address['street'];
            $billingAddress->City = $address['city'];
            $billingAddress->PostalCode = $address['postcode'];
            $billingAddress->Region = $address['region'] ?: '0';
            $billingAddress->Country = $address['country_id'];
        }
        $customer = new Customer($billingAddress);

        if ($order->getShippingAddress()) {
            $address = $order->getShippingAddress()->convertToArray();
            $shippingAddress = new Address();
            $shippingAddress->Email = $order->getShippingAddress()->getEmail();
            $shippingAddress->Firstname = $address['firstname'];
            $shippingAddress->Lastname = $address['lastname'];
            $shippingAddress->Address = $address['street'];
            $shippingAddress->City = $address['city'];
            $shippingAddress->PostalCode = $address['postcode'];
            $shippingAddress->Region = $address['region'] ?: '0';
            $shippingAddress->Country = $address['country_id'];
            $customer->setShipping($shippingAddress);
        } else {
            $customer->setShipping($billingAddress);
        }

        if ($order->getBillingAddress()) {
            $customer->setEmail($order->getBillingAddress()->getEmail());
            $customer->setPhone($order->getBillingAddress()->getTelephone());
        } elseif ($order->getShippingAddress()) {
            $customer->setEmail($order->getShippingAddress()->getEmail());
            $customer->setPhone($order->getShippingAddress()->getTelephone());
        } else {
            $customer->setEmail($order->getBillingAddress()->getEmail());
            $customer->setPhone($order->getBillingAddress()->getTelephone());
        }
        return $customer;
    }

    /**
      * @return Config
      */
    private function setConfig()
    {
        $config = new Config();
        $config->setCallbackOk($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_OK));
        $config->setCallbackFail($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_FAIL));
        $config->setCallbackRedirect($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_REDIRECT));
        $config->setCallbackOpen($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_OPEN));
        $config->setCallbackNotification($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_NOTIFICATION));
        //$config->setCallbackVerifyOrder($this->urlInterface->getDirectUrl(ConstantConfig::VERIFY_ORDER));
        $config->setCallbackForm($this->urlInterface->getDirectUrl(ConstantConfig::VALITOR_CALLBACK));
        return $config;
    }

    /**
     * @param Order $order
     * @param $state
     * @param $statusKey
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function setCustomOrderStatus(Order $order, $state, $statusKey)
    {
        $order->setState($state);
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $storeCode = $order->getStore()->getCode();
        if ($status = $this->systemConfig->getStatusConfig($statusKey, $storeScope, $storeCode)) {
            $order->setStatus($status);
        }
        $order->getResource()->save($order);
    }
    /**
     * @param $orderItems
     */
    private function allItemsHaveDiscount($orderItems)
    {
        $discountOnAllItems = true;
        foreach ($orderItems as $item) {
            $appliedRule = $item->getAppliedRuleIds();
            $product_type = $item->getProductType();
            if(!empty($appliedRule)){
                $appliedRuleArr = explode(",",$appliedRule);
                foreach($appliedRuleArr as $ruleId){
                    $couponCodeData = $this->rule->create()->load($ruleId); 
                    $applyToShipping  = $couponCodeData->getData('apply_to_shipping');
                    if(!$applyToShipping && $product_type != 'virtual' && $product_type != 'downloadable'){
                        $discountOnAllItems = false;
                    }
                }
            }else{
                $discountOnAllItems = false;
            }
        }
        return $discountOnAllItems;
    }
    
    /**
     * @param $orderID
     */
    private function getOrderShippingTax($orderID)
    {
        $shippingTaxPercent = 0;
        $tax_items = $this->taxItem->getTaxItemsByOrderId($orderID); 
        if(!empty($tax_items) && is_array($tax_items)){
            foreach($tax_items as $item){
                if ($item['taxable_item_type'] === 'shipping') {
                    $shippingTaxPercent = $item['tax_percent'];
                }
            }
        }
        return $shippingTaxPercent;
    }
}
