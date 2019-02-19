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

namespace SDM\Valitor\Traits;

use SDM\Valitor\Request\Customer;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait CustomerInfoTrait
{

    /**
     * Customer info - used for fraud detection
     *
     * @param Customer $customer
     * @return $this
     */
    public function setCustomerInfo(Customer $customer)
    {
        $this->unresolvedOptions['customer_info'] = $customer;
        if ($customer->getCreatedDate()) {
            $this->unresolvedOptions['customer_created_date'] = $customer->getCreatedDate()->format('Y-m-d');
        }
        return $this;
    }

    /**
     * Resolve amount option
     *
     * @param OptionsResolver $resolver
     */
    protected function setCustomerInfoResolver(OptionsResolver $resolver)
    {
        $resolver->setAllowedTypes('customer_info', Customer::class);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer('customer_info', function (Options $options, Customer $value) {
            return $value->serialize();
        });
    }
}
