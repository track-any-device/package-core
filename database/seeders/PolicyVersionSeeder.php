<?php

namespace TrackAnyDevice\Core\Database\Seeders;

use TrackAnyDevice\Core\Models\PolicyVersion;
use Illuminate\Database\Seeder;

/**
 * Seeds the v1.0.0 content of all three policy documents.
 *
 * Safe to re-run: uses updateOrCreate keyed by (type, version), so it
 * never duplicates a row. This seeder is for v1.0.0 only — any new
 * compliance change ships as a separate dated seeder
 * (PolicyVersionSeeder_YYYY_MM_DD) that calls setCurrent() and must
 * NOT modify existing rows.
 *
 * Tone: friendly and user-respectful. Substance: standard platform
 * protections (limitation of liability, data ownership, indemnity,
 * change rights). Replace contact details / company name with the
 * real operating entity before production launch.
 */
class PolicyVersionSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->toDateString();

        $defaults = [
            PolicyVersion::TYPE_TERMS => [
                'version' => '1.0.0',
                'title' => 'Terms of Service',
                'content' => self::terms(),
            ],
            PolicyVersion::TYPE_PRIVACY => [
                'version' => '1.0.0',
                'title' => 'Privacy Policy',
                'content' => self::privacy(),
            ],
            PolicyVersion::TYPE_COOKIE => [
                'version' => '1.0.0',
                'title' => 'Cookie Policy',
                'content' => self::cookies(),
            ],
        ];

        foreach ($defaults as $type => $data) {
            $row = PolicyVersion::updateOrCreate(
                ['type' => $type, 'version' => $data['version']],
                [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'effective_from' => $today,
                    'is_current' => true,
                ],
            );

            $row->setCurrent();
        }
    }

    private static function terms(): string
    {
        return <<<'MD'
# Terms of Service

Welcome — we're glad you're here. These terms explain what you can
expect from **Track Any Device** ("the Platform", "we", "us") and what
we expect from you ("you", "your"). We've kept the language as
friendly as we could without losing the parts that protect everyone.

By creating an account, signing in, buying hardware, or using any
part of the Platform, you agree to everything below.

## 1. Who we are

Track Any Device is a multi-tenant IoT device platform operated from
Pakistan. We import and sell tracking hardware, provide the TAD101
protocol for custom builds, and host the tenant portal at
`{tenant}.track-any-device.com`.

## 2. Your account

- You must be 18 or older, or have permission from a parent or guardian.
- Please keep your password and any API tokens safe — anyone who has
  them can act as you.
- Tell us right away if you think someone else has accessed your account.
- One human per account, please. Sharing logins makes audit logs misleading.

## 3. Buying hardware

- Prices are shown on the product page in your local currency where
  available. Taxes and shipping are added at checkout.
- Orders are confirmed when we ship the hardware, not when you place
  the order. We may cancel an order before shipping if stock has run
  out or pricing was clearly wrong, and we'll refund you in full.
- Returns: contact us within **7 days** of delivery for unused
  hardware in original packaging. Defective units are covered by the
  manufacturer warranty for 12 months from delivery.

## 4. Your data and devices

- You own the data your devices produce. Telemetry, locations,
  incidents, beats — it's all yours.
- We store it so the Platform can show it back to you and run the
  alerts you've configured.
- You can export your data at any time via the Tenant API or by
  contacting support. We'll give you a portable archive within 30
  days of a written request.

## 5. Using the Platform fairly

You agree not to:

- Probe, scan, or test the security of any part of the Platform you
  don't own, unless we've explicitly agreed in writing.
- Resell access to the Platform without becoming an authorised
  reseller. Reach out — we have a partner programme.
- Use the Platform to track people who haven't consented to being
  tracked. This is a hard line; misuse will close your account.
- Send harmful traffic, attempt to overload the Platform, or
  reverse-engineer billed components.

## 6. Tenant subdomains and operator access

If you operate a tenant (organisation) on the Platform:

- You are responsible for users you invite into your tenant.
- You will not use the Platform to violate local labour, privacy, or
  data-protection law in your jurisdiction.
- You will keep your administrator accounts secure with the
  Platform's SMS 2FA, and remove access promptly when staff leave.

## 7. Service availability

We aim for 99.9% monthly uptime but cannot guarantee it. Planned
maintenance is announced through the in-app banner; emergency
maintenance may be unannounced. Critical SOS-incident broadcasts
flow through redundant paths but no system is infallible — you
should not treat the Platform as a substitute for emergency
services.

## 8. Payment, fees, and changes

- Subscription fees, if any, are billed in advance and are
  non-refundable except where required by law.
- Hardware is one-time pricing as shown on the product page.
- We may change pricing with **30 days** written notice via email or
  the in-app banner. Your existing fixed-term agreement, if any,
  honours the original price until renewal.

## 9. Intellectual property

- The Platform, the TAD101 protocol implementation, the source code,
  the design system, the documentation, and any brand assets belong
  to Track Any Device.
- Your tenant logo, business name, and brand assets remain yours;
  you grant us a limited licence to display them inside your tenant
  portal so the Platform can render your branding.
- Feedback you give us is appreciated and may be used to improve
  the Platform without obligation to credit or compensate you.

## 10. Liability and warranty

We work hard to keep the Platform running and the hardware reliable.
That said:

- The Platform is provided **as is** and **as available**. We
  disclaim implied warranties to the maximum extent allowed by law.
- Our total liability to you for any claim related to the Platform
  is capped at the greater of:
  - the fees you paid us in the **12 months** before the claim, or
  - **PKR 25,000** (or equivalent in your billing currency).
- We are not liable for indirect, consequential, or punitive
  damages — for example lost profits, lost data, or downstream
  business impact — even if we were told it was possible.

Nothing in these terms limits anything that cannot be limited
under applicable law.

## 11. Indemnity

You will defend and hold us harmless against claims arising from
your misuse of the Platform, your content, your violation of these
terms, or your violation of someone else's rights. We will tell you
about any such claim promptly and let you handle the defence at your
expense.

## 12. Termination

- You can close your account any time from `/settings/profile` or
  by emailing us.
- We can suspend or close accounts that materially breach these
  terms after a reasonable notice and an opportunity to fix the
  issue. Repeated or egregious abuse may result in immediate
  suspension.
- After termination we will keep your data for **90 days** so you
  can re-activate, then we'll permanently delete it.

## 13. Changes to these terms

We may update these terms when the Platform evolves. Material
changes are announced via email and via an in-app banner with at
least **14 days** of notice before they take effect. Continuing to
use the Platform after a change means you accept the new terms.

## 14. Governing law and disputes

These terms are governed by the laws of Pakistan. Disputes are
handled in the courts of Lahore, Pakistan — except that we may
seek injunctive relief in any competent court to protect our
intellectual property or stop abuse.

## 15. Contact

Questions, refund requests, or compliance concerns: please email
**hello@track-any-device.com** or write to our registered address.

Thanks for using the Platform — we'll do our best to be worthy of
your trust.
MD;
    }

    private static function privacy(): string
    {
        return <<<'MD'
# Privacy Policy

Your trust matters to us. This policy explains what data we collect,
why we collect it, and the choices you have. Plain English first;
the legal precision is woven in where it counts.

## 1. The short version

- We collect only what's needed to run the Platform and the
  features you've turned on.
- We never sell your personal data. Full stop.
- We share data with vendors only to the extent strictly needed to
  deliver the service.
- You can ask us to export or delete your data at any time.

## 2. Who is the data controller

Track Any Device is the data controller for personal data you give
us through your account, your orders, and any device you register
to your name. If you operate a tenant (organisation), **you** are
the data controller for the assignees and end users under your
tenant; we are the data processor on your behalf for that data.

## 3. What we collect

### When you sign up

- Name, email address, phone number
- Hashed password and 2FA recovery codes
- Country and a coarse IP-derived location for fraud prevention

### When you buy hardware

- Shipping and billing address
- Order history
- Payment metadata (we never see your full card number — that lives
  with our payment processor)

### When you use a device

- IMEI and SIM identifiers of devices you register
- GPS coordinates, battery level, GSM signal, and any sensor
  readings the device sends
- Incident records produced by your alert rules
- Telemetry timestamps and the network path used to receive them

### When you use the website

- Pages you visit, links you click, and approximate timings
- Browser type, OS, and screen size — for layout decisions
- A cookie identifier so we can keep you signed in (see the Cookie
  Policy for the full list)

### When you contact us

- Whatever you choose to send us — please don't include sensitive
  data we don't need

## 4. Why we collect it

| Purpose | Lawful basis |
|---|---|
| Operating your account and the Platform | Contract |
| Sending you SMS 2FA codes | Contract / your security |
| Calculating shipping and tax on orders | Contract |
| Detecting fraud and abuse | Legitimate interests |
| Telling you about service updates | Legitimate interests |
| Sending optional marketing | Your consent (opt-in only) |
| Complying with tax, accounting, and other laws | Legal obligation |

## 5. Who sees it

We share data only with:

- **Cloud infrastructure** providers who host the Platform — they
  process data on our behalf under written agreements.
- **Payment processors** for orders you place — they receive only
  the information needed to complete the transaction.
- **SMS gateways** that deliver 2FA and alert messages.
- **Government authorities** when we are legally required to
  respond to a valid request. We push back on overbroad requests.

Inside a tenant, your operators and supervisors see the data you'd
expect them to see for their role. Beat-scoped staff see only the
beats they belong to. SIM and GSM numbers stay admin-only.

## 6. Where data lives

Production data is hosted in cloud regions chosen for proximity to
our customer base. We may transfer data across borders when our
infrastructure providers replicate it for redundancy; in those
cases we use the same protections (encryption at rest, access
control, contractual safeguards) regardless of location.

## 7. How long we keep it

| Data type | Retention |
|---|---|
| Account profile | Until you close your account + 90 days |
| Device telemetry & locations | 24 months by default (tenant-configurable) |
| Incident history | 3 years (operations + compliance audit trail) |
| Order records & invoices | 7 years (tax law) |
| SMS 2FA codes | 15 minutes |
| Session cookies | Until you sign out or 14 days, whichever comes first |

Backups follow the same retention policy with a 30-day overlap so
recent activity can be restored if something breaks.

## 8. Your rights

You can:

- **Access** the data we hold about you.
- **Correct** anything that's wrong or out of date.
- **Export** your data in a portable format (we'll deliver an
  archive within 30 days of a written request).
- **Delete** your account and personal data. We keep the minimum
  required by law (invoices, security logs) for the retention
  windows above.
- **Object** to specific processing (e.g. marketing) and we'll
  stop.
- **Lodge a complaint** with a privacy regulator if you think we
  got it wrong. We'd prefer you tell us first so we can fix it.

To exercise any of these rights, email **privacy@track-any-device.com**.
We'll verify your identity and respond within 30 days.

## 9. Children

The Platform isn't designed for children under 13. We don't
knowingly collect data from them. If you believe we have, contact
us and we'll delete it.

## 10. Security

We protect your data with:

- HTTPS / TLS in transit
- Encryption at rest for personal data
- SMS 2FA on every login
- Beat-scoped access controls inside tenants
- Logged audit trails on incident actions and tenant settings
- Regular dependency and security reviews

No system is perfectly secure. We'll tell you and the relevant
regulator about any breach within 72 hours of discovery, in line
with applicable law.

## 11. Cookies

See the dedicated [Cookie Policy](/cookies) for the full list and
how to control them.

## 12. Changes

We may update this policy. Material changes get an email and an
in-app banner at least 14 days before they take effect. The
version history lives at `/privacy/history`.

## 13. Contact

Privacy questions, requests, or concerns:
**privacy@track-any-device.com**

Thanks for trusting us with your data — we don't take it lightly.
MD;
    }

    private static function cookies(): string
    {
        return <<<'MD'
# Cookie Policy

This page explains the cookies and similar technologies the
Platform uses, why we use them, and how you can control them.

## What is a cookie?

A cookie is a small piece of text a website stores in your browser
so it can remember things between page loads. We use cookies, plus
two browser storage mechanisms (localStorage, sessionStorage), for
the purposes below.

## The cookies we set

| Name | Purpose | Type | Lifetime |
|---|---|---|---|
| `XSRF-TOKEN` | Prevents cross-site request forgery on every form post | Strictly necessary | Session |
| `track_any_device_session` | Keeps you signed in across page loads | Strictly necessary | 2 hours of inactivity, 14 days max |
| `sidebar_state` | Remembers whether your portal sidebar is collapsed | Preference | 1 year |
| `appearance` | Stores your light/dark theme choice | Preference | 1 year |
| `cookie_banner_dismissed` | Hides the consent banner after you've answered it | Preference | 1 year |
| `applied_theme` | Tenant accent override for branded preview links | Preference | 1 year |

### Browser storage we use

- `localStorage.theme` — same as the `appearance` cookie, mirrored
  for the React app to read without an HTTP round-trip.
- `localStorage.last_tenant` — speeds up the tenant picker on next
  login.

## Cookies we don't set

We don't use cross-site advertising cookies, behavioural ad
trackers, or third-party analytics that profile you across the
web. If we add any of those later, it'll be opt-in and announced
through the in-app banner first.

## Your choices

- **Strictly necessary cookies** — these keep the Platform working.
  Blocking them will sign you out and stop most forms from
  submitting. We can't disable them and there's no consent
  required under most privacy laws.
- **Preference cookies** — you can clear them from your browser
  settings. We'll simply forget your last theme or sidebar state
  on the next visit.

You can clear cookies via your browser:

- **Chrome**: Settings → Privacy and security → Cookies and other site data
- **Safari**: Preferences → Privacy → Manage Website Data
- **Firefox**: Settings → Privacy & Security → Cookies and Site Data

## Changes

If we add new cookies or change a purpose, we update this page and
show the consent banner again. The version history lives at
`/cookies/history`.

## Contact

Cookie questions: **privacy@track-any-device.com**

Thanks for reading. We try to be honest about what runs in your
browser.
MD;
    }
}
