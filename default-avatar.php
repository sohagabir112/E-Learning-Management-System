<?php
// Default avatar generator
header('Content-Type: image/svg+xml');

// Get user initials from query parameter or use default
$initials = isset($_GET['initials']) ? strtoupper(substr($_GET['initials'], 0, 2)) : 'U';

echo '<?xml version="1.0" encoding="UTF-8"?>
<svg width="150" height="150" viewBox="0 0 150 150" xmlns="http://www.w3.org/2000/svg">
  <circle cx="75" cy="75" r="75" fill="#e1e8ed"/>
  <circle cx="75" cy="60" r="25" fill="#95a5a6"/>
  <ellipse cx="75" cy="130" rx="35" ry="25" fill="#95a5a6"/>
  <text x="75" y="68" font-family="Arial, sans-serif" font-size="24" font-weight="bold" text-anchor="middle" fill="white">' . htmlspecialchars($initials) . '</text>
</svg>';
?>
