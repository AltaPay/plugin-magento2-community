<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright 2018 Altapay
 * @category  payment
 * @package   altapay
 */
namespace SDM\Altapay\Model\Config\Source;

use Magento\Config\Model\Config\Source\Locale;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Languages
 * @package SDM\Altapay\Model\Config\Source
 */
class Languages extends Locale implements ArrayInterface
{
    // nb, nn will be converted to no.
    //ee will be converted to et
    /**
     * @var array
     */
    private static $allowedLanguages = [
        'cs', 'da', 'de', 'en', 'es', 'fi', 'fr', 'ja',
        'lt', 'nl', 'no', 'nb', 'nn', 'pl', 'sv','th', 'tr', 'zh',
        'et', 'ee', 'it', 'pt', 'eu'
    ];

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $languages = [];
        $mainlanguages = parent::toOptionArray();
        foreach ($mainlanguages as $keylang => $language) {
            list($key, $tmp) = explode('_', $language['value']);
            if (in_array($key, self::$allowedLanguages)) {
                $languages[$key] = $language;
            }
        }

        return array_merge([
            ['value' => '', 'label' => '- Auto select -']
        ], $languages);
    }
}
