<?php

namespace App\Console\Commands;

use App\Services\TextBeeService;
use Illuminate\Console\Command;

class SendTestSms extends Command
{
    /**
     * php artisan sms:test 3 +60123456789 --var=customerName=Kaiya --var=queueNumber=128 --var=waitTime=20
     */
    protected $signature = 'sms:test
        {templateId : The message_templates database ID to use}
        {phone : The recipient phone number, e.g. +60123456789}
        {--var=* : Template variables in key=value format}';

    protected $description = 'Send a test SMS via TextBee using a message template ID';

    public function handle(TextBeeService $textBee): int
    {
        $templateId = (int) $this->argument('templateId');
        $phone = $this->argument('phone');

        $variables = [];
        foreach ($this->option('var') as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            $variables[$key] = $value;
        }

        $this->info("Using template ID {$templateId} with variables: " . json_encode($variables));

        try {
            $result = $textBee->sendById($templateId, $phone, $variables);
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Template used: {$result['template']}");
        $this->line("Message sent: \"{$result['message']}\"");
        $this->line('TextBee response: ' . json_encode($result['textbee_response']));

        return self::SUCCESS;
    }
}