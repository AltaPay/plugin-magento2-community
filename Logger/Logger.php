<?php
/**
 * Valitor Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Valitor
 * @category  payment
 * @package   valitor
 */
namespace SDM\Valitor\Logger;

/**
 * Class Logger
 * @package SDM\Valitor\Logger
 */
class Logger extends \Monolog\Logger
{

    /**
     * @param $type
     * @param $data
     */
    public function addInfoLog($type, $data)
    {
        if (is_array($data)) {
            $this->addInfo($type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->addInfo($type . ': ' . json_encode($data));
        } else {
            $this->addInfo($type . ': ' . $data);
        }
    }

    /**
     * @param $type
     * @param $data
     */
    public function addErrorLog($type, $data)
    {
        if (is_array($data)) {
            $this->addError($type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->addError($type . ': ' . json_encode($data));
        } else {
            $this->addError($type . ': ' . $data);
        }
    }

    /**
     * @param $type
     * @param $data
     */
    public function addCriticalLog($type, $data)
    {
        if (is_array($data)) {
            $this->addCritical($type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->addCritical($type . ': ' . json_encode($data));
        } else {
            $this->addCritical($type . ': ' . $data);
        }
    }

    /**
     * @param $type
     * @param $data
     */
    public function addDebugLog($type, $data)
    {
        if (is_array($data)) {
            $this->addCritical($type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->addCritical($type . ': ' . json_encode($data));
        } else {
            $this->addCritical($type . ': ' . $data);
        }
    }
}
