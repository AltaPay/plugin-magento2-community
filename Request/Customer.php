<?php
/**
 * Copyright (c) 2016 Martin Aarhof
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SDM\Valitor\Request;

use SDM\Valitor\Exceptions\Exception;

class Customer extends AbstractSerializer
{
    const FEMALE = 'F';
    const MALE = 'M';

    /**
     * The customer's email address.
     *
     * @var string
     */
    private $email;

    /**
     * The customer's e-shop username/user id.
     *
     * @var string
     */
    private $username;

    /**
     * Indicator of whether the customer is an individual or a business.
     *
     * @var string
     */
    private $type;

    /**
     * Name of the customer,if the customer type is Business.
     *
     * @var string
     */
    private $companyName;

    /**
     * The nature of the company.
     *
     * @var string
     */
    private $companyType;

    /**
     * The company's VAT registration number.
     *
     * @var string
     */
    private $vatId;

    /**
     * The name of the person/role who manages the billing for the company.
     *
     * @var string
     */
    private $billingAtt;

    /**
     * The name of the person receiving the purchase on behalf of the company.
     *
     * @var string
     */
    private $shippingAtt;

    /**
     * The customer's telephone number.
     *
     * @var string
     */
    private $phone;

    /**
     * The name of the bank where the credit card was issued.
     *
     * @var string
     */
    private $bankName;

    /**
     * The phone number of the bank where the credit card was issued.
     *
     * @var string
     */
    private $bankPhone;

    /**
     * The country specific organisation number for the customer, if it is a corporate customer.
     *
     * @var string
     */
    private $organisationNumber;

    /**
     * The country specific personal identity number for the customer,
     * for countries where it is applicable. eg. Norway, Sweden, Finland
     *
     * @var string
     */
    private $personalIdentifyNumber;

    /**
     * Billing address
     *
     * @var Address
     */
    private $billing;

    /**
     * Shipping address
     *
     * @var Address
     */
    private $shipping;

    /**
     * The birth date of the customer
     *
     * @var \DateTime
     */
    private $birthdate;

    /**
     * The creation date of the customer in your system
     *
     * @var \DateTime
     */
    private $createdDate;

    /**
     * Gender
     *
     * @var string
     */
    private $gender;

    /**
     * Text entered by the consumer referencing a procurement order, internal ID or similar..
     *
     * @var string
     */
    private $billingRef;

    /**
     * Text entered by the consumer referencing a procurement order, internal ID or similar..
     *
     * @var string
     */
    private $shippingRef;

    /**
     * Customer constructor.
     *
     * @param Address $billingAddress Billing address
     */
    public function __construct(Address $billingAddress)
    {
        $this->billing = $billingAddress;
    }

    /**
     * @param Address $shipping
     *
     * @return Customer
     */
    public function setShipping(Address $shipping)
    {
        $this->shipping = $shipping;

        return $this;
    }

    /**
     * Set Email
     *
     * @param string $email
     *
     * @return Customer
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Set Username
     *
     * @param string $username
     *
     * @return Customer
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set Customer Type
     *
     * @param string $type
     *
     * @return Customer
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set Company Name
     *
     * @param string $companyName
     *
     * @return Customer
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * Set Company Type
     *
     * @param string $companyType
     *
     * @return Customer
     */
    public function setCompanyType($companyType)
    {
        $this->companyType = $companyType;

        return $this;
    }

    /**
     * Set VAT Id
     *
     * @param string $vatId
     *
     * @return Customer
     */
    public function setVatId($vatId)
    {
        $this->vatId = $vatId;

        return $this;
    }

    /**
     * Set billingAtt
     *
     * @param string $billingAtt
     *
     * @return Customer
     */
    public function setBillingAtt($billingAtt)
    {
        $this->billingAtt = $billingAtt;

        return $this;
    }

    /**
     * Set shippingAtt
     *
     * @param string $shippingAtt
     *
     * @return Customer
     */
    public function setShippingAtt($shippingAtt)
    {
        $this->shippingAtt = $shippingAtt;

        return $this;
    }

    /**
     * Set Phone
     *
     * @param string $phone
     *
     * @return Customer
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Set BankName
     *
     * @param string $bankName
     *
     * @return Customer
     */
    public function setBankName($bankName)
    {
        $this->bankName = $bankName;

        return $this;
    }

    /**
     * Set BankPhone
     *
     * @param string $bankPhone
     *
     * @return Customer
     */
    public function setBankPhone($bankPhone)
    {
        $this->bankPhone = $bankPhone;

        return $this;
    }

    /**
     * Set Birthdate
     *
     * @param \DateTime $birthdate
     *
     * @return Customer
     */
    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    /**
     * Set CreatedDate
     *
     * @param \DateTime $createdDate
     *
     * @return Customer
     */
    public function setCreatedDate(\DateTime $createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param string $organisationNumber
     *
     * @return Customer
     */
    public function setOrganisationNumber($organisationNumber)
    {
        $this->organisationNumber = $organisationNumber;

        return $this;
    }

    /**
     * @param string $personalIdentifyNumber
     *
     * @return Customer
     */
    public function setPersonalIdentifyNumber($personalIdentifyNumber)
    {
        $this->personalIdentifyNumber = $personalIdentifyNumber;

        return $this;
    }

    /**
     * @param string $billingRef
     *
     * @return Customer
     */
    public function setBillingRef($billingRef)
    {
        $this->billingRef = $billingRef;

        return $this;
    }

    /**
     * @param string $shippingRef
     *
     * @return Customer
     */
    public function setShippingRef($shippingRef)
    {
        $this->shippingRef = $shippingRef;

        return $this;
    }

    /**
     * Sets the gender
     *
     * @param string $gender
     *
     * @return Customer
     * @throws Exception
     */
    public function setGender($gender)
    {
        switch (strtolower($gender)) {
            case 'male':
            case 'm':
                $this->gender = self::MALE;

                return $this;
            case 'female':
            case 'f':
                $this->gender = self::FEMALE;

                return $this;
        }

        throw new Exception('setGender() only allows the value (m, male, f or female)');
    }

    /**
     * Serialize a object
     *
     * @return array
     */
    public function serialize()
    {
        $output = [];

        if ($this->birthdate) {
            $output['birthdate'] = $this->birthdate->format('Y-m-d');
        }

        if ($this->email) {
            $output['email'] = $this->email;
        }

        if ($this->username) {
            $output['username'] = $this->username;
        }

        if ($this->type) {
            $output['type'] = $this->type;
        }

        if ($this->companyName) {
            $output['company_name'] = $this->companyName;
        }

        if ($this->companyType) {
            $output['company_type'] = $this->companyType;
        }

        if ($this->vatId) {
            $output['vat_id'] = $this->vatId;
        }

        if ($this->billingAtt) {
            $output['billing_att'] = $this->billingAtt;
        }

        if ($this->shippingAtt) {
            $output['shipping_att'] = $this->shippingAtt;
        }

        if ($this->phone) {
            $output['customer_phone'] = $this->phone;
        }

        if ($this->bankName) {
            $output['bank_name'] = $this->bankName;
        }

        if ($this->bankPhone) {
            $output['bank_phone'] = $this->bankPhone;
        }

        if ($this->organisationNumber) {
            $output['organisationNumber'] = $this->organisationNumber;
        }

        if ($this->personalIdentifyNumber) {
            $output['personalIdentifyNumber'] = $this->personalIdentifyNumber;
        }

        if ($this->gender) {
            $output['gender'] = $this->gender;
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $output['client_ip'] = $_SERVER['REMOTE_ADDR'];
        }

        if (session_id()) {
            $output['client_session_id'] = crypt(session_id(),'$5$rounds=5000$customersessionid$');
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $output['client_accept_language'] = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $output['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $output['client_forwarded_ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ($this->billingRef) {
            $output['billing_ref'] = $this->billingRef;
        }

        if ($this->shippingRef) {
            $output['shipping_ref'] = $this->shippingRef;
        }

        $this->setAddress($output, 'billing_', $this->billing);
        $this->setAddress($output, 'shipping_', $this->shipping);

        return $output;
    }

    /**
     * @param array   $output
     * @param         $key
     * @param Address $object
     */
    private static function setAddress(array &$output, $key, Address $object)
    {
        $fields = [
            'Firstname'  => 'firstname',
            'Lastname'   => 'lastname',
            'Address'    => 'address',
            'City'       => 'city',
            'Region'     => 'region',
            'PostalCode' => 'postal',
            'Country'    => 'country'
        ];

        foreach ($fields as $fieldKey => $fieldName) {
            if ($object->{$fieldKey} !== null) {
                $output[$key . $fieldName] = $object->{$fieldKey};
            }
        }
    }
}
