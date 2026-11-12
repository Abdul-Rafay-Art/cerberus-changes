# Cerberus Live Deployment Reference

Use this file as the non-secret reference for future live deployments.

## Server

- Domain: `https://cerberus.codescale.dev`
- IPv4: `72.60.196.141`
- SSH user: `root`
- SSH key: `C:\Users\COU1\.ssh\cyberpanel_cerberus`
- Remote application path: `/home/codescale.dev/public_html/cerberus`
- Web document root: `/home/codescale.dev/public_html/cerberus/public`
- OpenLiteSpeed vhost: `/usr/local/lsws/conf/vhosts/cerberus.codescale.dev/vhost.conf`

## Database

- Database: `cerberus_prod`
- Application user: `cerberus_app`
- Host: `127.0.0.1`
- Password: stored only in the protected server `.env`

## Application

- Framework: Laravel
- PHP: `8.3`
- Live packages: Gold GBP 249, Silver GBP 170, Bronze GBP 120
- Migrations: applied
- Admin email: `admin@gmail.com`
- Admin password: never store in this file; change it after login

## Current Mail Mode

The server is temporarily using Laravel `log` mail mode for flow testing. Emails are written to:

`/home/codescale.dev/public_html/cerberus/storage/logs/laravel.log`

Before real release, configure an approved SMTP provider and change `MAIL_MAILER` from `log` to `smtp`. Do not store SMTP passwords here.

## Deployment Notes

- Never expose the project root as the document root; use `/public`.
- Never commit or copy `.env`, SSH private keys, database passwords, SMTP passwords, or customer data.
- Use the existing SSH key and inspect this file before future live deployment work.
