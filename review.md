# Critical Review — `support-checkoutSession` branch vs `main`

Scope: everything unique to this branch relative to `main` (`git diff main...HEAD`) —
`Controller/Index/ApplePay.php`, `Model/Gateway.php`, `Model/SystemConfig.php`,
`Model/ConstantConfig.php`, `etc/adminhtml/system/terminal*.xml`, `etc/module.xml`,
`composer.json`, `terminal-abstract.js`, `wiki.md`. No code was changed as part of this review.

No changes have been made to the code — this is analysis only.

## 🔴 Critical — will fatal-error in production

### 1. `Model/Gateway.php:387` calls `CardWalletAuthorize::setPaymentId()`, which does not exist
```php
$request = new CardWalletAuthorize($auth);   // line 379
...
$paymentId = $this->checkoutSession->getData('altapay_payment_id');
if ($paymentId) {
    $request->setPaymentId($paymentId);      // line 387 — undefined method
}
```
`CardWalletAuthorize` (`vendor/altapay/api-php/src/Api/Payments/CardWalletAuthorize.php`) only uses
`TerminalTrait, AmountTrait, CurrencyTrait, ShopOrderIdTrait, TransactionInfoTrait, CustomerInfoTrait, OrderlinesTrait`.
None of these define `setPaymentId()`, and the class has no `__call` magic method. The only
`setPaymentId()` implementations in the vendored SDK belong to unrelated classes
(`UpdateOrder`, `Api/Others/Payments`, `CalculateSurcharge`, `UpdateReconciliationIdentifier`).

**Impact:** Every authorize call for a non-legacy ("MarketPay") Apple Pay terminal where a
`altapay_payment_id` was stored will throw `Error: Call to undefined method
Altapay\Api\Payments\CardWalletAuthorize::setPaymentId()` — a fatal PHP error during checkout
place-order. This is the primary code path the whole feature exists for.

### 2. `Controller/Index/ApplePay.php:127-134` calls four methods that don't exist on `CardWalletSession`
```php
$request = new CardWalletSession($auth);      // line ~103
...
$request->setShopOrderId($quote->reserveOrderId()->getReservedOrderId())
        ->setAmount(round($grandTotal, 2))
        ->setCurrency($currencyCode)
        ->setApplePayRequestData([...]);
```
`CardWalletSession` (`vendor/altapay/api-php/src/Api/Payments/CardWalletSession.php`) only uses
`TerminalTrait` and defines `setValidationUrl()` / `setDomain()`. It has **no**
`setShopOrderId`, `setAmount`, `setCurrency`, or `setApplePayRequestData` methods anywhere in the
vendored SDK (`setApplePayRequestData` doesn't exist in the SDK at all, under any class).

**Impact:** Every Apple Pay "validate merchant" call for a non-legacy terminal will fatal-error
immediately with `Error: Call to undefined method
Altapay\Api\Payments\CardWalletSession::setShopOrderId()` before the request is even sent to
AltaPay. The Apple Pay sheet will simply fail for the customer.

### 3. Root cause of #1/#2: `composer.json` bumps the SDK requirement, but the lock file/vendor were never updated
```diff
- "altapay/api-php": "^3.5.9"
+ "altapay/api-php": "^3.6.0"
```
`composer.lock` was **not touched** by this branch (`git diff main...HEAD -- composer.lock` is
empty) and still pins `altapay/api-php` at **3.5.8** — which doesn't even satisfy the *old*
`^3.5.9` constraint, let alone the new `^3.6.0` one. Installed `vendor/altapay/api-php` is 3.5.8,
which is why none of the methods used above exist.

**This needs to be verified before anything else**: does `altapay/api-php` 3.6.0 actually exist
and implement `setShopOrderId`/`setAmount`/`setCurrency`/`setApplePayRequestData` on
`CardWalletSession` and `setPaymentId` on `CardWalletAuthorize`? If that SDK version doesn't exist
yet or doesn't add these methods, this feature cannot work at all as written. Either way,
`composer.lock` must be regenerated (`composer update altapay/api-php`) before this ships, or a
fresh `composer install` elsewhere will also pull the old 3.5.8 (lock-file wins) and reproduce the
same fatal errors.

## 🟠 High — dead code / silently swallowed success path

### 4. `getSessionData()`'s `WalletData` branch can never execute
```php
private function getSessionData($response)
{
    $data = ['message' => __(ConstantConfig::PAYMENT_FAILED)];
    if ($response->Result === 'Success') {
        if (isset($response->ApplePaySession)) {
            $data = $response->ApplePaySession;
        } elseif (isset($response->WalletData->Session)) {   // <-- dead branch
            ...
        }
    }
    return $data;
}
```
The response is deserialized via `Altapay\Serializer\ResponseSerializer::serialize(PaymentRequestResponse::class, ...)`.
`Altapay\Response\AbstractResponse::set()` only ever assigns a property if
`property_exists($object, $elementName)` is true (see `vendor/altapay/api-php/src/Response/AbstractResponse.php`).
`PaymentRequestResponse` has no `$WalletData` property declared, so any `<WalletData>` node in the
XML response is silently dropped — it is never attached to the object. `isset($response->WalletData->Session)`
will therefore always evaluate to `false`.

**Impact:** Even in the best case where the new SDK version and the future AltaPay gateway
(version "20260113") return wallet data instead of `ApplePaySession`, this module will report
`PAYMENT_FAILED` on every *successful* non-legacy Apple Pay session response, because the data is
never read into the response object. (This will need a `$childs`/property addition to
`PaymentRequestResponse` in the SDK, not just this module.)

### 5. Frontend never distinguishes the new failure payload from a real Apple Pay session
`terminal-abstract.js` (`onvalidatemerchant`) is unchanged apart from adding `terminalCode` to the
POST body:
```js
success: function(response) {
    var responsedata = jQuery.parseJSON(response);
    session.completeMerchantValidation(responsedata);
}
```
The backend can now return `{"message": "Payment failed. Please try again."}` instead of an Apple
Pay merchant session object, but the JS blindly forwards whatever comes back into
`session.completeMerchantValidation(responsedata)`. Apple's `ApplePaySession` API expects a real
merchant session object; passing `{message: "..."}"` will make merchant validation fail with an
opaque native error, not the friendly message the backend now tries to provide. The new
`PAYMENT_FAILED` string is effectively unreachable by the customer — none of the code added in
this branch actually surfaces it in the UI.

## 🟡 Medium

### 6. Duplicate `sortOrder="30"` in all 10 terminal config XML files
```xml
<field id="legacyapplepayflow" ... sortOrder="30" ...>
<field id="applepaylabel" ... sortOrder="30" ...>
```
Present identically in `terminal1.xml` through `terminal10.xml`. Not fatal, but the relative order
of the two admin fields is undefined/fragile (currently relies on declaration order, which Magento
does not guarantee across all renderers/versions). Should be a unique sort order (e.g. `29`/`31`).

### 7. `CHANGELOG.md` was not updated for this branch's changes
- `etc/module.xml` and `composer.json` both bump the module version `4.2.5 → 4.2.6`.
- `CHANGELOG.md` has **no `[4.2.6]` entry**, and no mention of `checkoutSession`, `legacyapplepayflow`,
  or the MarketPay Apple Pay flow at all (`git diff main...HEAD -- CHANGELOG.md` is empty).
- The changelog's current top entry is an unreleased `[4.3.0]` block ("Add support for MarketPay
  payment methods", "Add support for Google Pay") that predates and doesn't describe this branch's
  actual Apple Pay/legacy-flow work, and there's a version-ordering gap (`4.3.0` → `4.2.4`, skipping
  `4.2.5`/`4.2.6` entirely).
