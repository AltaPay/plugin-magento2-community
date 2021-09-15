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

namespace SDM\Altapay\Traits;

use SDM\Altapay\Types\CurrencyTypes;
use Symfony\Component\OptionsResolver\OptionsResolver;

trait CurrencyTrait
{

    /**
     * Either 3 letter or 3 digit currency code. ISO-4217
     *
     * @param string|int $currency
     *
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->unresolvedOptions['currency'] = $currency;
        return $this;
    }

    /**
     * Resolve amount option
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    protected function setCurrencyResolver(OptionsResolver $resolver)
    {
        $resolver->setAllowedTypes('currency', ['string', 'int']);
        $resolver->setAllowedValues('currency', function ($value) {
            return CurrencyTypes::currencyCodeExists($value) || CurrencyTypes::currencyNumberExists($value);
        });
    }
}
