<?php

namespace App\Services;

use App\Models\JitsiSetting;
use App\Models\Teleconsultation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JitsiService
{
    private $settings;

    public function __construct()
    {
        $this->settings = JitsiSetting::current();
    }

    /**
     * Create a Jitsi Meet meeting for teleconsultation
     */
    public function createMeeting(Teleconsultation $teleconsultation): array
    {
        if (!$this->settings->isConfigured()) {
            throw new \Exception('Jitsi Meet is not properly configured');
        }

        try {
            // Generate unique meeting room name
            $roomName = $this->generateRoomName($teleconsultation);
            
            // Generate JWT token if configured
            $jwtToken = $this->generateJWTToken($teleconsultation, $roomName);
            
            // Build meeting URL
            $meetingUrl = $this->buildMeetingUrl($roomName, $jwtToken);
            
            // Generate meeting password if required
            $meetingPassword = $this->settings->require_password ? $this->generateMeetingPassword() : null;
            
            // Update teleconsultation with meeting details
            $teleconsultation->update([
                'meeting_url' => $meetingUrl,
                'meeting_password' => $meetingPassword,
                'meeting_id' => $roomName,
                'status' => 'scheduled',
            ]);

            Log::info('Jitsi Meet meeting created successfully', [
                'teleconsultation_id' => $teleconsultation->id,
                'meeting_id' => $roomName,
                'meeting_url' => $meetingUrl,
                'meeting_password' => $meetingPassword,
            ]);

            // Build dynamic meeting configuration based on teleconsultation preferences
            $meetingConfig = $this->buildDynamicMeetingConfig($teleconsultation);

            return [
                'success' => true,
                'meeting' => [
                    'id' => $roomName,
                    'meetingUrl' => $meetingUrl,
                    'meetingCode' => $roomName,
                    'meetingPassword' => $meetingPassword,
                    'start_time' => $teleconsultation->scheduled_at,
                    'end_time' => Carbon::parse($teleconsultation->scheduled_at)->addMinutes($this->settings->meeting_duration_minutes),
                    'participants' => $this->getParticipants($teleconsultation),
                    'status' => 'scheduled',
                    'jwt_token' => $jwtToken,
                    'room_name' => $roomName,
                    'config' => $meetingConfig, // Dynamic configuration
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to create Jitsi Meet meeting', [
                'teleconsultation_id' => $teleconsultation->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to create Jitsi Meet meeting: ' . $e->getMessage());
        }
    }

    /**
     * Get meeting details
     */
    public function getMeetingDetails(string $meetingId): ?array
    {
        try {
            $meetingUrl = $this->buildMeetingUrl($meetingId);
            
            return [
                'id' => $meetingId,
                'meeting_url' => $meetingUrl,
                'meeting_code' => $meetingId,
                'status' => 'active', // Jitsi meetings are always active when accessed
                'participants' => [], // Would need to implement participant tracking
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get meeting details', [
                'meeting_id' => $meetingId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * End a meeting (Jitsi doesn't have server-side meeting control)
     */
    public function endMeeting(string $meetingId): bool
    {
        try {
            // Jitsi doesn't have server-side meeting control
            // This is handled client-side by the participants leaving
            Log::info('Jitsi meeting ended', [
                'meeting_id' => $meetingId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to end meeting', [
                'meeting_id' => $meetingId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send meeting invitation
     */
    public function sendMeetingInvite(string $meetingUrl, array $participants): bool
    {
        try {
            // This would typically integrate with your email service
            // For now, we'll just log the invitation details
            Log::info('Sending Jitsi meeting invites', [
                'meeting_url' => $meetingUrl,
                'participants' => $participants,
            ]);

            // You can integrate with your email service here
            // Example: Mail::send(new MeetingInvite($meetingUrl, $participants));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send meeting invites', [
                'meeting_url' => $meetingUrl,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate unique room name for the meeting
     */
    private function generateRoomName(Teleconsultation $teleconsultation): string
    {
        // Create a unique room name based on teleconsultation details
        $prefix = 'nexthospital';
        $timestamp = time();
        $random = Str::random(8);
        
        return "{$prefix}-{$teleconsultation->id}-{$timestamp}-{$random}";
    }

    /**
     * Generate JWT token for secure meeting access (doctor/moderator)
     */
    public function generateJWTToken(Teleconsultation $teleconsultation, string $roomName): ?string
    {
        if (empty($this->settings->jwt_secret)) {
            return null; // No JWT secret configured, use public access
        }

        try {
            $now = time();
            $exp = $now + (24 * 60 * 60); // 24 hours from now

            $payload = [
                'iss' => $this->settings->app_id ?? 'nexthospital',
                'aud' => 'jitsi',
                'exp' => $exp,
                'nbf' => $now,
                'room' => $roomName,
                'context' => [
                    'user' => [
                        'id' => $teleconsultation->doctor_id,
                        'name' => "Dr. {$teleconsultation->doctor->first_name} {$teleconsultation->doctor->last_name}",
                        'email' => $teleconsultation->doctor->email,
                        'avatar' => '',
                        'moderator' => true,
                    ],
                    'group' => 'doctors',
                ],
                'sub' => $this->settings->server_url,
            ];

            return JWT::encode($payload, $this->settings->jwt_secret, $this->settings->jwt_algorithm);
        } catch (\Exception $e) {
            Log::error('Failed to generate JWT token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build meeting URL
     */
    private function buildMeetingUrl(string $roomName, ?string $jwtToken = null): string
    {
        $url = rtrim($this->settings->server_url, '/') . '/' . $roomName;
        
        if ($jwtToken) {
            $url .= '?jwt=' . $jwtToken;
        }

        return $url;
    }

    /**
     * Generate meeting password
     */
    private function generateMeetingPassword(): string
    {
        return Str::random(8);
    }

    /**
     * Get participants for teleconsultation
     */
    private function getParticipants(Teleconsultation $teleconsultation): array
    {
        return [
            [
                'id' => $teleconsultation->doctor_id,
                'name' => "Dr. {$teleconsultation->doctor->first_name} {$teleconsultation->doctor->last_name}",
                'email' => $teleconsultation->doctor->email,
                'role' => 'doctor',
                'status' => 'invited',
            ],
            [
                'id' => $teleconsultation->patient_id,
                'name' => "{$teleconsultation->patient->first_name} {$teleconsultation->patient->last_name}",
                'email' => $teleconsultation->patient->email,
                'role' => 'patient',
                'status' => 'invited',
            ],
        ];
    }

    /**
     * Check if Jitsi is available
     */
    public function isAvailable(): bool
    {
        return $this->settings->enabled && $this->settings->isConfigured();
    }

    /**
     * Get settings for frontend
     */
    public function getSettings(): array
    {
        return $this->settings->getPublicSettings();
    }

    /**
     * Get client configuration
     */
    public function getClientConfig(): array
    {
        return $this->settings->getClientConfig();
    }

    /**
     * Build dynamic meeting configuration based on teleconsultation preferences
     */
    private function buildDynamicMeetingConfig(Teleconsultation $teleconsultation): array
    {
        $baseConfig = $this->settings->getClientConfig();
        
        // Override with teleconsultation-specific preferences
        $dynamicConfig = [
            'video_enabled' => $teleconsultation->video_enabled ?? ($baseConfig['screen_sharing_enabled'] ?? true),
            'audio_enabled' => $teleconsultation->audio_enabled ?? true,
            'recording_enabled' => $teleconsultation->recording_enabled ?? ($baseConfig['recording_enabled'] ?? false),
            'screen_sharing_enabled' => $teleconsultation->screen_sharing_enabled ?? ($baseConfig['screen_sharing_enabled'] ?? true),
        ];

        // Merge with base config, allowing teleconsultation preferences to override
        return array_merge($baseConfig, $dynamicConfig);
    }

    /**
     * Get dynamic meeting configuration for a specific teleconsultation
     */
    public function getMeetingConfig(Teleconsultation $teleconsultation): array
    {
        return $this->buildDynamicMeetingConfig($teleconsultation);
    }

    /**
     * Generate patient JWT token for joining meeting
     */
    public function generatePatientJWTToken(Teleconsultation $teleconsultation, string $roomName): ?string
    {
        if (empty($this->settings->jwt_secret)) {
            return null; // No JWT secret configured, use public access
        }

        try {
            $now = time();
            $exp = $now + (24 * 60 * 60); // 24 hours from now

            $payload = [
                'iss' => $this->settings->app_id ?? 'nexthospital',
                'aud' => 'jitsi',
                'exp' => $exp,
                'nbf' => $now,
                'room' => $roomName,
                'context' => [
                    'user' => [
                        'id' => $teleconsultation->patient_id,
                        'name' => "{$teleconsultation->patient->first_name} {$teleconsultation->patient->last_name}",
                        'email' => $teleconsultation->patient->email,
                        'avatar' => '',
                        'moderator' => false,
                    ],
                    'group' => 'patients',
                ],
                'sub' => $this->settings->server_url,
            ];

            return JWT::encode($payload, $this->settings->jwt_secret, $this->settings->jwt_algorithm);
        } catch (\Exception $e) {
            Log::error('Failed to generate patient JWT token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
