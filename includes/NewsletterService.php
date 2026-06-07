<?php

class NewsletterService
{
    /**
     * Phase 1.5: Wire up Mailchimp API when keys are configured in Settings.
     */
    public static function syncToMailchimp(string $email): bool
    {
        $apiKey = Settings::get('mailchimp_api_key');
        $listId = Settings::get('mailchimp_list_id');
        if (!$apiKey || !$listId) {
            return false;
        }
        // Placeholder for Mailchimp API integration
        return false;
    }

    public static function isConfigured(): bool
    {
        return (bool) Settings::get('mailchimp_api_key') && (bool) Settings::get('mailchimp_list_id');
    }
}
