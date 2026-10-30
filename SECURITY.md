# Security Policy

## Reporting a vulnerability

Email **mailchimp@help.ebizmarts.com** with `security` in the subject. Please do
not open a public issue for a vulnerability: this repository is public, and an
issue is visible long before there is a release to install.

What helps, in rough order of usefulness:

- the extension version and the Magento version
- the file and line, or a request that shows the behaviour
- what an attacker gains, and what they need to know or already hold to get it

## What happens next

We reply to confirm we received the report, and again once we know whether we
can reproduce it. A fix lands on every supported line at the same time, because
this extension is maintained on several, and the release notes say which
versions carry it.

We work to coordinated disclosure. Tell us when you intend to publish and we
will tell you whether the fix will be out by then; if we need longer we will say
so, and why, rather than let a date pass quietly. Reporters are credited in the
release notes under whatever name they ask for, or not at all if they prefer.

## Supported lines

Fixes are released for the lines that still receive releases. A report against a
version older than those will still be assessed, but the fix ships on the
supported lines.
