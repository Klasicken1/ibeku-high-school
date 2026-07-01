<?php
/* ============================================================
   IBEKU HIGH SCHOOL — VAPID KEYS FOR WEB PUSH
   File: src/config/vapid.php

   These keys identify this server when sending Web Push
   notifications. Generated once and never changed (changing
   them invalidates all existing browser subscriptions).

   SECURITY:
   - This file lives outside public/ — not web-accessible.
   - Do NOT commit this file to a public repository with real
     keys. Add src/config/vapid.php to .gitignore if this
     repo ever becomes public.
   - On cPanel production, you can override these via .env
     environment variables (VAPID_PUBLIC_KEY, etc.) if your
     host supports it. Otherwise the constants below are used.
   ============================================================ */

define('VAPID_PUBLIC_KEY',
    getenv('VAPID_PUBLIC_KEY') ?:
    'BHsWzywBD6IC7Jp8bL8bHQvB1GzyRPM56r6pXtwV-zFKmv6Bf8m-QbwRVGBsveemOibzry1VFRMfhhKDb9R2dCg'
);

define('VAPID_PRIVATE_KEY',
    getenv('VAPID_PRIVATE_KEY') ?:
    '4Wfe6UT7_mdzqejnV1bhJYy37ihvRYxTIHacGvIpJlM'
);

define('VAPID_SUBJECT',
    getenv('VAPID_SUBJECT') ?:
    'mailto:me.klasicken@gmail.com'
);