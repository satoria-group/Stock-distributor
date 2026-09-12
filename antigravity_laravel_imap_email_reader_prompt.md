# Anti Gravity Prompt — Laravel IMAP Email Reader

## Role

You are a senior Laravel engineer. Implement a production-ready **read-only email viewer** inside an existing Laravel application.

The application is an internal company website with only around **2–5 users**.

Do not overengineer the solution.

---

## Main Goal

Add a feature that allows authenticated users to:

1. View emails from the company mail server.
2. Open/read individual emails.
3. View basic email metadata.
4. See attachment information.
5. Download attachments securely.
6. Optionally preview safe attachment types.
7. Refresh the inbox manually.

The application must use the company's own mail server through **IMAP**.

Sending email is **NOT required**.

SMTP is **NOT required**.

---

## Architecture

Use this architecture:

```text
User
  |
  | HTTPS
  v
Laravel Application
  |
  | IMAPS / TLS
  v
Company Mail Server
```

Laravel should communicate directly with the IMAP server.

For the current scale of only 2–5 users:

- Do NOT create an email synchronization worker.
- Do NOT copy all emails into PostgreSQL.
- Do NOT introduce Kafka.
- Do NOT introduce Redis unless Redis already exists in the project.
- Do NOT introduce background queues only for fetching emails.
- Do NOT create unnecessary microservices.

Use a short Laravel cache for inbox requests.

Recommended cache duration:

```text
30–60 seconds
```

Attachments must remain on the mail server and should only be fetched when requested.

---

## Technology

Use:

- Laravel
- PHP
- IMAP
- `webklex/php-imap`
- Laravel Cache
- Existing Laravel authentication
- Existing frontend stack in the project

Install:

```bash
composer require webklex/php-imap
```

Before implementing, inspect the existing Laravel project structure and follow the project's conventions.

Do not unnecessarily rewrite existing application architecture.

---

# Environment Configuration

Use `.env` variables.

Example:

```env
IMAP_HOST=mail.example.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=true

IMAP_USERNAME=mailbox@example.com
IMAP_PASSWORD=change_me

IMAP_MAILBOX=INBOX
IMAP_CONNECTION_TIMEOUT=10
IMAP_CACHE_TTL=30
```

Never hardcode credentials.

Never expose credentials to frontend JavaScript.

Do not commit credentials into Git.

Add the required placeholders to `.env.example`.

---

# Security Requirements

This is a production application.

Implement the following security requirements.

## 1. IMAPS

Use secure IMAP:

```text
Port: 993
Encryption: SSL/TLS
Certificate validation: enabled
```

Never disable certificate validation in production.

---

## 2. Authentication

All email routes must require authentication.

Example:

```php
Route::middleware('auth')->group(function () {
    // email routes
});
```

Reuse the existing authentication and authorization system.

Do not create a separate login mechanism.

---

## 3. Authorization

Do not assume that knowing an email UID means the user is authorized to access it.

Every email/detail/attachment endpoint must pass through Laravel authorization.

If the application currently has roles or permissions, reuse them.

If no specific email permission exists, keep the implementation easy to extend later.

---

## 4. HTML Email Sanitization

Never render raw email HTML directly using:

```php
{!! $emailHtml !!}
```

without sanitization.

Email content is untrusted external input.

Sanitize HTML before rendering.

Protect against:

- XSS
- inline scripts
- event handlers
- malicious links
- embedded HTML attacks

If the project does not already contain an HTML sanitizer, choose a maintained PHP-compatible sanitizer package.

Prefer removing dangerous content rather than attempting to execute it safely.

---

## 5. Remote Images

Avoid automatically loading remote tracking images from emails.

If possible:

- block remote images by default,
- or sanitize them,
- or provide a controlled mechanism for displaying them.

Do not allow email HTML to silently leak user IP/address information through tracking pixels.

---

# IMAP Message Identification

Do NOT use IMAP message sequence numbers as permanent identifiers.

Use:

```text
UID
```

where possible.

For future compatibility, structure the service so that the system could account for:

```text
mailbox + UIDVALIDITY + UID
```

