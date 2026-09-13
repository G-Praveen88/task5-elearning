<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: includes/header.php
 * 
 * Global Layout Header & Asset Inclusions
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth_check.php';

$pageTitle = $pageTitle ?? 'E-Learning Portal | Online Learning & LMS';
$pageDescription = $pageDescription ?? 'A comprehensive Learning Management System with secure authentication, interactive video lessons, progress tracking, and administrative analytics.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <meta name="description" content="<?= e($pageDescription); ?>">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons 1.11.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('css/style.css'); ?>">
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="flex-grow-1">
