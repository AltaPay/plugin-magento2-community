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

namespace SDM\Valitor;

use SDM\Valitor\Exceptions\ClassDoesNotExistsException;
use SDM\Valitor\Api\Ecommerce;
use SDM\Valitor\Api\Others;
use SDM\Valitor\Api\Payments;
use SDM\Valitor\Api\Subscription;
use SDM\Valitor\Api\Test;

class Factory
{
    const ECOMMERCE_PAYMENT_REQUEST = Ecommerce\PaymentRequest::class;
    const ECOMMERCE_CALLBACK = Ecommerce\Callback::class;

    const OTHERS_CALCULATE_SURCHARGE = Others\CalculateSurcharge::class;
    const OTHERS_CUSTOM_REPORT = Others\CustomReport::class;
    const OTHERS_FUNDING_DOWNLOAD = Others\FundingDownload::class;
    const OTHERS_FUNDING_LIST = Others\FundingList::class;
    const OTHERS_INVOICE_TEXT = Others\InvoiceText::class;
    const OTHERS_PAYMENTS = Others\Payments::class;
    const OTHERS_QUERY_GIFT_CARD = Others\QueryGiftcard::class;
    const OTHERS_TERMINALS = Others\Terminals::class;

    const PAYMENTS_CAPTURE_RESERVATION = Payments\CaptureReservation::class;
    const PAYMENTS_CREDIT = Payments\Credit::class;
    const PAYMENTS_INVOICE_RESERVATION = Payments\InvoiceReservation::class;
    const PAYMENTS_REFUND_CAPTURED_RESERVATION = Payments\RefundCapturedReservation::class;
    const PAYMENTS_RELEASE_RESERVATION = Payments\ReleaseReservation::class;
    const PAYMENTS_RESERVATION_OF_FIXED_AMOUNT = Payments\ReservationOfFixedAmount::class;

    const SUBSCRIPTION_CHARGE_SUBSCRIPTION = Subscription\ChargeSubscription::class;
    const SUBSCRIPTION_RESERVE_SUBSCRIPTION_CHARGE = Subscription\ReserveSubscriptionCharge::class;
    const SUBSCRIPTION_SETUP_SUBSCRIPTION = Subscription\SetupSubscription::class;

    const TEST_AUTHENTICATION = Test\TestAuthentication::class;
    const TEST_CONNECTION = Test\TestConnection::class;

    public static function create($class, Authentication $authentication = null)
    {
        if (class_exists($class)) {
            return new $class($authentication);
        }

        throw new ClassDoesNotExistsException($class);
    }
}
