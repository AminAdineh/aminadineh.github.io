<?php

header('Content-Type: application/json');

$diaryFile = __DIR__ . '/../notes_diary.txt';
$entries = array();
$errors = array();
$legacyBuffer = '';

if (file_exists($diaryFile) && is_readable($diaryFile)) {
    $content = file_get_contents($diaryFile);

    if ($content !== false) {
        $lines = preg_split("/(\r\n|\n|\r)/", $content);

        if ($lines !== false) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if ($line[0] === '{' && substr($line, -1) === '}') {
                    $decoded = json_decode($line, true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
                        $timestamp = isset($decoded['timestamp']) ? $decoded['timestamp'] : null;
                        $dateObject = $timestamp ? date_create($timestamp) : false;

                        $entries[] = array(
                            'timestamp'       => $timestamp,
                            'date_label'      => $dateObject ? $dateObject->format('F j, Y') : 'Date unavailable',
                            'time_label'      => $dateObject ? $dateObject->format('H:i') : '',
                            'name'            => isset($decoded['name']) ? $decoded['name'] : '',
                            'subject'         => isset($decoded['subject']) ? $decoded['subject'] : '',
                            'message'         => isset($decoded['message']) ? $decoded['message'] : '',
                            'attachment'      => isset($decoded['attachment']) ? $decoded['attachment'] : null,
                            'attachment_url'  => !empty($decoded['attachment']) ? ('uploads/diary/' . basename($decoded['attachment'])) : null,
                            'attachment_name' => !empty($decoded['attachment']) ? basename($decoded['attachment']) : null,
                        );
                        continue;
                    }

                    $errors[] = array(
                        'line'   => $line,
                        'reason' => json_last_error_msg()
                    );
                }

                // Collect legacy-formatted segments for secondary parsing.
                $legacyBuffer .= $line . PHP_EOL;
            }
        }

        if ($legacyBuffer !== '') {
            $legacyBlocks = preg_split('/-{10,}\R?/', $legacyBuffer);

            foreach ($legacyBlocks as $block) {
                $block = trim($block);
                if ($block === '') {
                    continue;
                }

                $lines = preg_split("/(\r\n|\n|\r)/", $block);
                $entry = array(
                    'timestamp'  => null,
                    'date_label' => 'Date unavailable',
                    'time_label' => '',
                    'name'       => '',
                    'subject'    => '',
                    'message'    => '',
                    'attachment' => null,
                );

                foreach ($lines as $legacyLine) {
                    $legacyLine = trim($legacyLine);
                    if ($legacyLine === '') {
                        continue;
                    }

                    if (stripos($legacyLine, 'Date:') === 0) {
                        $value = trim(substr($legacyLine, 5));
                        $entry['date_label'] = $value;
                        $parsedDate = date_create($value);
                        if ($parsedDate) {
                            $entry['timestamp'] = $parsedDate->format(DATE_ATOM);
                            $entry['time_label'] = $parsedDate->format('H:i');
                        }
                        continue;
                    }

                    if (stripos($legacyLine, 'Name:') === 0) {
                        $entry['name'] = trim(substr($legacyLine, 5));
                        continue;
                    }

                    if (stripos($legacyLine, 'Email:') === 0) {
                        // Intentionally ignored for UI purposes.
                        continue;
                    }

                    if (stripos($legacyLine, 'Topic:') === 0 || stripos($legacyLine, 'Subject:') === 0) {
                        $colonPos = strpos($legacyLine, ':');
                        $entry['subject'] = $colonPos !== false ? trim(substr($legacyLine, $colonPos + 1)) : '';
                        continue;
                    }

                    if (stripos($legacyLine, 'Note:') === 0 || stripos($legacyLine, 'Message:') === 0) {
                        $colonPos = strpos($legacyLine, ':');
                        $entry['message'] = $colonPos !== false ? trim(substr($legacyLine, $colonPos + 1)) : '';
                        continue;
                    }

                    // Append any remaining text to the message body.
                    if ($entry['message'] === '') {
                        $entry['message'] = $legacyLine;
                    } else {
                        $entry['message'] .= PHP_EOL . $legacyLine;
                    }
                }

                $entries[] = $entry;
            }
        }
    }
}

usort($entries, function ($a, $b) {
    $timeA = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
    $timeB = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
    return $timeB <=> $timeA;
});

if (count($entries) > 50) {
    $entries = array_slice($entries, 0, 50);
}

echo json_encode(array(
    'success'      => true,
    'entries'      => $entries,
    'fileExists'   => file_exists($diaryFile),
    'errorDetails' => $errors
));
