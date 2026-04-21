<?php

namespace App\Console\Commands;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Console\Command;
use GuzzleHttp\Client as GuzzleClient;

class AuthorizeGoogleDrive extends Command
{
    protected $signature   = 'drive:authorize';
    protected $description = 'One-time OAuth2 authorization for Google Drive access';

    public function handle(): int
    {
        $clientSecrets = storage_path('app/google-oauth-client.json');

        if (!file_exists($clientSecrets)) {
            $this->error("Client secrets not found at: {$clientSecrets}");
            $this->line('Download it from Google Cloud → APIs & Services → Credenciais → OAuth 2.0 Client ID');
            return 1;
        }

        $client = new Client();
        $client->setAuthConfig($clientSecrets);
        $client->addScope(Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // forces refresh_token to be returned
        $client->setRedirectUri('http://localhost');
        $client->setHttpClient($this->buildHttpClient());

        $authUrl = $client->createAuthUrl();

        $this->info('Open this URL in your browser:');
        $this->line('');
        $this->line($authUrl);
        $this->line('');
        $this->warn('After authorizing, your browser will redirect to http://localhost (it will fail to load — that is normal).');
        $this->warn('Copy the FULL URL from the address bar and paste it below.');
        $this->line('Example: http://localhost?code=4/0AXXXXXXX&scope=...');
        $this->line('');

        $redirected = trim($this->ask('Paste the full redirect URL here'));

        // Extract the code from the URL
        $parsed = parse_url($redirected);
        parse_str($parsed['query'] ?? '', $params);
        $code = $params['code'] ?? $redirected; // fallback: maybe they pasted just the code

        if (empty($code)) {
            $this->error('Could not extract code from URL. Please try again.');
            return 1;
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $this->error('Failed: ' . $token['error_description'] ?? $token['error']);
            return 1;
        }

        $tokenPath = storage_path('app/google-token.json');
        file_put_contents($tokenPath, json_encode($token, JSON_PRETTY_PRINT));

        $this->info("✓ Token saved to: {$tokenPath}");
        $this->info('Google Drive is now authorized. The token auto-refreshes — run this command again only if access is revoked.');

        return 0;
    }

    private function buildHttpClient(): GuzzleClient
    {
        if (!app()->environment('local')) {
            return new GuzzleClient();
        }

        $caBundle = env('CURL_CA_BUNDLE', 'C:\\wamp64\\bin\\php\\php8.2.18\\extras\\ssl\\cacert.pem');
        if (!file_exists($caBundle)) {
            $this->warn("CA bundle not found at: {$caBundle}. Falling back to default SSL config.");
            return new GuzzleClient();
        }

        $this->line("Using local CA bundle: {$caBundle}");
        return new GuzzleClient([
            'verify' => $caBundle,
        ]);
    }
}
