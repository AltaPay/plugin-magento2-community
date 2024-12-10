<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Test\Unit\Model;

use Magento\Sales\Model\Order;
use SDM\Altapay\Model\Generator as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Altapay\Test\Unit\MainTestCase;
use Magento\Store\Model\Store;
use SDM\Altapay\Model\SystemConfig;
use Altapay\Api\Test\TestAuthentication;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Request\Address;
use Altapay\Request\Customer;
use Altapay\Request\OrderLine;
use SDM\Altapay\Test\Unit\ConstantTestConfig;
use Altapay\Api\Ecommerce\Callback;
use Altapay\Response\CallbackResponse;

/**
 * Class GeneratorTest
 * Handle the create payment related functionality.
 */
class GeneratorTest extends MainTestCase
{
    /**
     * @var order
     */
    private $order;
    /**
     * @var ClassToTest
     */
    private $classToTest;

    /**
     * @var SystemConfig
     */
    private $systemConfig;

    /**
     * Initial setup for Generator test class with dependency systemConfig
     */
    protected function setUp()
    {
        $objectManager = $this->getObjectManager();

        $this->systemConfig = $this->getMockBuilder(SystemConfig::class)->disableOriginalConstructor()->getMock();

        $this->classToTest = $objectManager->getObject(ClassToTest::class, [
            'systemConfig' => $this->systemConfig
        ]);
        $this->order       = [
            'shop_orderid'       => '000000289',
            'currency'           => '208',
            'transaction_info'   => [
                'ecomPlatform'      => 'Magento',
                'ecomVersion'       => '2.3.2',
                'ecomPluginName'    => 'SDM_Altapay',
                'ecomPluginVersion' => '1.3.4',
                'otherInfo'         => 'websiteName - Main Website, storeName - Danish Store View',
            ],
            'type'               => 'payment',
            'embedded_window'    => '0',
            'amount'             => '23.18',
            'transaction_id'     => '33329721',
            'payment_id'         => 'fe68da2c-a7ca-493a-b01c-ebafd80cd93b',
            'nature'             => 'CreditCard',
            'require_capture'    => 'true',
            'payment_status'     => 'preauth',
            'masked_credit_card' => '411111******1111',
            'blacklist_token'    => '57c4f859a6b4e005bb1e7a42bd4cc7e8e4b58f1b',
            'credit_card_token'  => 'AQ+DuN1BmWe8g9PnsfU2jNC4ZsG7r5TuSAxFvt3uO/A5UPuGq1FarLiphj2WJA+AYBu/fbP6zdPezEJA3Q49ZA==+1',
            'status'             => 'succeeded',
            'avs_code'           => 'S',
            'avs_text'           => 'AVS not supported',
            'xml'                => <<<XML
<?xml version="1.0"?>
<APIResponse version="20170228">
    <Header>
        <Date>2019-08-15T14:05:39+02:00</Date>
        <Path>API/reservationOfFixedAmount</Path>
        <ErrorCode>0</ErrorCode>
        <ErrorMessage />
    </Header>
    <Body>
        <Result>Success</Result>
        <Transactions>
            <Transaction>
                <TransactionId>33329721</TransactionId>
                <PaymentId>fe68da2c-a7ca-493a-b01c-ebafd80cd93b</PaymentId>
                <AuthType>payment</AuthType>
                <CardStatus>Valid</CardStatus>
                <CreditCardExpiry>
                    <Year>2022</Year>
                    <Month>07</Month>
                </CreditCardExpiry>
                <CreditCardToken>AQ+DuN1BmWe8g9PnsfU2jNC4ZsG7r5TuSAxFvt3uO/A5UPuGq1FarLiphj2WJA+AYBu/fbP6zdPezEJA3Q49ZA==+1</CreditCardToken>
                <CreditCardMaskedPan>411111******1111</CreditCardMaskedPan>
                <IsTokenized>false</IsTokenized>
                <ThreeDSecureResult>Not_Attempted</ThreeDSecureResult>
                <LiableForChargeback>Merchant</LiableForChargeback>
                <CVVCheckResult>Not_Attempted</CVVCheckResult>
                <BlacklistToken>57c4f859a6b4e005bb1e7a42bd4cc7e8e4b58f1b</BlacklistToken>
                <ShopOrderId>000000329</ShopOrderId>
                <Shop>EmbraceIT</Shop>
                <Terminal>EmbraceIT Test Terminal</Terminal>
                <TransactionStatus>preauth</TransactionStatus>
                <ReasonCode>NONE</ReasonCode>
                <MerchantCurrency>208</MerchantCurrency>
                <MerchantCurrencyAlpha>DKK</MerchantCurrencyAlpha>
                <CardHolderCurrency>208</CardHolderCurrency>
                <CardHolderCurrencyAlpha>DKK</CardHolderCurrencyAlpha>
                <ReservedAmount>23.18</ReservedAmount>
                <CapturedAmount>0.00</CapturedAmount>
                <RefundedAmount>0.00</RefundedAmount>
                <CreditedAmount>0.00</CreditedAmount>
                <RecurringDefaultAmount>0.00</RecurringDefaultAmount>
                <SurchargeAmount>0.00</SurchargeAmount>
                <CreatedDate>2019-08-15 14:05:39</CreatedDate>
                <UpdatedDate>2019-08-15 14:05:39</UpdatedDate>
                <PaymentNature>CreditCard</PaymentNature>
                <PaymentSource>eCommerce</PaymentSource>
                <PaymentSchemeName>Visa</PaymentSchemeName>
                <PaymentNatureService name="SoapTestAcquirer">
                    <SupportsRefunds>true</SupportsRefunds>
                    <SupportsRelease>true</SupportsRelease>
                    <SupportsMultipleCaptures>true</SupportsMultipleCaptures>
                    <SupportsMultipleRefunds>true</SupportsMultipleRefunds>
                </PaymentNatureService>
                <AddressVerification>S</AddressVerification>
                <AddressVerificationDescription>AVS not supported</AddressVerificationDescription>
                <ChargebackEvents />
                <PaymentInfos>
                    <PaymentInfo name="ecomPlatform"><![CDATA[Magento]]></PaymentInfo>
                    <PaymentInfo name="ecomVersion"><![CDATA[2.3.2]]></PaymentInfo>
                    <PaymentInfo name="ecomPluginName"><![CDATA[SDM_Altapay]]></PaymentInfo>
                    <PaymentInfo name="ecomPluginVersion"><![CDATA[1.3.5]]></PaymentInfo>
                    <PaymentInfo name="otherInfo"><![CDATA[websiteName - Main Website, storeName - Danish Store View]]></PaymentInfo>
                </PaymentInfos>
                <CustomerInfo>
                    <UserAgent>Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36</UserAgent>
                    <IpAddress>182.191.81.110</IpAddress>
                    <Email><![CDATA[Embrace@admin.com]]></Email>
                    <Username />
                    <CustomerPhone>20123456</CustomerPhone>
                    <OrganisationNumber />
                    <CountryOfOrigin>
                        <Country>US</Country>
                        <Source>CardNumber</Source>
                    </CountryOfOrigin>
                    <BillingAddress>
                        <Firstname><![CDATA[Testperson-dk Approved]]></Firstname>
                        <Lastname><![CDATA[Approved]]></Lastname>
                        <Address><![CDATA[Sæffleberggate 56,1 mf]]></Address>
                        <City><![CDATA[Varde]]></City>
                        <Region><![CDATA[0]]></Region>
                        <Country><![CDATA[DK]]></Country>
                        <PostalCode><![CDATA[6800]]></PostalCode>
                    </BillingAddress>
                    <ShippingAddress>
                        <Firstname><![CDATA[Testperson-dk Approved]]></Firstname>
                        <Lastname><![CDATA[Approved]]></Lastname>
                        <Address><![CDATA[Sæffleberggate 56,1 mf]]></Address>
                        <City><![CDATA[Varde]]></City>
                        <Region><![CDATA[0]]></Region>
                        <Country><![CDATA[DK]]></Country>
                        <PostalCode><![CDATA[6800]]></PostalCode>
                    </ShippingAddress>
                </CustomerInfo>
                <ReconciliationIdentifiers />
            </Transaction>
        </Transactions>
    </Body>
</APIResponse>
XML
        ];
    }

