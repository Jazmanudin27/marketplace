<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$convs = \App\Models\ChatConversation::count();
$msgs = \App\Models\ChatMessage::count();
echo "Total Conversations: $convs\n";
echo "Total Messages: $msgs\n";

if ($convs > 0) {
    echo "\nSample Conversations:\n";
    foreach (\App\Models\ChatConversation::with('store')->limit(5)->get() as $c) {
        echo "- ID: {$c->id} | Buyer: {$c->buyer_name} | Store: {$c->store->store_name} | Status: {$c->status} | Last Message: {$c->last_message_preview}\n";
    }
}
