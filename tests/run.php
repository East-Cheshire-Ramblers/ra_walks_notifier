<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/BookingCsvParser.php';
require_once __DIR__ . '/../src/BookingNotifier.php';

use WalksManagerWatch\BookingCsvParser;
use WalksManagerWatch\BookingNotifier;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . PHP_EOL . 'Expected: ' . var_export($expected, true) . PHP_EOL . 'Actual: ' . var_export($actual, true));
    }
}

function assertContainsText(string $needle, string $haystack, string $message): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($message . PHP_EOL . 'Missing text: ' . $needle);
    }
}

$parser = new BookingCsvParser();
$result = $parser->parseFile(__DIR__ . '/fixtures/bookings.csv');

assertSameValue(7, $result['records_read'], 'The parser should count every CSV row read.');
assertSameValue(
    [
        ['line' => 1, 'reason' => 'header'],
        ['line' => 3, 'reason' => 'comment'],
    ],
    $result['ignored'],
    'The parser should ignore the header row and # comment rows.'
);
assertSameValue(2, count($result['rows']), 'The parser should return only valid data rows.');
assertSameValue(
    [
        'line' => 2,
        'group_code' => 'EC',
        'name' => 'Jane Walker',
        'email' => 'jane@example.org',
        'partner' => '',
    ],
    $result['rows'][0],
    'The parser should preserve group, name, email, and empty partner values.'
);
assertSameValue(
    [
        'line' => 4,
        'group_code' => 'EC',
        'name' => 'John Rambler',
        'email' => 'john@example.org',
        'partner' => 'Ann Rambler',
    ],
    $result['rows'][1],
    'The parser should preserve optional partner values.'
);
assertSameValue(3, count($result['errors']), 'The parser should report one validation error for each invalid fixture row.');
assertSameValue('First column (Group code) is blank', $result['errors'][0]['message'], 'Blank group code should match the v4.2 error.');
assertSameValue('Second column (name) is blank', $result['errors'][1]['message'], 'Blank name should match the v4.2 error.');
assertSameValue('missing-name@example.org', $result['errors'][1]['email'], 'Blank-name errors should include the supplied email.');
assertSameValue('Third column (email) is blank', $result['errors'][2]['message'], 'Blank email should match the v4.2 error.');

$notifier = new BookingNotifier();
$messages = $notifier->renderMessages(
    [
        'title' => 'Macclesfield Forest Walk',
        'organiser' => 'Alex Organiser',
        'organiser_email' => 'organiser@example.org',
        'max_bookings' => 2,
        'booking1' => 'Diet',
        'booking2' => '',
    ],
    [
        'preferred_name' => 'Jane Walker',
        'email' => 'jane@example.org',
        'created' => '2026-06-28 09:15:00',
        'state' => 0,
    ],
    [
        [
            'preferred_name' => 'Jane Walker',
            'status' => 'Provisional',
            'state' => 0,
            'created' => '2026-06-28 09:15:00',
            'num_places' => 2,
            'partner' => 'Ann Walker',
            'special_request' => 'Near front',
            'custom1' => 'Vegetarian',
        ],
        [
            'preferred_name' => 'Sam Hill',
            'status' => 'Confirmed',
            'state' => 1,
            'created' => '2026-06-28 09:20:00',
            'num_places' => 1,
            'partner' => '',
            'special_request' => '',
            'custom1' => '',
        ],
    ],
    true
);

assertSameValue(2, count($messages), 'Organiser notification should be rendered when notify_organiser is enabled.');
assertSameValue('Your booking for Macclesfield Forest Walk', $messages[0]['subject'], 'Booker acknowledgement subject should be preserved.');
assertSameValue('New booking for Macclesfield Forest Walk', $messages[1]['subject'], 'New provisional bookings should notify the organiser.');
assertContainsText('The list of bookings is now:', $messages[1]['body'], 'Organiser email should include the booking list.');
assertContainsText('Provisional places: 2 <b>(Over subscribed!)</b>', $messages[1]['body'], 'Organiser email should flag over-subscription.');

$messages = $notifier->renderMessages(
    [
        'title' => 'Macclesfield Forest Walk',
        'organiser' => 'Alex Organiser',
        'organiser_email' => 'organiser@example.org',
        'max_bookings' => 12,
    ],
    [
        'preferred_name' => 'Jane Walker',
        'email' => 'jane@example.org',
        'created' => '2026-06-28 09:15:00',
        'state' => 1,
    ],
    [],
    false
);

assertSameValue(1, count($messages), 'Only the booker acknowledgement should be rendered when organiser notification is disabled.');

echo "All tests passed.\n";