Do not rely on message order.

---

# Required Application Structure

Prefer a clean service-based implementation.

Suggested structure:

```text
app/
├── Services/
│   └── ImapService.php
│
├── Http/
│   └── Controllers/
│       └── EmailController.php
│
├── DTOs/
│   ├── EmailListItem.php
│   ├── EmailDetail.php
│   └── EmailAttachment.php
│
└── Exceptions/
    └── MailServerException.php
```

DTO classes are optional if the existing project uses another pattern.

Follow existing project conventions before introducing new abstractions.

---

# ImapService Responsibilities

Create an `ImapService`.

It should be responsible for:

```text
connect()
getInbox()
getMessage()
getAttachments()
getAttachment()
disconnect()
```

Avoid putting IMAP logic directly inside controllers.

Example conceptual API:

```php
$imap->getInbox(
    page: 1,
    perPage: 20
);

$imap->getMessage($uid);

$imap->getAttachment(
    uid: $uid,
    attachmentId: $attachmentId
);
```

Controllers should stay thin.

---

# Inbox Requirements

Create an inbox page/API.

Display at least:

```text
From
Subject
Date
Unread / Read status
Has attachment
```

Example response structure:

```json
{
  "data": [
    {
      "uid": 12345,
      "from": {
        "name": "John Doe",
        "email": "john@example.com"
      },
      "subject": "Purchase Order",
      "received_at": "2026-09-12T08:30:00+07:00",
      "is_read": false,
      "has_attachment": true
    }
  ]
}
```

---

# Pagination

Do NOT fetch the entire mailbox.

Use pagination.

Default:

```text
20 emails per page
```

Maximum:

```text
50 emails per page
```

Order by newest first.

The inbox must remain usable even if the mailbox contains thousands of messages.

---

# Caching

Use Laravel Cache for inbox results.

Recommended:

```text
30 seconds
```

Example concept:

```php
Cache::remember(
    "mail:inbox:{$mailbox}:page:{$page}:per-page:{$perPage}",
    now()->addSeconds(30),
    fn () => $imapService->getInbox($page, $perPage)
);
```

Do not cache email credentials.

Do not cache attachment binary data by default.

Provide a manual refresh mechanism that clears relevant inbox cache keys.

---

# Email Detail

Create an endpoint/page for:

```text
GET /emails/{uid}
```

Display:

```text
From
To
CC
Subject
Date
Text body
HTML body (sanitized)
Attachments
```

Prefer HTML body if available.

Otherwise fall back to plain-text body.

Do not break when:

- subject is empty,
- sender name is missing,
- HTML body does not exist,
- text body does not exist,
- attachments do not exist.

---

# Attachments

Attachments should NOT be permanently copied into application storage unless explicitly required.

Fetch attachments on demand from IMAP.

Suggested endpoint:

```text
GET /emails/{uid}/attachments/{attachmentId}
```

The flow must be:

```text
Browser
   |
   v
Laravel Authentication
   |
   v
Authorization
   |
   v
IMAP fetch attachment
   |
   v
Laravel streams response
   |
   v
Browser
```

---

# Attachment Download Security

Use safe response headers.

At minimum:

```text
Content-Type: <validated mime type>
Content-Disposition: attachment; filename="..."
X-Content-Type-Options: nosniff
```

Sanitize the filename.

Prevent:

```text
../
\
null bytes
header injection
```

Never use a user-controlled path directly on the filesystem.

---

# Attachment Preview

If preview functionality is implemented, only allow a safe MIME allowlist.

Suggested preview allowlist:

```text
application/pdf
image/jpeg
image/png
image/webp
text/plain
```

Potentially dangerous types should download instead of rendering inline.

Examples:

```text
text/html
image/svg+xml
application/javascript
application/x-msdownload
application/x-sh
```

Do not execute or render active content inside the application origin.

---

# Large Attachments

Avoid unnecessarily loading very large attachment files into memory if the selected IMAP library/API allows streaming.

If true streaming from IMAP is not practical with the installed library, implement sensible safeguards.