    /**
     * testCreateRequest in order to generate for url Compare result dummy data vs actual data
     */
    public function testCreateRequest()
    {
        $terminalId    = ConstantTestConfig::TERMINAL_ID;
        $orderId       = ConstantTestConfig::ORDER_ID;
        $requestParams = [];
        $result        = $this->classToTest->createRequest($terminalId, $orderId);
        $storeCode     = ConstantTestConfig::STORE_CODE;
        $auth          = $this->systemConfig->getAuth($storeCode);
        $api           = new TestAuthentication($auth);
        $response      = $api->call();
        $terminalName  = $this->systemConfig->getTerminalConfig(
            $terminalId,
            'terminalname',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeCode
        );
        if (!$response) {
            $this->assertArrayHasKey('status', $response);
        }
        $request = new PaymentRequest($auth);
        $request
            ->setTerminal($terminalName)
            ->setShopOrderId(1)
            ->setAmount((float)ConstantTestConfig::ORDER_TOTAL)
            ->setCurrency(ConstantTestConfig::USD)
            ->setCustomerInfo($this->setCustomerTest())
            ->setConfig(ConstantTestConfig::CALL_BACK);
        $orderlines = [];
        /** @var \Magento\Sales\Model\Order\Item $item */
        $taxAmount  = ConstantTestConfig::TAX;
        $taxPercent = ConstantTestConfig::TAX_PERCENT;
        $productUrl = ConstantTestConfig::PRODUCT_URL;
        $imageUrl   = ConstantTestConfig::IMAGE_URL;
        $unitCode   = ConstantTestConfig::UNIT_CODE;
        $orderline  = new OrderLine(
            ConstantTestConfig::ITEM_NAME,
            ConstantTestConfig::ITEM_SKU,
            ConstantTestConfig::ITEM_QTY,
            ConstantTestConfig::ITEM_PRICE
        );
        $orderline->setGoodsType('item');
        $orderline->taxAmount  = $taxAmount;
        $orderline->productUrl = $productUrl;
        $orderline->imageUrl   = $imageUrl;
        $orderline->unitCode   = $unitCode;
        $orderlines[]          = $orderline;
        // Handling orderline
        $orderlines[] = (new OrderLine(
            'free_shipping',
            'free_free',
            1,
            5
        ))->setGoodsType('shipment');
        $request->setOrderLines($orderlines);
        $response                 = $request->call();
        $requestParams['result']  = 'success';
        $requestParams['formurl'] = $response->Url;

        $this->assertEquals($requestParams, $result);
    }

