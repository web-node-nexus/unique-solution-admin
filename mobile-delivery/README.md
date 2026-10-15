# Unique Solution — Delivery App

Expo 57 delivery partner app. Login is OTP-only via 2factor (API key stays on the Laravel server).

## Setup

```bash
cd mobile-delivery
npm install
npx expo start
```

Staff accounts must exist in admin (role **Staff**) with the same mobile number used for OTP.

## Live API

`http://94.103.163.218/unique-solution/api/v1`

Server `.env` needs:

```
TWOFACTOR_API_KEY=...
TWOFACTOR_SMS_TEMPLATE=OTP1
```
