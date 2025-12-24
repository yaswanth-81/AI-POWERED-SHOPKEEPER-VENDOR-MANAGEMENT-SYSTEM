<?php
header('Content-Type: application/json');
session_start();
$_SESSION['assistant_chat'] = [];
echo json_encode(['success' => true]);
