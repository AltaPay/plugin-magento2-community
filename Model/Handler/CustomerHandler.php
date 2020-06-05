<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * Copyright © 2018 Valitor. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Valitor\Model\Handler;

use SDM\Valitor\Request\Address;
use SDM\Valitor\Request\Customer;
use Magento\Sales\Model\Order;
use Magento\Customer\Api\CustomerRepositoryInterface;

/**
 * Class CustomerHandler
 * To handle the customer information for
 * create payment request at valitor.
 */
class CustomerHandler
{
    /**
     * @var Order
     */
    private $order;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepositoryInterface;

    /**
     * Gateway constructor.
     *
     * @param Order                       $order
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        Order $order,
        CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        $this->order                       = $order;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * @param Order $order
     *
     * @return Customer
     */
    public function setCustomer(Order $order)
    {
        $address       = new Address();
        $customerEmail = '';
        $customerPhone = '';

        if ($order->getBillingAddress()) {
            $billingAddress = $order->getBillingAddress()->convertToArray();
            $address        = $this->createAddressObject($billingAddress, $address);
            $customerEmail  = $order->getBillingAddress()->getEmail();
            $customerPhone  = $order->getBillingAddress()->getTelephone();
        }
        $customer = new Customer($address);

        if ($order->getShippingAddress()) {
            $shippingAddress = $order->getShippingAddress()->convertToArray();
            $shippingAddress = $this->createAddressObject($shippingAddress, $address);
            $customer->setShipping($shippingAddress);
        } else {
            $customer->setShipping($address);
        }

        if (!$order->getBillingAddress() && $order->getShippingAddress()) {
            $customerEmail = $order->getShippingAddress()->getEmail();
            $customerPhone = $order->getShippingAddress()->getTelephone();
        }

        $customer->setEmail($customerEmail);
        $customer->setPhone(str_replace(' ', '', $customerPhone));

        if (!$order->getCustomerIsGuest()) {
            $customer->setUsername($order->getCustomerId());
            $cst       = $this->customerRepositoryInterface->getById($order->getCustomerId());
            $createdAt = $cst->getCreatedAt();
            $customer->setCreatedDate(new \DateTime($createdAt));
        }

        return $customer;
    }

    /**
     * @param $addObject
     * @param $address
     *
     * @return mixed
     */
    private function createAddressObject($address, $addObject)
    {
        $addObject->Email      = $address['email'];
        $addObject->Firstname  = $address['firstname'];
        $addObject->Lastname   = $address['lastname'];
        $addObject->Address    = $address['street'];
        $addObject->City       = $address['city'];
        $addObject->PostalCode = $address['postcode'];
        $addObject->Region     = $address['region'] ?: '0';
        $addObject->Country    = $address['country_id'];

        return $addObject;
    }
}
