<?php
/**
 * Altapay Module for Magento 2.x.
 *
 * Copyright © 2018 Altapay. All rights reserved.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SDM\Altapay\Model\Config\Source;

use Altapay\Response\TerminalsResponse;
use Magento\Framework\Option\ArrayInterface;

class TerminalLogo
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return $this->toArray();
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $terminalsLogosWithLabel = [];

        $terminalLogos = [
            ''                      => '-- Select --',
            'amex'                  => 'Amex',
            'apple_pay'             => 'ApplePay',
            'bancontact'            => 'Bancontact',
            'bank'                  => 'BankPayment',
            'creditcard'            => 'CreditCard',
            'creditcard_wallet'     => 'Credit Card Wallet',
            'dankort'               => 'Dankort',
            'dnb'                   => 'Dnb',
            'e_payment'             => 'E-Payment',
            'elv_giropay'           => 'Elv Giropay',
            'giftcard'              => 'Gift Card',
            'ideal'                 => 'iDeal',
            'invoice'               => 'Invoice',
            'jcb'                   => 'JCB',
            'klarna_pink'           => 'Klarna',
            'klarna'                => 'Klarna - Black',
            'maestro'               => 'Maestro',
            'mastercard'            => 'Mastercard',
            'mobilepay'             => 'MobilePay',
            'mobilepay_horizontal'  => 'MobilePay - Horizontal',
            'bank'                  => 'OpenBanking',
            'payconiq'              => 'Payconiq',
            'paypal'                => 'PayPal',
            'przelewy24'            => 'Przelewy24',
            'seb'                   => 'SEB',
            'sepa'                  => 'Sepa',
            'swish'                 => 'SwishSweden',
            'swish_horizontal'      => 'Swish - Horizontal',
            'trustly'               => 'Trustly',
            'twint'                 => 'Twint',
            'viabill'               => 'ViaBill',
            'vipps'                 => 'Vipps',
            'visa'                  => 'Visa',
            'visa_electron'         => 'Visa Electron'
        ];

        foreach ($terminalLogos as $key => $val) {
            $terminalsLogosWithLabel[] = ['value' => $key, 'label' => $val];
        }

        return $terminalsLogosWithLabel;
    }
}
