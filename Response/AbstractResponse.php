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

namespace SDM\Altapay\Response;

use SDM\Altapay\Response\Embeds\Header;
use SDM\Altapay\Serializer\ResponseSerializer;

/**
 * Class AbstractResponse
 */
abstract class AbstractResponse
{

    /**
     * Header of the result
     *
     * @var Header
     */
    public $Header;

    /**
     * Childs of the response
     *
     * @var array<string, array<string, mixed>>
     */
    protected $childs = [];

    /**
     * @return void
     */
    public function headerSetter(\SimpleXMLElement $xml = null)
    {
        if ($xml) {
            $this->Header = ResponseSerializer::serialize(Header::class, $xml);
        }
    }

    /**
     * Deserialize XML to object
     *
     * @param \SimpleXMLElement $xml
     *
     * @return static
     */
    public function deserialize(\SimpleXMLElement $xml = null)
    {
        $object = clone $this;

        if ($xml) {
            $this->attributeSetter($object, $xml);

            try {
                $this->elementSetter($object, trim((string)$xml), $xml);
            } catch (\InvalidArgumentException $e) {
                // Do nothing if element setter not found.
            }

            foreach ($xml->children() as $child) {
                if (isset($this->childs[$child->getName()])) {
                    $builder = $this->childs[$child->getName()];
                    /** @var class-string<AbstractResponse> */
                    $className = $builder['class'];
                    /** @var string|false */
                    $childKey = $builder['array'];
                    if ($childKey === false) {
                        $data = ResponseSerializer::serialize($className, $child);
                    } else {
                        $data = ResponseSerializer::serializeChildren($className, $child, $builder['array']);
                    }
                } else {
                    $data = trim((string)$child);
                    $this->attributeSetter($object, $child);
                }

                $this->elementSetter($object, $data, $child);
            }
        }

        return $object;
    }

    /**
     * Sets attributes
     *
     * @param object            $object
     * @param \SimpleXMLElement $element
     *
     * @return void
     */
    private function attributeSetter($object, \SimpleXMLElement $element)
    {
        if ($element->getName()) {
            foreach ($element->attributes() ?: [] as $attribute) {
                if (!$this->set($object, (string)$attribute, $attribute)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'The attribute "%s" on element "%s" does not have a setter or a property in class "%s"',
                            $attribute->getName(),
                            $element->getName(),
                            get_called_class()
                        )
                    );
                }
            }
        }
    }

    /**
     * Sets elements
     *
     * @param object            $object
     * @param mixed             $data
     * @param \SimpleXMLElement $element
     *
     * @return void
     */
    private function elementSetter($object, $data, \SimpleXMLElement $element)
    {
        if (!$this->set($object, $data, $element)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The parameter "%s" does not have a setter or a property in class "%s"',
                    $element->getName(),
                    get_called_class()
                )
            );
        }
    }

    /**
     * Setter
     *
     * @param object            $object
     * @param mixed             $data
     * @param \SimpleXMLElement $element
     *
     * @return bool
     */
    private function set($object, $data, \SimpleXMLElement $element)
    {
        if ($element->getName()) {
            $setter = 'set' . ucfirst($element->getName());
            if (method_exists($object, $setter)) {
                $object->{$setter}($data);

                return true;
            }

            if (property_exists($object, $element->getName())) {
                $object->{$element->getName()} = $data;

                return true;
            }
        }

        return false;
    }
}