    public function testRequestCallback()
    {
        $call     = new Callback($this->order);
        $response = $call->call();
        $this->assertInstanceOf(CallbackResponse::class, $response);
        $this->assertEquals('fe68da2c-a7ca-493a-b01c-ebafd80cd93b', $response->paymentId);
        $this->assertEquals('000000289', $response->shopOrderId);
        $this->assertEquals('succeeded', $response->status);
        $this->assertCount(1, $response->Transactions);
        $this->assertEquals('33329721', $response->Transactions[0]->TransactionId);
        $this->assertEquals('Success', $response->Result);
    }

    public function testBillingDetails()
    {
        $billingInfo                = [
            'email'      => 'johnandleson@gmail.com',
            'firstname'  => 'John',
            'lastname'   => 'Andleson',
            'city'       => 'Varde',
            'postalcode' => '6800',
            'region'     => 'Esbjerg',
            'country'    => 'AUS',
        ];
        $expectedResult             = [
            'Firstname'  => 'John',
            'Lastname'   => 'Andleson',
            'Address'    => null,
            'City'       => 'Varde',
            'PostalCode' => '6800',
            'Region'     => 'Esbjerg',
            'Country'    => 'AUS',
            'Header'     => null,
            'Email'      => 'johnandleson@gmail.com',
        ];
        $billingAddress             = new Address();
        $billingAddress->Email      = $billingInfo['email'];
        $billingAddress->Firstname  = $billingInfo['firstname'];
        $billingAddress->Lastname   = $billingInfo['lastname'];
        $billingAddress->City       = $billingInfo['city'];
        $billingAddress->PostalCode = $billingInfo['postalcode'];
        $billingAddress->Region     = $billingInfo['region'] ?: '0';
        $billingAddress->Country    = $billingInfo['country'];
        $customer                   = new Customer($billingAddress);
        $customer->setShipping($billingAddress);
        $reflectionProperty = new \ReflectionProperty(Customer::class, 'billing');
        $reflectionProperty->setAccessible(true);
        $setbillinginfo = (array)$reflectionProperty->getValue($customer);
        $keys           = array_keys($setbillinginfo);
        end($keys);
        $removechildarray = prev($keys);
        unset($setbillinginfo[$removechildarray]);
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals($expectedResult, $setbillinginfo);
    }

