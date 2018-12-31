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

use SDM\Altapay\Request\OrderLine;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Orderlines resolver trait
 */
trait OrderlinesTrait
{

    /**
     * @param array|OrderLine $orderLines
     * @return $this
     */
    public function setOrderLines($orderLines)
    {
        if ($orderLines instanceof OrderLine) {
            $this->unresolvedOptions['orderLines'] = [$orderLines];
        }

        if (is_array($orderLines)) {
            foreach ($orderLines as $orderLine) {
                if (!$orderLine instanceof OrderLine) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'orderLines should all be a instance of "%s"',
                            OrderLine::class
                        )
                    );
                }
            }

            $this->unresolvedOptions['orderLines'] = $orderLines;
        }

        return $this;
    }

    protected function setOrderLinesResolver(OptionsResolver $resolver)
    {
        $resolver->addAllowedTypes('orderLines', 'array');
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer('orderLines', function (Options $options, $value) {
            $output = [];
            /** @var OrderLine $object */
            foreach ($value as $object) {
                $output[] = $object->serialize();
            }
            return $output;
        });
    }
}
