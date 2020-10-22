<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Test\Unit\Block\Callback;

use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\App\Request\Http;
use SDM\Altapay\Block\Callback\Ordersummary;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use SDM\Altapay\Test\Unit\MainTestCase;
use SDM\Altapay\Test\Unit\ConstantTestConfig;

class OrdersummaryTest extends MainTestCase
{
    /**
     * @var Ordersummary
     */
    private $Ordersummary;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var OrderCore|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCore;

    protected function setUp()
    {
        $this->objectManager = $this->getObjectManager();

        $this->orderCore = $this->getMockBuilder(OrderCore::class)->disableOriginalConstructor()->getMock();
        $this->orderCore->method('loadByIncrementId')->willReturn($this->orderCore);
        $orderFactory = $this->getMockBuilder(OrderFactory::class)
                             ->disableOriginalConstructor()
                             ->setMethods(['create'])
                             ->getMock();
        $orderFactory->method('create')->willReturn($this->orderCore);

        $this->order = $this->objectManager->getObject(Ordersummary::class, [
            'orderFactory' => $orderFactory
        ]);
    }

    public function testGetOrderId()
    {
        $this->orderCore->method('getId')->willReturn('1');
        $result = $this->order->getOrderId(ConstantTestConfig::ORDER_ID);
        $this->assertInstanceOf(OrderCore::class, $result);
    }

    public function testGetOrderIdNull()
    {
        $this->orderCore->method('getId')->willReturn(false);
        $result = $this->order->getOrderId(ConstantTestConfig::ORDER_ID);
        $this->assertNull($result);
    }

    public function testGetPaymentMethodtitle()
    {
        $result = $this->order->getPaymentMethodtitle();
    }

    public function testGetPaymentMethodtitleFalse()
    {
        $result = $this->order->getPaymentMethodtitle();
        $this->assertFalse($result);
    }

    public function testGetFormatedShippingAddress()
    {
        $address = '';
        $result  = $this->order->FormatedShippingAddress();
        $this->assertEquals($address, $result);
    }

    public function testGetProductById()
    {
        $result = $this->order->getProductById();
        $this->assertFalse($result);
    }

    public function testGetProductByIdFalse()
    {
        $result = $this->order->getProductById();
        $this->assertFalse($result);
    }
}
