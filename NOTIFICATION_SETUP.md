# Notification System Setup

This project includes email and SMS notification capabilities for tenant invitations.

## Environment Variables

Add the following variables to your `.env` file:

### Email (SendGrid)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@domain.com
MAIL_FROM_NAME="Your App Name"
```

### SMS (Twilio)
```
TWILIO_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_ALPHANUMERIC_ID=IHRM
```

### Frontend URL
```
FRONTEND_URL=http://localhost:4200
```

## API Endpoints

### Send Email
```
POST /api/notifications/email
{
    "email": "recipient@example.com",
    "subject": "Email Subject",
    "message": "Email content",
    "attachments": [] // optional
}
```

### Send SMS
```
POST /api/notifications/sms
{
    "phone_number": "+1234567890",
    "message": "SMS content"
}
```

## Usage in Code

### Using NotificationService
```php
use App\Services\NotificationService;

// Send invitation
$notificationService->sendInvitation($invitation, $tenant, $inviter);

// Send custom email
$notificationService->sendEmail('user@example.com', 'Subject', 'Message');

// Send custom SMS
$notificationService->sendSms('+1234567890', 'Message');
```

## Features

- **Email Notifications**: Uses SendGrid for reliable email delivery
- **SMS Notifications**: Uses Twilio for SMS delivery
- **Invitation System**: Automatic email/SMS sending when creating tenant invitations
- **Error Handling**: Graceful error handling with logging
- **Development Mode**: SSL verification disabled in development environment 