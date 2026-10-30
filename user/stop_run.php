<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { http_response_code(403); exit; }
$jobId = preg_replace('/[^a-f0-9]/', '', $_POST['job'] ?? '');
if (!$jobId) exit(json_encode(['error'=>'missing job']));
$file = __DIR__ . '/jobs/' . $jobId . '.status.json';
if (!file_exists($file)) exit(json_encode(['error'=>'not found']));
$cur = json_decode(file_get_contents($file), true) ?: [];
$cur['stop_requested'] = true;
file_put_contents($file, json_encode($cur), LOCK_EX);
echo json_encode(['success'=>true]);