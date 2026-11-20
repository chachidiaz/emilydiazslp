<?php
/*
 * Handles the "General Contact Form" on /contact/.
 * Sends the message by email, then sends the visitor back to /contact/
 * with ?sent=1 (worked) or ?error=1 (missing fields or mail failure).
 */

// ---------------------------------------------------------------------------
// WHO GETS THE MESSAGES — change this to the inbox that should receive them.
// ---------------------------------------------------------------------------
const CONTACT_RECIPIENT = 'CHANGE-ME@emilydiazslp.com';

// Address the email is sent from. Use an address on this domain so mail
// isn't marked as spam (Hostinger: create it under Emails, or keep no-reply).
const CONTACT_FROM = 'no-reply@emilydiazslp.com';

function back(string $status): void {
    header('Location: /contact/?' . $status . '=1#general-contact-form', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    back('error');
}

// Honeypot: real people never fill this hidden field in. Pretend it worked.
if (trim($_POST['website'] ?? '') !== '') {
    back('sent');
}

function field(string $name, int $max = 500): string {
    $v = trim((string)($_POST[$name] ?? ''));
    $v = str_replace("\0", '', $v);
    return mb_substr($v, 0, $max);
}

$first   = field('first_name', 100);
$last    = field('last_name', 100);
$email   = field('email', 200);
$topic   = field('topic', 200);
$other   = field('topic_other', 300);
$message = field('message', 10000);

if ($topic === 'Other' && $other !== '' && $other !== 'Other') {
    $topic = 'Other: ' . $other;
}

if ($first === '' || $last === '' || $topic === '' || $message === ''
    || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    back('error');
}

// Strip line breaks from anything that ends up in a header.
$clean = fn(string $s): string => preg_replace('/[\r\n]+/', ' ', $s);

$subject = 'Website contact form: ' . $clean($topic);
$body = "New message from the contact form on emilydiazslp.com\n\n"
      . "Name: {$first} {$last}\n"
      . "Email: {$email}\n"
      . "This email is about: {$topic}\n\n"
      . "Message:\n{$message}\n";

$headers = [
    'From: Emily Diaz SLP website <' . CONTACT_FROM . '>',
    'Reply-To: ' . $clean("{$first} {$last}") . ' <' . $clean($email) . '>',
    'Content-Type: text/plain; charset=UTF-8',
];

$ok = mail(CONTACT_RECIPIENT, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

back($ok ? 'sent' : 'error');
