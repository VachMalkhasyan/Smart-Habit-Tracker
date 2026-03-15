<?php
$user = App\Models\User::find(1);
$service = app(App\Services\AiService::class);
echo "SYSTEM PROMPT:\n" . $service->buildSystemPrompt($user) . "\n-----------------\n";
