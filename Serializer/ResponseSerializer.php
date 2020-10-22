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

namespace SDM\Altapay\Serializer;

use SDM\Altapay\Response\AbstractResponse;

/**
 * Response serializer
 * Serialize response from XML Element.
 */
class ResponseSerializer
{

    /**
     * Serialize a response
     *
     * @param string            $objectName
     * @param \SimpleXMLElement $data
     * @param bool              $childKey
     * @param \SimpleXMLElement $header
     *
     * @return AbstractResponse|array
     */
    public static function serialize(
        $objectName,
        \SimpleXMLElement $data,
        $childKey = false,
        \SimpleXMLElement $header = null
    ) {
        if ($childKey !== false) {
            $documents = [];
            foreach ($data->{$childKey} as $d) {
                /** @var AbstractResponse $object */
                $object      = new $objectName;
                $documents[] = $object->deserialize($d);
            }

            return $documents;
        }

        /** @var AbstractResponse $object */
        $object = new $objectName;
        $object->headerSetter($header);

        return $object->deserialize($data);
    }
}
