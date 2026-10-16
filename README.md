# Laravel MFS SMS Forwarder Payment Gateway

[![Latest Version](https://img.shields.io/packagist/v/prantiksoft/laravel-mfs-sms-gateway.svg?style=flat-square)](https://packagist.org/packages/prantiksoft/laravel-mfs-sms-gateway)
[![Total Downloads](https://img.shields.io/packagist/dt/prantiksoft/laravel-mfs-sms-gateway.svg?style=flat-square)](https://packagist.org/packages/prantiksoft/laravel-mfs-sms-gateway)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

An automated, plug-and-play Bangladeshi Mobile Financial Services (**bKash**, **Nagad**, and **Rocket**) SMS forwarder payment gateway package for **Laravel 9, 10, and 11**.

Customers pay directly to your personal/merchant SIM via Send Money or Payment. An Android phone with an SMS forwarder app catches the transaction notification SMS and posts it to your Laravel webhook. This package **instantly parses the SMS, matches the pending order, updates the order status to paid, and redirects the customer—without requiring them to manually type a Transaction ID!**

---

## ✨ Features

- ⚡ **Zero Customer Friction**: Auto-detects customer payments in real-time via background webhook; manual TrxID entry is strictly optional as a fallback.
- 📱 **Multi-Provider Support**: Built-in regex parsers for **bKash**, **Nagad**, and **Rocket** (extensible for Upay).
- 🛡️ **Fraud & Duplicate Prevention**: Automatically prevents duplicate TrxID submissions and replay attacks.
- 🎨 **Branded Payment Waiting Screen**: Live pulse radar animation, copy wallet number button, 15-minute countdown timer, and 3-second status polling with dynamic provider color palettes (bKash `#e2136e`, Nagad `#f04923`, Rocket `#8c2d82`).
- 🔄 **Configurable Order Model**: Works with any Laravel `Order` model, column names, status values, and database schemas.
- 🔔 **Laravel Events**: Dispatches `MfsSmsReceived` and `MfsPaymentMatched` events for easy notifications, SMS alerts, POS synchronization, and accounting.
- 🧪 **Built-in Test Simulator**: Test and verify SMS parsing logic without needing an actual Android phone.

---

## 📦 Requirements

- PHP `^8.0` | `^8.1` | `^8.2` | `^8.3`
- Laravel `^9.0` | `^10.0` | `^11.0`

---

## 🚀 Installation

### Option 1: Via Composer (when published to Packagist)
```bash
composer require prantiksoft/laravel-mfs-sms-gateway
```

### Option 2: Using Local Path Repository (in any Laravel project)
If you keep the package inside your project (e.g. `packages/prantiksoft/laravel-mfs-sms-gateway`), add this to your main `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/prantiksoft/laravel-mfs-sms-gateway"
    }
],
"require": {
    "prantiksoft/laravel-mfs-sms-gateway": "*@dev"
}
```
Then run:
```bash
composer update prantiksoft/laravel-mfs-sms-gateway
```

---

## ⚙️ Configuration & Migrations

### 1. Publish Configuration and Migrations
```bash
# Publish configuration
php artisan vendor:publish --tag=mfs-sms-config

# Publish migrations (optional if auto-loading migrations)
php artisan vendor:publish --tag=mfs-sms-migrations

# Publish Blade views (optional, if you want custom styling)
php artisan vendor:publish --tag=mfs-sms-views
```

### 2. Run Database Migrations
```bash
php artisan migrate
```
This creates the `mfs_sms_transactions` table to record all forwarded SMS and matched orders.

### 3. Configure `.env` File
Add your wallet numbers and secret webhook tokens:

```env
# bKash SMS Settings
BKASH_SMS_ENABLED=true
BKASH_SMS_NUMBER=01735254295
BKASH_SMS_TYPE=Personal
BKASH_SMS_WEBHOOK_TOKEN=your_random_secret_token_bkash

# Nagad SMS Settings
NAGAD_SMS_ENABLED=true
NAGAD_SMS_NUMBER=01735254295
NAGAD_SMS_TYPE=Personal
NAGAD_SMS_WEBHOOK_TOKEN=your_random_secret_token_nagad

# Rocket SMS Settings (Optional)
ROCKET_SMS_ENABLED=false
ROCKET_SMS_NUMBER=01735254295-9
ROCKET_SMS_WEBHOOK_TOKEN=your_random_secret_token_rocket

# Order Model Customization (if your columns differ from defaults)
MFS_ORDER_MODEL="App\Models\Order"
MFS_PAID_STATUS_VALUE=7
MFS_PAID_PAYMENT_STATUS_VALUE="paid"
MFS_TIMEOUT_MINUTES=15
```

---

## 🛒 Usage in Checkout / Controller

When a customer selects bKash or Nagad SMS during checkout, simply redirect them to the automated payment route:

```php
// In your Checkout / OrderController:

if ($payment_method === 'bkash_sms') {
    return redirect()->route('mfs.sms.pay', [
        'order_id' => $order->id,
        'provider' => 'bkash'
    ]);
}

if ($payment_method === 'nagad_sms') {
    return redirect()->route('mfs.sms.pay', [
        'order_id' => $order->id,
        'provider' => 'nagad'
    ]);
}
```

That's it! The customer will see the live payment screen with your wallet number, amount, reference, countdown timer, and auto-detecting radar. Once your phone receives the SMS, the screen will instantly redirect them to your success page (`/customer/order-success/{order_id}`).

---

## 📲 Android SMS Forwarder Setup Guide

To forward SMS from your physical SIM card to your Laravel website:

### Step 1: Install a Forwarder App on your Android Phone
Any of the following free apps work seamlessly:
1. **SMS Forwarder** (by bogkonstantin) – Free open-source APK on [GitHub Releases](https://github.com/bogkonstantin/android-sms-forwarder/releases) or F-Droid.
2. **MacroDroid** or **Tasker** – Trigger: Incoming SMS $\rightarrow$ Action: HTTP Request.
3. **SMS to Webhook** – Available on Google Play Store.

### Step 2: Configure Webhook Destination in the App
Set the target URL with your secret token:

- **Unified Webhook (bKash & Nagad in one rule)**:
  ```
  https://yourdomain.com/api/mfs-sms/webhook?token=your_random_secret_token
  ```
- **Provider-Specific Webhooks**:
  ```
  https://yourdomain.com/api/mfs-sms/bkash/webhook?token=your_bkash_token
  https://yourdomain.com/api/mfs-sms/nagad/webhook?token=your_nagad_token
  ```

### Step 3: Configure Filter Rules
- **Method**: `POST`
- **Sender Filter**: `bKash` or `16247` for bKash; `NAGAD` or `16167` for Nagad.
- **Battery Optimization**: Disable battery optimization for the app so Android doesn't put it to sleep in the background.

---

## 🔔 Listening to Events

In your `EventServiceProvider` or listeners, you can easily listen for successful payments:

```php
use Prantiksoft\MfsSmsGateway\Events\MfsPaymentMatched;

Event::listen(MfsPaymentMatched::class, function (MfsPaymentMatched $event) {
    $order = $event->order;
    $transaction = $event->transaction; // MfsSmsTransaction instance
    $provider = $event->provider;       // 'bkash' or 'nagad'

    // Example: Dispatch customer SMS notification
    // Example: Sync with POS / ERP
    // Example: Notify admin via Telegram / WhatsApp
});
```

---

## 🧪 Testing with Test Simulator

You can test the parser without waiting for a real SMS:

```php
use Prantiksoft\MfsSmsGateway\Facades\MfsSms;

// Simulate Nagad SMS
$result = MfsSms::processSms(
    "Money Received. Amount: Tk 2,500.00. Sender: 01735254295. TxnID: 72J9KA21. Ref: Order #46. 16/10/2026 11:30",
    "NAGAD",
    "nagad"
);

print_r($result);
```

Or make an HTTP POST request to:
`POST /mfs-sms/test-simulator`
```json
{
  "provider": "nagad",
  "test_sms": "Money Received. Amount: Tk 2,500.00. Sender: 01735254295. TxnID: 72J9KA21. Ref: Order #46. 16/10/2026 11:30"
}
```

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
