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

namespace SDM\Altapay\Types;

/**
 * Allowed payment sources
 */
class PaymentSources implements TypeInterface
{

    /**
     * Allowed payment sources
     *
     * @var array<int, string>
     */
    private static $sources = [
        'eCommerce',
        'mobi',
        'moto',
        'mail_order',
        'telephone_order',
    ];

    /**
     * Get allowed values
     *
     * @return array<int, string>
     */
    public static function getAllowed()
    {
        return self::$sources;
    }

    /**
     * Is the requested value allowed
     *
     * @param string $value
     *
     * @return bool
     */
    public static function isAllowed($value)
    {
        return in_array($value, self::$sources);
    }
}
