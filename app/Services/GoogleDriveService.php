<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;

class GoogleDriveService
{
    private Drive $drive;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google-oauth-client.json'));
        $client->addScope(Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setRedirectUri('http://localhost');
        $client->setHttpClient($this->buildHttpClient());

        $tokenPath = storage_path('app/google-token.json');

        if (!file_exists($tokenPath)) {
            throw new \RuntimeException(
                'Google Drive not authorized. Run: php artisan drive:authorize'
            );
        }

        $token = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($token);

        // Auto-refresh expired token and persist it
        if ($client->isAccessTokenExpired()) {
            if (!$client->getRefreshToken()) {
                throw new \RuntimeException(
                    'Google Drive refresh token missing. Run: php artisan drive:authorize'
                );
            }
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken(), JSON_PRETTY_PRINT));
        }

        $this->drive = new Drive($client);
    }

    private function buildHttpClient(): GuzzleClient
    {
        if (!app()->environment('local')) {
            return new GuzzleClient();
        }

        $caBundle = env('CURL_CA_BUNDLE', 'C:\\wamp64\\bin\\php\\php8.2.18\\extras\\ssl\\cacert.pem');
        if (!file_exists($caBundle)) {
            Log::warning('GoogleDriveService: local CA bundle not found, using default SSL config.', [
                'ca_bundle' => $caBundle,
            ]);
            return new GuzzleClient();
        }

        return new GuzzleClient([
            'verify' => $caBundle,
        ]);
    }

    /**
     * Upload a file from local storage path to Drive.
     * Returns ['drive_file_id', 'drive_link', 'drive_download_link'] or throws.
     */
    public function upload(string $localAbsPath, string $filename, string $mimeType): array
    {
        $folderId = config('services.google_drive.folder_id');

        $meta = new DriveFile([
            'name'    => $filename,
            'parents' => $folderId ? [$folderId] : [],
        ]);

        $result = $this->drive->files->create($meta, [
            'data'       => file_get_contents($localAbsPath),
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id,webViewLink,webContentLink',
        ]);

        // Make readable via link (anyone with link can view)
        $this->drive->permissions->create($result->id, new Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]));

        return [
            'drive_file_id'       => $result->id,
            'drive_link'          => $result->webViewLink,
            'drive_download_link' => $result->webContentLink,
        ];
    }

    /**
     * Delete a file from Drive by its Drive file ID.
     */
    public function delete(string $driveFileId): void
    {
        try {
            $this->drive->files->delete($driveFileId);
        } catch (\Exception $e) {
            Log::warning("GoogleDrive: failed to delete {$driveFileId}: " . $e->getMessage());
        }
    }
}
