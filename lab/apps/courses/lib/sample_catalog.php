<?php
// SAMPLE catalog copied from the design canvas: generic placeholder courses, not Emily's real
// catalog. Runs once (migration 3) so the screens have something to show. Replace before launch.

declare(strict_types=1);

function seed_sample_catalog(PDO $db): void
{
    if ((int) $db->query('SELECT COUNT(*) FROM courses')->fetchColumn() > 0) {
        return;
    }
    $now = gmdate('Y-m-d H:i:s');
    $courses = [
        ['watercolor-foundations', 'Watercolor Foundations', 'Art', 'Mara Ellison', 12900, '#EBDCC8',
            '<svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><circle cx="120" cy="100" r="68" fill="#D98B6A" opacity=".75"/><circle cx="196" cy="92" r="58" fill="#7FA3B8" opacity=".65"/><circle cx="158" cy="142" r="48" fill="#E8C766" opacity=".7"/></svg>',
            [
                ['Getting started', 'Your materials', 8], ['Getting started', 'Paper and brushes', 10], ['Getting started', 'Color basics', 14],
                ['Washes', 'Wet-on-wet washes', 12], ['Washes', 'Flat and graded washes', 11], ['Washes', 'Blending soft edges', 13],
                ['Putting it together', 'Simple landscapes', 18], ['Putting it together', 'Final project', 22],
            ]],
        ['intro-to-spreadsheets', 'Intro to Spreadsheets', 'Work skills', 'Dev Anand', 7900, '#DCE6DA',
            '<svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect x="70" y="40" width="180" height="120" rx="10" fill="#FFFFFF" opacity=".85"/><rect x="70" y="40" width="180" height="26" rx="10" fill="#5E8F6E"/><path d="M130 66v94M190 66v94M70 100h180M70 130h180" stroke="#B9CDBD" stroke-width="2"/><rect x="134" y="104" width="52" height="22" fill="#CFE3D3"/></svg>',
            10],
        ['sourdough-from-scratch', 'Sourdough from Scratch', 'Food', 'Hana Whitlock', 8900, '#F3DFCB',
            '<svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><ellipse cx="160" cy="118" rx="92" ry="62" fill="#C98F5A"/><path d="M104 96q56-34 112 0" stroke="#F3E3CF" stroke-width="6" fill="none" stroke-linecap="round"/><path d="M122 122q38-18 76 0" stroke="#F3E3CF" stroke-width="6" fill="none" stroke-linecap="round"/></svg>',
            12],
        ['photography-natural-light', 'Photography: Natural Light', 'Creative', 'Theo Marsh', 9900, '#DDE3EC',
            '<svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><circle cx="160" cy="100" r="74" fill="none" stroke="#6F86A8" stroke-width="10"/><circle cx="160" cy="100" r="46" fill="none" stroke="#9DB0CB" stroke-width="10"/><circle cx="160" cy="100" r="18" fill="#3F5880"/></svg>',
            9],
        ['spanish-for-travelers', 'Spanish for Travelers', 'Languages', 'Lucía Ferrer', 6900, '#EEDDE0',
            '<svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect x="62" y="44" width="130" height="76" rx="20" fill="#C9788A"/><path d="M92 120l-6 26 28-26z" fill="#C9788A"/><rect x="132" y="86" width="126" height="70" rx="20" fill="#F8EEF0"/><path d="M228 156l8 24-30-24z" fill="#F8EEF0"/></svg>',
            20],
        ['personal-finance-basics', 'Personal Finance Basics', 'Money', 'Nate Okafor', 5900, '#E9E4C8',
            '<svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><rect x="76" y="108" width="34" height="52" rx="6" fill="#B5A24E"/><rect x="126" y="84" width="34" height="76" rx="6" fill="#9C8A36"/><rect x="176" y="58" width="34" height="102" rx="6" fill="#7E6F25"/><rect x="226" y="36" width="34" height="124" rx="6" fill="#5D5118"/></svg>',
            11],
    ];

    $insC = $db->prepare('INSERT INTO courses (slug, title, category, instructor, summary, cover_bg, cover_art, price_cents, sort, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insL = $db->prepare('INSERT INTO lessons (course_id, section, position, title, minutes, description, practice) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $insD = $db->prepare('INSERT INTO downloads (course_id, lesson_id, label, file_type, size_label, sort) VALUES (?, ?, ?, ?, ?, ?)');

    foreach ($courses as $i => [$slug, $title, $cat, $who, $price, $bg, $art, $lessons]) {
        $insC->execute([$slug, $title, $cat, $who, "Sample course. Replace with a real description.", $bg, $art, $price, $i, $now]);
        $courseId = (int) $db->lastInsertId();
        if (is_int($lessons)) {
            $lessons = array_map(fn($n) => ['Lessons', 'Lesson ' . $n, 10], range(1, $lessons));
        }
        foreach ($lessons as $n => [$section, $lessonTitle, $minutes]) {
            $desc = 'Sample lesson description. Replace with what this lesson covers and why it matters.';
            $practice = "First thing you'll practice\nSecond thing you'll practice\nThird thing you'll practice";
            if ($lessonTitle === 'Wet-on-wet washes') {
                $desc = 'Wet-on-wet is the technique behind soft skies, glowing backgrounds and blooming color. In this lesson Mara shows how to wet your paper evenly, load the brush, and let pigment travel on its own, so you spend less time fighting the paint and more time guiding it.';
                $practice = "Judging when the paper is wet enough, and when it is too wet\nDropping in a second color and letting it bleed\nTilting the board to guide a wash";
            }
            $insL->execute([$courseId, $section, $n + 1, $lessonTitle, $minutes, $desc, $practice]);
            $lessonId = (int) $db->lastInsertId();
            if ($slug === 'watercolor-foundations' && $n === 3) {
                foreach ([['Lesson 4 practice sheet', 'PDF', '1.2 MB'], ['Color mixing chart', 'PDF', '640 KB'], ['Supply list', 'DOC', '88 KB'], ['Reference photos', 'ZIP', '18 MB']] as $k => [$label, $type, $size]) {
                    $insD->execute([$courseId, $lessonId, $label, $type, $size, $k]);
                }
            }
        }
    }
}
