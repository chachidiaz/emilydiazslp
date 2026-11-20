<?php
/*
 * Handles the website's forms and sends each one by email:
 *  - the "General Contact Form" on /contact/ (no `form` field), and
 *  - the inquiry forms listed in INQUIRY_FORMS below (they post a hidden
 *    `form` field with their key).
 * Afterwards the visitor goes back to the form's page with ?sent=1 (worked)
 * or ?error=1 (missing fields or mail failure).
 */

// ---------------------------------------------------------------------------
// WHO GETS THE MESSAGES — change this to the inbox that should receive them.
// ---------------------------------------------------------------------------
const CONTACT_RECIPIENT = 'hello@emilydiazslp.com';

// Address the email is sent from. Use an address on this domain so mail
// isn't marked as spam (Hostinger: create it under Emails, or keep no-reply).
const CONTACT_FROM = 'no-reply@emilydiazslp.com';

// Inquiry forms on the service pages. `fields` lists every field in the order
// it appears in the email (POST name => label); `required` must be filled in.
const INQUIRY_FORMS = [
    'aac-evaluation' => [
        'subject'  => 'AAC Evaluation Inquiry',
        'page'     => '/evals/',
        'fields'   => [
            'client_first' => "Client's first name", 'client_last' => "Client's last name",
            'client_dob' => "Client's DOB",
            'parent_first' => "Parent/Guardian's first name", 'parent_last' => "Parent/Guardian's last name",
            'email' => 'Email', 'phone' => 'Phone', 'location' => 'Location',
            'details' => "What they're looking for",
        ],
        'required' => ['client_first', 'client_last', 'client_dob', 'parent_first', 'parent_last', 'email', 'location', 'details'],
    ],
    'speech-therapy' => [
        'subject'  => 'Speech Therapy Inquiry',
        'page'     => '/therapy-2/',
        'fields'   => [
            'client_first' => "Client's first name", 'client_last' => "Client's last name",
            'client_dob' => "Client's DOB",
            'parent_first' => "Parent/Guardian's first name", 'parent_last' => "Parent/Guardian's last name",
            'email' => 'Email', 'phone' => 'Phone', 'location' => 'Location',
            'details' => "What they're looking for",
        ],
        'required' => ['client_first', 'client_last', 'client_dob', 'parent_first', 'parent_last', 'email', 'location', 'details'],
    ],
    'school-contracts' => [
        'subject'  => 'School Contracts Inquiry',
        'page'     => '/schoolcontracts/',
        'fields'   => [
            'name_first' => 'First name', 'name_last' => 'Last name',
            'email' => 'Email', 'phone' => 'Phone',
            'district' => 'School district / organization',
            'interests' => "Would like more information about",
        ],
        'required' => ['email'],
    ],
];

// Simpler forms that post first_name, last_name, email, and a `message` the page
// has already put together (coaching, speaking). `anchor` is where the visitor lands afterwards.
const SIMPLE_FORMS = [
    'coaching'       => ['subject' => '1:1 Coaching & Consult Inquiry', 'page' => '/coaching/', 'anchor' => 'inquiry-form'],
    'speaking'       => ['subject' => 'Speaking Request Inquiry', 'page' => '/speaking-2/', 'anchor' => 'request-form'],
];

$formKey = (string)($_POST['form'] ?? '');
$inquiry = INQUIRY_FORMS[$formKey] ?? null;
$simple  = SIMPLE_FORMS[$formKey] ?? null;

function back(string $status): void {
    global $inquiry, $simple;
    // The folder this script lives in ("" at the domain root, "/new-site" in the preview copy).
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $to = $base . ($inquiry ? $inquiry['page'] . '?' . $status . '=1#inquiry-form'
        : ($simple ? $simple['page'] . '?' . $status . '=1#' . $simple['anchor']
                   : '/contact/?' . $status . '=1#general-contact-form'));
    header('Location: ' . $to, true, 303);
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

// Strip line breaks from anything that ends up in a header.
$clean = fn(string $s): string => preg_replace('/[\r\n]+/', ' ', $s);

if ($inquiry) {
    $lines = [];
    $values = [];
    foreach ($inquiry['fields'] as $name => $label) {
        $raw = $_POST[$name] ?? '';
        $v = is_array($raw)
            ? implode(', ', array_map(fn($x) => mb_substr(trim((string)$x), 0, 200), $raw))
            : field($name, $name === 'details' ? 10000 : 300);
        $values[$name] = $v;
        $lines[] = $name === 'details' ? "\n{$label}:\n{$v}" : "{$label}: {$v}";
    }
    foreach ($inquiry['required'] as $name) {
        if (($values[$name] ?? '') === '') back('error');
    }
    $email = $values['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('error');

    $who = trim(($values['parent_first'] ?? $values['name_first'] ?? '') . ' ' . ($values['parent_last'] ?? $values['name_last'] ?? ''));
    $subject = 'Website ' . $inquiry['subject'] . ($who !== '' ? ': ' . $clean($who) : '');
    $body = "New {$inquiry['subject']} from emilydiazslp.com{$inquiry['page']}\n\n" . implode("\n", $lines) . "\n";
    $headers = [
        'From: Emily Diaz SLP website <' . CONTACT_FROM . '>',
        'Reply-To: ' . ($who !== '' ? $clean($who) . ' ' : '') . '<' . $clean($email) . '>',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $ok = mail(CONTACT_RECIPIENT, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    back($ok ? 'sent' : 'error');
}

if ($simple) {
    $first = field('first_name', 100);
    $last  = field('last_name', 100);
    $email = field('email', 200);
    $msg   = field('message', 10000);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('error');
    $who = trim("{$first} {$last}");
    $subject = 'Website ' . $simple['subject'] . ($who !== '' ? ': ' . $clean($who) : '');
    $body = "New {$simple['subject']} from emilydiazslp.com{$simple['page']}\n\n"
          . "Name: {$who}\nEmail: {$email}\n" . ($msg !== '' ? "\n{$msg}\n" : '');
    $headers = [
        'From: Emily Diaz SLP website <' . CONTACT_FROM . '>',
        'Reply-To: ' . ($who !== '' ? $clean($who) . ' ' : '') . '<' . $clean($email) . '>',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $ok = mail(CONTACT_RECIPIENT, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    back($ok ? 'sent' : 'error');
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
