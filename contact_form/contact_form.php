<?php

// configure
$from = 'no-reply@aminadineh.ir';
$sendTo = 'AminAdineh95@gmail.com';
$formType = isset($_POST['form_type']) ? $_POST['form_type'] : 'contact_form';

switch ($formType) {
    case 'diary_note':
        $subject = 'New public diary note submitted';
        $fields = array(
            'name'    => 'Name',
            'email'   => 'Email',
            'subject' => 'Topic',
            'message' => 'Note'
        );
        $okMessage = 'Thanks for sharing a note! Your message has been received.';
        $diaryFile = __DIR__ . '/../notes_diary.txt';
        break;

    default:
        $subject = 'New message from contact form';
        $fields = array(
            'name'    => 'Name',
            'email'   => 'Email',
            'subject' => 'Subject',
            'message' => 'Message'
        );
        $okMessage = 'Contact form successfully submitted. Thank you, I will get back to you soon!';
        break;
}

$errorMessage = 'There was an error while submitting the form. Please try again later';

function outputResponse($responseArray) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($responseArray);
    } else {
        echo $responseArray['message'];
    }
}

// let's do the sending

$captchaRequired = ($formType !== 'diary_note');

if ($captchaRequired) {
    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $secret = '6LdqmCAUAAAAANONcPUkgVpTSGGqm60cabVMVaON';
        $c = curl_init('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        $verifyResponse = curl_exec($c);

        $responseData = json_decode($verifyResponse);
        if (empty($responseData) || empty($responseData->success)) {
            $responseArray = array('type' => 'danger', 'message' => 'Robot verification failed, please try again.');
            outputResponse($responseArray);
            exit;
        }
    } else {
        $responseArray = array('type' => 'danger', 'message' => 'Please click on the reCAPTCHA box.');
        outputResponse($responseArray);
        exit;
    }
}

try
{
    $cleanInput = array();
    foreach ($fields as $key => $label) {
        $rawValue = isset($_POST[$key]) ? $_POST[$key] : '';
        $cleanInput[$key] = trim(strip_tags($rawValue));
    }

    $emailText = nl2br("You have a new submission from your website (" . ucfirst(str_replace('_', ' ', $formType)) . ")\n\n");

    foreach ($fields as $key => $label) {
        $emailText .= nl2br($label . ': ' . $cleanInput[$key] . "\n");
    }

    if (isset($_SERVER['REMOTE_ADDR'])) {
        $emailText .= nl2br("\nIP Address: " . $_SERVER['REMOTE_ADDR'] . "\n");
    }

    $uploadedFilePath = null;
    if ($formType === 'diary_note' && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $allowedMime = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $_FILES['attachment']['tmp_name']);
        finfo_close($fileInfo);

        if (in_array($mimeType, $allowedMime, true)) {
            $uploadsDir = __DIR__ . '/../uploads/diary/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $safeName = uniqid('diary_', true) . '.' . strtolower($extension);
            $destination = $uploadsDir . $safeName;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
                $uploadedFilePath = $destination;
                $emailText .= nl2br("Attachment: " . $safeName . "\n");
            }
        }
    }

    if ($formType === 'diary_note' && isset($diaryFile)) {
        $entry = array(
            'timestamp'  => date('c'),
            'name'       => $cleanInput['name'],
            'email'      => $cleanInput['email'],
            'subject'    => $cleanInput['subject'],
            'message'    => $cleanInput['message'],
            'attachment' => $uploadedFilePath ? basename($uploadedFilePath) : null
        );

        $encodedEntry = json_encode($entry, JSON_UNESCAPED_UNICODE);
        if ($encodedEntry !== false) {
            file_put_contents($diaryFile, $encodedEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    $headers = array('Content-Type: text/html; charset="UTF-8";',
        'From: ' . $from,
        'Reply-To: ' . $from,
        'Return-Path: ' . $from,
    );
    
    mail($sendTo, $subject, $emailText, implode("\n", $headers));

    $responseArray = array('type' => 'success', 'message' => $okMessage);
}
catch (\Exception $e)
{
    $responseArray = array('type' => 'danger', 'message' => $errorMessage);
}

outputResponse($responseArray);

