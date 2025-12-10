<?php
if (!admin()) {
    redirect('/');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    redirect('/advisories');
    exit;
}

$info = [];
compact("info");
?>