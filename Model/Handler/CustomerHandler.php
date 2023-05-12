<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Handler;

use Altapay\Request\Address;
use Altapay\Request\Customer;
use Magento\Sales\Model\Order;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class CustomerHandler
 * To handle the customer information for
 * create payment request at altapay.
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
     * @var Http
     */
    private $request;

    /**
     * @var SessionManagerInterface
     */
    protected $session;

    /**
     * Gateway constructor.
     *
     * @param Order                       $order
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param Http                        $request
     * @param SessionManagerInterface     $session
     */
    public function __construct(
        Order $order,
        CustomerRepositoryInterface $customerRepositoryInterface,
        Http $request,
        SessionManagerInterface $session
    ) {
        $this->order                       = $order;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->request                     = $request;
        $this->session                     = $session;
    }
    
    /**
     * @param Order $order
     * @param Bool  $isReservation
     *
     * @return Customer
     * @throws \Exception
     */
    public function setCustomer(Order $order, $isReservation = false)
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
        if(!$isReservation) {
            $customer->setClientIP($this->request->getServer('REMOTE_ADDR'));
            $customer->setClientAcceptLanguage(substr($this->request->getServer('HTTP_ACCEPT_LANGUAGE'), 0, 2));
            $customer->setHttpUserAgent($this->request->getServer('HTTP_USER_AGENT'));
            $customer->setClientSessionID(crypt($this->session->getSessionId(),'$5$rounds=5000$customersessionid$'));
        }
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
        $addObject->setEmail($address['email']);
        $addObject->setFirstname($address['firstname']);
        $addObject->setLastname($address['lastname']);
        $addObject->setAddress($address['street']);
        $addObject->setCity($address['city']);
        $addObject->setPostalCode($address['postcode']);
        $addObject->setRegion($address['region'] ?: '0');
        $addObject->setCountry($address['country_id']);
    
        return $addObject;
    }
}
