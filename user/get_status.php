<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') { http_response_code(403); exit; }
$jobId = preg_replace('/[^a-f0-9]/', '', $_GET['job'] ?? '');
if (!$jobId) exit(json_encode(['error'=>'missing job']));
$file = __DIR__ . '/jobs/' . $jobId . '.status.json';
if (!file_exists($file)) exit(json_encode(['error'=>'not found']));
header('Content-Type: application/json');
echo file_get_contents($file);