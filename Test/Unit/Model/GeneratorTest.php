<?php
/**
 * Altapay Module version 3.0.1 for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */

namespace SDM\Altapay\Test\Unit\Model;

use Magento\Sales\Model\Order;
use SDM\Altapay\Model\Generator as ClassToTest;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PSDM\Altapay\Test\Unit\MainTestCase;
use Magento\Store\Model\Store;
use SDM\Altapay\Model\SystemConfig;
use Altapay\Api\Test\TestAuthentication;
use Altapay\Api\Ecommerce\PaymentRequest;
use Altapay\Request\Address;
use Altapay\Request\Customer;
use Altapay\Request\OrderLine;
use SDM\Altapay\Test\Unit\ConstantTestConfig;

class GeneratorTest extends MainTestCase
{
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
    }

    /**
     * testCreateRequest in order to generate for url Compare result dummy data vs actual data
     */
    public function testCreateRequest()
    {
        $terminalId = ConstantTestConfig::TERMINAL_ID;
        $orderId = ConstantTestConfig::ORDER_ID;
        $requestParams = [];
        
        $result = $this->classToTest->createRequest($terminalId, $orderId);

        $storeCode = ConstantTestConfig::STORE_CODE;
        $auth = $this->systemConfig->getAuth($storeCode);
        $api = new TestAuthentication($auth);
        $response = $api->call();

        $terminalName = $this->systemConfig->getTerminalConfig($terminalId, 'terminalname', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeCode);
        if (! $response) {
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
            $taxAmount = ConstantTestConfig::TAX;
            $orderline = new OrderLine(
                ConstantTestConfig::ITEM_NAME,
                ConstantTestConfig::ITEM_SKU,
                ConstantTestConfig::ITEM_QTY,
                ConstantTestConfig::ITEM_PRICE
            );
            $orderline->setGoodsType('item');
            $orderline->taxAmount = $taxAmount;
            //$orderline->taxPercent = $item->getTaxPercent();
            $orderlines[] = $orderline;
        

            // Handling orderline
        $orderlines[] = (new OrderLine(
            'free_shipping',
            'free_free',
            1,
            5
        ))->setGoodsType('shipment');
        $request->setOrderLines($orderlines);
        $response = $request->call();
        $requestParams['result'] = __('success');
        $requestParams['formurl'] = $response->Url;

        $this->assertEquals($requestParams, $result);
    }

    /**
     * set dummy data and pass to create request
     */
    private function setCustomerTest()
    {
        $billingAddress = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $billingAddress->method('getEmail')->willReturn(ConstantTestConfig::EMAIL);
        $billingAddress->method('getCountryId')->willReturn(ConstantTestConfig::COUNTRY);
        $billingAddress->method('getFirstname')->willReturn(ConstantTestConfig::FIRST_NAME);
        $billingAddress->method('getLastname')->willReturn(ConstantTestConfig::LAST_NAME);
        $billingAddress->method('getCompany')->willReturn(ConstantTestConfig::COMPANY);
        $billingAddress->method('getStreet')->willReturn([ConstantTestConfig::STREET]);
        $billingAddress->method('getPostcode')->willReturn(ConstantTestConfig::ZIP);
        $billingAddress->method('getCity')->willReturn(ConstantTestConfig::CITY);
        $billingAddress->method('getRegionCode')->willReturn(ConstantTestConfig::REGION_ID);
        $customer = new Customer($billingAddress);

        $shippingAddress = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        $shippingAddress->method('getEmail')->willReturn(ConstantTestConfig::EMAIL);
        $shippingAddress->method('getCountryId')->willReturn(ConstantTestConfig::COUNTRY);
        $shippingAddress->method('getFirstname')->willReturn(ConstantTestConfig::FIRST_NAME);
        $shippingAddress->method('getLastname')->willReturn(ConstantTestConfig::LAST_NAME);
        $shippingAddress->method('getCompany')->willReturn(ConstantTestConfig::COMPANY);
        $shippingAddress->method('getStreet')->willReturn([ConstantTestConfig::STREET]);
        $shippingAddress->method('getPostcode')->willReturn(ConstantTestConfig::ZIP);
        $shippingAddress->method('getCity')->willReturn(ConstantTestConfig::CITY);
        $shippingAddress->method('getRegionCode')->willReturn(ConstantTestConfig::REGION_ID);
        $customer = new Customer($shippingAddress);

        return $customer;
    }
}
