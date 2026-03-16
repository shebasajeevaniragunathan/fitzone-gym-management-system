<?php
require_once "config/mail.php";

$ok = sendFitZoneMail(
    "fitnesscentrefitzone@gmail.com",
    "Test Mail",
    "<h2>FitZone mail working 🔥</h2>"
);

echo $ok ? "Mail sent successfully!" : "Mail failed.";