    public function testShippingDetails()
    {
        $shippingInfo                = [
            'email'      => 'johnandleson@gmail.com',
            'firstname'  => 'John',
            'lastname'   => 'Andleson',
            'city'       => 'Varde',
            'postalcode' => '6800',
            'region'     => 'Esbjerg',
            'country'    => 'AUS',
        ];
        $expectedResult              = [
            'Firstname'  => 'John',
            'Lastname'   => 'Andleson',
            'Address'    => null,
            'City'       => 'Varde',
            'PostalCode' => '6800',
            'Region'     => 'Esbjerg',
            'Country'    => 'AUS',
            'Header'     => null,
            'Email'      => 'johnandleson@gmail.com',
        ];
        $shippingAddress             = new Address();
        $shippingAddress->Email      = $shippingInfo['email'];
        $shippingAddress->Firstname  = $shippingInfo['firstname'];
        $shippingAddress->Lastname   = $shippingInfo['lastname'];
        $shippingAddress->City       = $shippingInfo['city'];
        $shippingAddress->PostalCode = $shippingInfo['postalcode'];
        $shippingAddress->Region     = $shippingInfo['region'] ?: '0';
        $shippingAddress->Country    = $shippingInfo['country'];
        $customer                    = new Customer($shippingAddress);
        $customer->setShipping($shippingAddress);
        $reflectionProperty = new \ReflectionProperty(Customer::class, 'shipping');
        $reflectionProperty->setAccessible(true);
        $setshippinginfo = (array)$reflectionProperty->getValue($customer);
        $keys            = array_keys($setshippinginfo);
        end($keys);
        $removechildarray = prev($keys);
        unset($setshippinginfo[$removechildarray]);
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals($expectedResult, $setshippinginfo);
    }

    public function testNoShipping()
    {
        $shippingInfo               = [];
        $billingInfo                = [
            'email'      => 'johnandleson@gmail.com',
            'firstname'  => 'John',
            'lastname'   => 'Andleson',
            'city'       => 'Varde',
            'postalcode' => '6800',
            'region'     => 'Esbjerg',
            'country'    => 'AUS',
        ];
        $expectedResult             = [
            'Firstname'  => 'John',
            'Lastname'   => 'Andleson',
            'Address'    => null,
            'City'       => 'Varde',
            'PostalCode' => '6800',
            'Region'     => 'Esbjerg',
            'Country'    => 'AUS',
            'Header'     => null,
            'Email'      => 'johnandleson@gmail.com',
        ];
        $billingAddress             = new Address();
        $billingAddress->Email      = $billingInfo['email'];
        $billingAddress->Firstname  = $billingInfo['firstname'];
        $billingAddress->Lastname   = $billingInfo['lastname'];
        $billingAddress->City       = $billingInfo['city'];
        $billingAddress->PostalCode = $billingInfo['postalcode'];
        $billingAddress->Region     = $billingInfo['region'] ?: '0';
        $billingAddress->Country    = $billingInfo['country'];
        if (empty($shippingInfo)) {
            $customer = new Customer($billingAddress);
            $customer->setShipping($billingAddress);
            $reflectionProperty = new \ReflectionProperty(Customer::class, 'billing');
            $reflectionProperty->setAccessible(true);
            $setBillingAsShippingInfo = (array)$reflectionProperty->getValue($customer);
            $keys                     = array_keys($setBillingAsShippingInfo);
            end($keys);
            $removeChildArray = prev($keys);
            unset($setBillingAsShippingInfo[$removeChildArray]);
            $this->assertInstanceOf(Customer::class, $customer);
            $this->assertEquals($expectedResult, $setBillingAsShippingInfo);
        }
    }

    public function testForPriceInclTax()
    {
        $orderline = [
            "productOriginalPrice" => 220,
            "unitPrice"            => 200,
            "couponCode"           => "abcdef",
            "taxPercent"           => 10,
            "quantity"             => 1,
        ];
        if (empty($orderline['couponCode'])) {
            $taxAmount = (($orderline['taxPercent'] / 100) * $orderline['unitPrice']) * $orderline['quantity'];
            $this->assertEquals(20, $taxAmount);
        } else {
            $taxAmount = ($orderline['productOriginalPrice'] - $orderline['unitPrice']) * $orderline['quantity'];
            $this->assertEquals(20, $taxAmount);
        }
    }

    public function testgetItemDiscountByPercentage()
    {
        $itemDiscount       = 0;
        $discountPercentage = [0.1, 0.2];
        if (count($discountPercentage) == 1) {
            $itemDiscount = array_shift($discountPercentage);
            $itemDiscount = $itemDiscount * 100;
            $this->assertEquals(10, $itemDiscount);
        } else {
            if (count($discountPercentage) > 1) {
                $discountSum     = array_sum($discountPercentage);
                $discountProduct = array_product($discountPercentage);
                $itemDiscount    = ($discountSum - $discountProduct) * 100;
                $this->assertEquals(28, $itemDiscount);
            }
        }
    }
}
