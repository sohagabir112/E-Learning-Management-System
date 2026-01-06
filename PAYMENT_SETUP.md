# Payment System Setup Guide

## Overview
TMM Academy now includes a complete Stripe payment integration for course enrollment. This replaces the demo payment system with real credit card processing.

## Prerequisites
1. A Stripe account (sign up at https://stripe.com)
2. API keys from your Stripe dashboard

## Setup Steps

### 1. Get Your Stripe API Keys
1. Go to [Stripe Dashboard](https://dashboard.stripe.com/)
2. Navigate to **Developers > API keys**
3. Copy your **Publishable key** (starts with `pk_test_` for test mode)
4. Copy your **Secret key** (starts with `sk_test_` for test mode)

### 2. Configure API Keys
Edit `includes/config.php` and replace the placeholder values:

```php
// Replace these with your actual Stripe API keys
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_your_actual_publishable_key_here');
define('STRIPE_SECRET_KEY', 'sk_test_your_actual_secret_key_here');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_webhook_secret_here'); // Optional for webhooks
```

### 3. Test the Payment System
1. Start your local server (XAMPP)
2. Register/Login as a user
3. Try enrolling in a paid course
4. Use Stripe's test card numbers:
   - **Success**: `4242 4242 4242 4242`
   - **Decline**: `4000 0000 0000 0002`
   - Any future expiry date and any CVC

### 4. Webhook Setup (Optional but Recommended)
For production use, set up webhooks to handle payment events:

1. In Stripe Dashboard, go to **Developers > Webhooks**
2. Add endpoint: `https://yourdomain.com/stripe-webhook.php`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
4. Copy the webhook signing secret to `STRIPE_WEBHOOK_SECRET`

## Features Included

### ✅ Real Payment Processing
- Secure credit card payments via Stripe
- PCI compliant processing
- Support for multiple currencies
- Automatic payment confirmation

### ✅ Enrollment Management
- Pending enrollments during payment
- Automatic enrollment on successful payment
- Proper error handling for failed payments

### ✅ User Experience
- Clean, professional payment forms
- Real-time payment status updates
- Success/failure pages
- Mobile responsive design

### ✅ Security Features
- SSL encryption
- Secure token handling
- CSRF protection
- Input validation

## File Structure
```
├── includes/
│   └── config.php                 # Stripe configuration
├── stripe-webhook.php            # Webhook handler
├── user/
│   ├── payment.php               # Main payment page
│   ├── payment-success.php       # Success confirmation
│   └── payment-cancel.php        # Cancellation/failure page
└── PAYMENT_SETUP.md             # This setup guide
```

## Testing in Development

### Test Cards
Use these test card numbers in Stripe test mode:

| Card Number | Description |
|-------------|-------------|
| 4242424242424242 | Succeeds |
| 4000000000000002 | Declines |
| 4000000000009995 | Insufficient funds |
| 4000000000009987 | Expired card |

### Test Flow
1. User clicks "Enroll" on a paid course
2. Redirected to payment page
3. Enters test card details
4. Payment processes successfully
5. User enrolled and redirected to course

## Going Live

### 1. Switch to Live Mode
1. In Stripe Dashboard, toggle **Test mode** off
2. Get live API keys
3. Update `config.php` with live keys

### 2. Update Webhooks
- Update webhook endpoint URL for production
- Use live webhook signing secret

### 3. SSL Certificate
Ensure your site has SSL certificate for secure payments

### 4. Compliance
- Review PCI compliance requirements
- Complete Stripe's activation checklist

## Troubleshooting

### Common Issues
1. **"Invalid API Key"**: Check your keys in `config.php`
2. **Payment fails**: Ensure you're using test card numbers in test mode
3. **Webhook errors**: Check webhook endpoint URL and signing secret

### Debug Mode
Add this to see detailed errors:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## Support
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe Support](https://support.stripe.com/)
- Check PHP error logs for debugging

## Security Notes
- Never commit API keys to version control
- Use environment variables in production
- Keep webhook secrets secure
- Regularly update dependencies