- Given items #1–#3 above, documenting this in the changelog as shipped would be actively
  misleading — the feature does not run yet.

### 8. `reserveOrderId()` called on every Apple Pay merchant-validation request
`Controller/Index/ApplePay.php`: `$quote->reserveOrderId()->getReservedOrderId()` runs on every
`onvalidatemerchant` callback, which Apple Pay can invoke more than once per checkout session
(e.g. retries). `reserveOrderId()` is idempotent once a reserved ID exists on the quote, so this is
low-risk, but it's worth confirming under real Apple Pay retry conditions since it persists the
quote each time it runs.

## ⚪ Low / housekeeping

- File-mode changes (`100755 → 100644`) on `etc/module.xml`, `Model/ConstantConfig.php`,
  `Model/SystemConfig.php`, all `terminal*.xml`, and `terminal-abstract.js` — harmless, but likely
  unintentional (editor/tooling side effect) and adds noise to the diff.
- `Helper/Config::useBaseCurrency(string $moduleVersion = null)` (called with no args from
  `ApplePay.php`) uses an implicitly-nullable parameter type, which is deprecated on PHP 8.4+.
  Pre-existing, not introduced by this branch, but adjacent to code this branch now calls more often.

## Bottom line

The **legacy Apple Pay path is untouched and safe** — existing merchants who don't touch the new
"Legacy Apple Pay flow" setting will see no behavior change.

The **new MarketPay/non-legacy Apple Pay path is not functional as committed**: it calls SDK
methods (`setShopOrderId`, `setAmount`, `setCurrency`, `setApplePayRequestData` on
`CardWalletSession`; `setPaymentId` on `CardWalletAuthorize`) that do not exist in the
`altapay/api-php` version actually locked and vendored (3.5.8), and even assuming a future SDK
version adds them, the response-side `WalletData` handling and the frontend error handling are
both incomplete. Before merging/releasing, at minimum:
1. Confirm `altapay/api-php` `^3.6.0` exists and actually implements these methods; update
   `composer.lock` and vendor accordingly.
2. Add a `WalletData` property (or `$childs` entry) to `PaymentRequestResponse` if that's how the
   new gateway responds, and verify against a real gateway on version 20260113+.
3. Update `terminal-abstract.js` to check for an error/`message` field before calling
   `session.completeMerchantValidation()`.
4. Fix the duplicate `sortOrder` values and add real `CHANGELOG.md`/version entries once the above
   is verified working end-to-end.
