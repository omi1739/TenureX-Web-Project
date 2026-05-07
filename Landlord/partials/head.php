<?php
/**
 * Shared <head> markup for every page.
 *
 * Set $pageTitle BEFORE including this file, e.g.
 *   $pageTitle = 'Dashboard — TenureX';
 *   include __DIR__ . '/../partials/head.php';
 */
$pageTitle = $pageTitle ?? 'TenureX';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link href="https://fonts.googleapis.com/css?family=Inter:400,500,600,700,800&display=swap" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] } } },
    };
  </script>
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body class="font-sans bg-gray-50 text-gray-900 antialiased">
