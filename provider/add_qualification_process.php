<?php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'provider') {
    header("Location: ../login.html"); exit;
}
$providerNin = $_SESSION['user_id'];
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $institution_name    = trim($_POST['institution_name']    ?? '');
    $qualification_title = trim($_POST['qualification_title'] ?? '');
    $qualification_level = trim($_POST['qualification_level'] ?? '');
    $specialization      = trim($_POST['specialization']       ?? '');
    $skills_acquired     = trim($_POST['skills_acquired']      ?? '');
    $year_obtained       = intval($_POST['year_obtained']       ?? 0);
    $document_reference  = trim($_POST['document_reference']   ?? '');

    if (empty($institution_name) || empty($qualification_title) || empty($qualification_level) || $year_obtained <= 0) {
        header("Location: add_qualification.html?error=missing");
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO qualification (provider_nin, institution_name, qualification_title, qualification_level, specialization, skills_acquired, year_obtained, document_reference) VALUES(?,?,?,?,?,?,?,?)");
        $stmt->execute([$providerNin, $institution_name, $qualification_title, $qualification_level, $specialization, $skills_acquired, $year_obtained, $document_reference]);
        header("Location: dashboard.php?added=success");
        exit;
    } catch (PDOException $e) {
        header("Location: add_qualification.html?error=db_error");
        exit;
    }
}

header("Location: add_qualification.html");
exit;
