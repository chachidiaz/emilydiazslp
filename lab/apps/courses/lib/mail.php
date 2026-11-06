<?php
// Outgoing email. On Hostinger PHP mail() sends from the domain. Set mail_mode to "log"
// (local preview) to write messages to data/mail.log instead of sending them.

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function send_mail(string $to, string $subject, string $body): bool
{
    $from = (string) config('mail_from', 'hello@emilydiazslp.com');
    $fromName = 'Emily Diaz, SLP';
    $body .= "\n\n— Emily\nLearn with Emily Diaz, SLP\n";

    if (config('mail_mode', 'mail') === 'log') {
        $line = sprintf("=== %s\nTo: %s\nSubject: %s\n\n%s\n\n", gmdate('c'), $to, $subject, $body);
        return file_put_contents(dirname(__DIR__) . '/data/mail.log', $line, FILE_APPEND | LOCK_EX) !== false;
    }

    $headers = [
        'From' => mb_encode_mimeheader($fromName) . " <$from>",
        'Reply-To' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Content-Transfer-Encoding' => '8bit',
    ];
    return mail($to, mb_encode_mimeheader($subject), $body, $headers, '-f' . $from);
}

function mail_reset(array $user, string $link): void
{
    send_mail($user['email'], 'Reset your password', "Hi " . explode(' ', $user['name'])[0] . ",\n\n"
        . "Someone asked to reset the password for your Learn with Emily Diaz, SLP account. "
        . "To choose a new one, open this link within the next hour:\n\n$link\n\n"
        . "If you didn't ask for this, you can ignore this email. Your password stays the same.");
}

function mail_magic(array $user, string $link): void
{
    send_mail($user['email'], 'Your sign-in link', "Hi " . explode(' ', $user['name'])[0] . ",\n\n"
        . "Here's your link to sign in. It works once, for the next 15 minutes:\n\n$link\n\n"
        . "If you didn't ask for this, you can ignore this email.");
}

function mail_no_account(string $email, string $signupLink): void
{
    send_mail($email, 'Your sign-in link', "Hi,\n\n"
        . "Someone asked for a sign-in link for this email, but there's no account with it yet. "
        . "If it was you, you can create one here:\n\n$signupLink\n\n"
        . "If you didn't ask for this, you can ignore this email.");
}
