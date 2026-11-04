<?php
// Short surveys for lab.emilydiazslp.com: the question list, storage and validation.

declare(strict_types=1);

// Every survey the lab accepts answers for. A survey page posts its slug plus one field per key.
// multi = pick any, single = pick one, text = short written answer.
const SV_SURVEYS = [
    'call-times' => [
        'title' => 'Call times survey',
        'page' => '/landing-pages/call-times-survey/',
        'questions' => [
            'days' => [
                'label' => 'Which days work best for you?',
                'type' => 'multi',
                'options' => ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday'],
            ],
            'times' => [
                'label' => 'What time of day? (Central Time)',
                'type' => 'multi',
                'options' => ['morning' => 'Mornings (9–11am)', 'lunch' => 'Lunch break (12–2pm)', 'after-school' => 'Right after school (3:30–5pm)', 'evening' => 'Evening (6–7:30pm)'],
            ],
            'situation' => [
                'label' => 'What\'s one AAC eval situation you\'d love help with on a call?',
                'type' => 'text',
            ],
            'bonus' => [
                'label' => 'Interested in a bonus call on starting an AAC-niched business/practice?',
                'type' => 'single',
                'options' => ['yes' => 'Yes', 'maybe' => 'Maybe', 'no' => 'No'],
            ],
        ],
    ],
];

const SV_TEXT_MAX = 2000;

function sv_db(): PDO
{
    static $db = null;
    if ($db) {
        return $db;
    }
    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $db = new PDO('sqlite:' . $dir . '/surveys.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS responses (
  id INTEGER PRIMARY KEY,
  survey TEXT NOT NULL,
  email TEXT,                 -- from the Flodesk link; NULL when the link had none
  answers TEXT NOT NULL,      -- JSON: {question key: string | string[]}
  created_at INTEGER NOT NULL,
  updated_at INTEGER NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS responses_email ON responses (survey, email) WHERE email IS NOT NULL;
CREATE INDEX IF NOT EXISTS responses_survey ON responses (survey, updated_at);
SQL);
    return $db;
}

// Cleans one submission against the survey's questions. Returns [answers, error message or null].
function sv_clean(array $survey, array $input): array
{
    $answers = [];
    foreach ($survey['questions'] as $key => $q) {
        $v = $input[$key] ?? null;
        if ($q['type'] === 'text') {
            $v = is_string($v) ? trim(str_replace("\r\n", "\n", $v)) : '';
            $answers[$key] = mb_substr($v, 0, SV_TEXT_MAX);
        } elseif ($q['type'] === 'multi') {
            $v = is_array($v) ? $v : [];
            $answers[$key] = array_values(array_intersect(array_keys($q['options']), $v));
        } else {
            $answers[$key] = is_string($v) && isset($q['options'][$v]) ? $v : '';
        }
    }
    $filled = array_filter($answers, fn($a) => $a !== '' && $a !== []);
    return [$answers, $filled ? null : 'Answer at least one question first.'];
}

// One person answering twice (same email) replaces their earlier answers.
function sv_save(string $slug, ?string $email, array $answers): void
{
    $db = sv_db();
    $now = time();
    $json = json_encode($answers, JSON_UNESCAPED_UNICODE);
    if ($email !== null) {
        $st = $db->prepare('UPDATE responses SET answers = ?, updated_at = ? WHERE survey = ? AND email = ?');
        $st->execute([$json, $now, $slug, $email]);
        if ($st->rowCount()) {
            return;
        }
    }
    $db->prepare('INSERT INTO responses (survey, email, answers, created_at, updated_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$slug, $email, $json, $now, $now]);
}

function sv_email(mixed $v): ?string
{
    $v = is_string($v) ? strtolower(trim($v)) : '';
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? mb_substr($v, 0, 254) : null;
}

function sv_responses(string $slug): array
{
    $st = sv_db()->prepare('SELECT * FROM responses WHERE survey = ? ORDER BY updated_at DESC');
    $st->execute([$slug]);
    return array_map(function ($r) {
        $r['answers'] = json_decode($r['answers'], true) ?: [];
        return $r;
    }, $st->fetchAll());
}
