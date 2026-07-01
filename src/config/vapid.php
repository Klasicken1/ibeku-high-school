<?php
/* ============================================================
   IBEKU HIGH SCHOOL — VAPID KEYS FOR WEB PUSH
   File: src/config/vapid.php

   SETUP INSTRUCTIONS:
   1. Generate your VAPID key pair once using the helper script
      below, or any VAPID key generator tool:
      https://vapidkeys.com/

   2. Paste the generated keys into the constants below.

   3. Never commit real private keys to a public repository.
      On cPanel, set these via the .env file or directly here
      (this file is outside public/ so it's not web-accessible).

   KEY FORMAT:
   - VAPID_PUBLIC_KEY  → Base64url-encoded uncompressed P-256 public key (87 chars)
   - VAPID_PRIVATE_KEY → Base64url-encoded P-256 private key (43 chars)
   - VAPID_SUBJECT     → mailto: URI for your school email
   ============================================================ */

define('VAPID_PUBLIC_KEY',  getenv('VAPID_PUBLIC_KEY')  ?: 'REPLACE_WITH_YOUR_PUBLIC_KEY');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: 'REPLACE_WITH_YOUR_PRIVATE_KEY');
define('VAPID_SUBJECT',     getenv('VAPID_SUBJECT')     ?: 'mailto:admin@ibekuhighschool.edu.ng');