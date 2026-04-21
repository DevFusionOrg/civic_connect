<?php
// Redirect root requests to API health for Render service URL checks.
header('Location: /api/health', true, 302);
exit;