Add a configurable maximum attachment size, for example:

```env
MAIL_ATTACHMENT_MAX_MB=25
```

Return a user-friendly error if the configured limit is exceeded.

---

# Error Handling

Handle mail server failures cleanly.

Potential errors:

```text
IMAP connection timeout
Authentication failure
TLS error
Mailbox unavailable
Message UID not found
Attachment not found
Mail server temporarily unavailable
```

Do not show raw exceptions or credentials to users.

Return friendly responses, for example:

```text
Mail server is temporarily unavailable.
Please try again.
```

Log technical details server-side.

Never log:

```text
IMAP password
full authentication credentials
attachment binary contents
```

---

# Timeout

IMAP operations must not be allowed to hang indefinitely.

Use a reasonable connection timeout.

Recommended:

```text
5–10 seconds
```

If the mail server exceeds the timeout, fail gracefully.

---

# Controller

Create a thin `EmailController`.

Suggested methods:

```php
index()
show(string $uid)
downloadAttachment(string $uid, string $attachmentId)
refresh()
```

Example routes:

```php
Route::middleware('auth')->prefix('emails')->group(function () {
    Route::get('/', [EmailController::class, 'index'])
        ->name('emails.index');

    Route::post('/refresh', [EmailController::class, 'refresh'])
        ->name('emails.refresh');

    Route::get('/{uid}', [EmailController::class, 'show'])
        ->name('emails.show');

    Route::get('/{uid}/attachments/{attachmentId}', [
        EmailController::class,
        'downloadAttachment'
    ])->name('emails.attachments.download');
});
```

Adjust routes to match the existing API/web architecture.

---

# Frontend

Follow the frontend already used by the project.

Do not introduce another frontend framework.

Create a simple professional inbox.

Suggested layout:

```text
┌─────────────────────────────────────────────────────────────┐
│ Email                                          [ Refresh ]   │
├─────────────────────────────────────────────────────────────┤
│ From               Subject                 Date       📎    │
├─────────────────────────────────────────────────────────────┤
│ vendor@mail.com    Purchase Order          10:24      📎    │
│ finance@mail.com   Invoice September       09:50            │
│ client@mail.com    Re: Delivery            Yesterday  📎    │
└─────────────────────────────────────────────────────────────┘
```

Clicking an email opens the detail page.

Email detail:

```text
Subject
From
To
Date

-------------------------------------

Email body

-------------------------------------

Attachments

invoice.pdf       1.2 MB       [Download]
purchase.xlsx     220 KB       [Download]
```

---

# UX Requirements

Include:

- loading state,
- empty inbox state,
- IMAP connection failure state,
- pagination,
- refresh button,
- attachment icons,
- readable dates,
- safe error messages.

Avoid overly complex UI.

---

# Performance

The expected number of users is only:

```text
2–5 concurrent/internal users
```

Optimize for simplicity.

Do NOT implement premature distributed architecture.

Use:

```text
Laravel
   |
   +-- IMAP directly
   |
   +-- Laravel Cache 30–60 seconds
   |
   +-- Attachment on demand
```

This is intentional.

---

# Database

Do NOT create an `emails` table unless there is a clear existing business requirement.

The mail server remains the source of truth.

Application database should not become another copy of the mailbox.

Do not store email body or attachments permanently.

---

# Read-only Requirement

The implementation is strictly read-only.

Do NOT implement:

```text
SMTP
Send
Reply
Forward
Delete email
Move email
Archive email
Mark spam
Modify mailbox folders
```

Avoid operations that mutate the mailbox.

If reading an email through the library automatically marks it as `Seen`, avoid that behavior if technically possible.

Prefer read-only fetching / `peek` semantics.

The website should not unintentionally change mailbox state.

---

# Testing

Add tests appropriate to the existing project.

Do not require a real mail server for normal automated tests.

Mock the `ImapService` for controller/feature tests.

Test at least:

### Inbox

```text
authenticated user can open inbox
unauthenticated user cannot open inbox
pagination works
mail server failure is handled
```

### Detail

