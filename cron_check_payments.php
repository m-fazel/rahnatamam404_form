<?php
require_once __DIR__ . '/config.php';

function store_successful_codes(PDO $pdo, $registrationId)
{
    try {
        $stmt = $pdo->prepare('SELECT national_code, spouse_national_code FROM registrations WHERE id = :id');
        $stmt->execute([':id' => $registrationId]);
        $registration = $stmt->fetch();
        if (!$registration) {
            return;
        }

        $codes = [];
        if (!empty($registration['national_code'])) {
            $codes[] = ['code' => $registration['national_code'], 'role' => 'primary'];
        }
        if (!empty($registration['spouse_national_code'])) {
            $codes[] = ['code' => $registration['spouse_national_code'], 'role' => 'spouse'];
        }

        $memberStmt = $pdo->prepare('SELECT id, national_code FROM group_members WHERE registration_id = :id');
        $memberStmt->execute([':id' => $registrationId]);
        foreach ($memberStmt->fetchAll() as $member) {
            if (!empty($member['national_code'])) {
                $codes[] = [
                    'code' => $member['national_code'],
                    'role' => sprintf('group_member_%d', $member['id']),
                ];
            }
        }

        if ($codes === []) {
            return;
        }

        $insertStmt = $pdo->prepare('INSERT IGNORE INTO national_codes (code, registration_id, role, created_at) VALUES (:code, :registration_id, :role, NOW())');
        foreach ($codes as $record) {
            $insertStmt->execute([
                ':code' => $record['code'],
                ':registration_id' => $registrationId,
                ':role' => $record['role'],
            ]);
        }
    } catch (PDOException $e) {
        // Table missing or insert error, skip.
    }
}

$pdo = get_pdo($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);

$stmt = $pdo->prepare('SELECT id, payment_order_id, payment_order_guid FROM registrations WHERE payment_order_id IS NOT NULL AND payment_checked_at IS NULL');
$stmt->execute();

$registrations = $stmt->fetchAll();

foreach ($registrations as $registration) {
    $payload = [
        'Ch' => 1,
        'FN' => 'User_Payment_Status2',
        'OrderID' => (string) $registration['payment_order_id'],
        'OrderGUID' => (string) $registration['payment_order_guid'],
    ];

    $ch = curl_init('https://pay.sharif.edu/api/API');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'Input' => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        continue;
    }

    $result = json_decode($response, true);
    if (!is_array($result) || ($result['Result'] ?? null) !== 0) {
        continue;
    }

    $status = $result['User_Payment_Status'] ?? [];
    $statusId = isset($status['StatusID']) ? (int) $status['StatusID'] : null;
    $statusText = isset($status['Status']) ? (string) $status['Status'] : null;
    $reference = isset($status['Reference']) && $status['Reference'] !== '-1' ? (string) $status['Reference'] : null;

    $updateStmt = $pdo->prepare('UPDATE registrations SET payment_status_id = :status_id, payment_status_text = :status_text, payment_reference = :reference, payment_checked_at = NOW() WHERE id = :id');
    $updateStmt->execute([
        ':status_id' => $statusId,
        ':status_text' => $statusText,
        ':reference' => $reference,
        ':id' => $registration['id'],
    ]);

    if ($statusId === 0 && $reference !== null) {
        store_successful_codes($pdo, (int) $registration['id']);
    }
}