```text
authenticated user can open email
invalid UID returns safe 404/error
HTML email is sanitized
plain text fallback works
```

### Attachment

```text
attachment can be downloaded
missing attachment returns 404
dangerous filename is sanitized
Content-Disposition is safe
X-Content-Type-Options is nosniff
unauthenticated access is rejected
```

---

# Logging

Add structured logs for operational debugging.

Example:

```text
mail.imap.connection_failed
mail.imap.authentication_failed
mail.message.fetch_failed
mail.attachment.fetch_failed
```

Include useful identifiers such as:

```text
mailbox
uid
attachment id
```

Do NOT log sensitive email credentials.

Avoid logging email body unless necessary.

---

# Production Checklist

Before finishing, verify:

- [ ] IMAPS uses port 993.
- [ ] TLS certificate validation is enabled.
- [ ] IMAP credentials only exist server-side.
- [ ] `.env.example` is updated.
- [ ] All routes require authentication.
- [ ] Existing authorization is respected.
- [ ] Inbox is paginated.
- [ ] Inbox uses short cache.
- [ ] Cache can be manually refreshed.
- [ ] No database mailbox duplication was introduced.
- [ ] Attachment files are fetched on demand.
- [ ] Attachment filenames are sanitized.
- [ ] MIME types are handled safely.
- [ ] `X-Content-Type-Options: nosniff` is present.
- [ ] HTML email is sanitized.
- [ ] Remote tracking resources are handled safely.
- [ ] IMAP errors are handled gracefully.
- [ ] Credentials are never logged.
- [ ] Application remains read-only toward the mailbox.
- [ ] Automated tests exist.

---

# Implementation Workflow

Perform the work in this order.

## Step 1 — Inspect

Inspect the existing Laravel project:

```text
Laravel version
PHP version
authentication
authorization
frontend framework
route organization
service patterns
testing framework
cache driver
existing dependencies
```

Do not make assumptions when the project already provides a convention.

---

## Step 2 — Plan

Before modifying files, produce a concise implementation plan containing:

```text
files to add
files to modify
dependencies
routes
security decisions
```

Avoid unrelated refactoring.

---

## Step 3 — Implement

Implement:

```text
IMAP configuration
ImapService
EmailController
routes
frontend inbox
email detail
attachment download
cache
sanitization
error handling
```

---

## Step 4 — Test

Run the project's relevant checks.

Examples:

```bash
php artisan test
```

and, where applicable:

```bash
composer test
npm run lint
npm run build
```

Use the commands that actually exist in the project.

Fix errors introduced by the implementation.

Do not silently ignore failing tests.

---

## Step 5 — Review

Review the final implementation specifically for:

```text
security
IMAP connection behavior
read-only behavior
attachment handling
XSS
path traversal
credential leakage
performance
unnecessary complexity
```

---

# Important Constraints

Do not:

- rewrite unrelated code,
- change database schema without a real requirement,
- add SMTP,
- create Kafka infrastructure,
- create mail synchronization workers,
- create mailbox database replicas,
- store attachments permanently,
- expose IMAP credentials,
- disable TLS certificate validation,
- render raw HTML email,
- use message sequence numbers as stable IDs,
- load the entire mailbox on every request.

---

# Desired Final Result

The final system should behave like:

```text
                    ┌───────────────────────┐
                    │ Company Mail Server   │
                    │ IMAPS :993            │
                    └───────────┬───────────┘
                                │
                                │ Secure IMAP
                                │
                    ┌───────────▼───────────┐
                    │ Laravel ImapService   │
                    └───────────┬───────────┘
                                │
                  ┌─────────────┴─────────────┐
                  │                           │
            Inbox / Detail              Attachment
                  │                           │
             Cache 30s                  Fetch on demand
                  │                           │
                  └─────────────┬─────────────┘
                                │
                         Laravel Controller
                                │
                              HTTPS
                                │
                              User
```

Priority order:

```text
1. Security
2. Correctness
3. Simplicity
4. Maintainability
5. Performance
```

The system is for a small internal user base, therefore favor a clean monolithic Laravel implementation over unnecessary infrastructure.
