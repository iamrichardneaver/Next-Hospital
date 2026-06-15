<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JitsiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'server_url',
        'app_id',
        'app_secret',
        'jwt_secret',
        'jwt_algorithm',
        'meeting_duration_minutes',
        'recording_enabled',
        'chat_enabled',
        'screen_sharing_enabled',
        'file_sharing_enabled',
        'live_streaming_enabled',
        'transcription_enabled',
        'waiting_room_enabled',
        'mute_on_entry',
        'require_display_name',
        'require_password',
        'enable_knocking',
        'enable_lobby',
        'max_participants',
        'default_language',
        'interface_config',
        'config_overwrite',
        'toolbar_buttons',
        'default_timezone',
        'reminder_settings',
        'meeting_settings',
    ];

    protected $hidden = [
        'app_secret', // Hide sensitive data
        'jwt_secret', // Hide sensitive data
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'recording_enabled' => 'boolean',
        'chat_enabled' => 'boolean',
        'screen_sharing_enabled' => 'boolean',
        'file_sharing_enabled' => 'boolean',
        'live_streaming_enabled' => 'boolean',
        'transcription_enabled' => 'boolean',
        'waiting_room_enabled' => 'boolean',
        'mute_on_entry' => 'boolean',
        'require_display_name' => 'boolean',
        'require_password' => 'boolean',
        'enable_knocking' => 'boolean',
        'enable_lobby' => 'boolean',
        'interface_config' => 'array',
        'config_overwrite' => 'array',
        'toolbar_buttons' => 'array',
        'reminder_settings' => 'array',
        'meeting_settings' => 'array',
    ];

    /**
     * Get current Jitsi settings
     */
    public static function current()
    {
        return static::first() ?? static::create([
            'enabled' => true,
            'server_url' => 'https://meet.jit.si',
            'app_id' => env('JITSI_APP_ID', ''),
            'app_secret' => env('JITSI_APP_SECRET', ''),
            'jwt_secret' => env('JITSI_JWT_SECRET', ''),
            'jwt_algorithm' => 'HS256',
            'meeting_duration_minutes' => 60,
            'recording_enabled' => false,
            'chat_enabled' => true,
            'screen_sharing_enabled' => true,
            'file_sharing_enabled' => true,
            'live_streaming_enabled' => false,
            'transcription_enabled' => false,
            'waiting_room_enabled' => false,
            'mute_on_entry' => false,
            'require_display_name' => true,
            'require_password' => false,
            'enable_knocking' => true,
            'enable_lobby' => false,
            'max_participants' => 100,
            'default_language' => 'en',
            'interface_config' => [
                'SHOW_JITSI_WATERMARK' => false,
                'SHOW_WATERMARK_FOR_GUESTS' => false,
                'SHOW_POWERED_BY' => false,
                'DEFAULT_LOGO_URL' => '',
                'DEFAULT_WELCOME_PAGE_LOGO_URL' => '',
                'PROVIDER_NAME' => config('app.name', 'Hospital'),
                'APP_NAME' => config('app.name', 'Hospital') . ' Teleconsultation',
                'LANG_DETECTION' => true,
                'DEFAULT_LANGUAGE' => 'en',
                'DISPLAY_WELCOME_FOOTER' => false,
                'DISPLAY_WELCOME_PAGE_ADDITIONAL_CARD' => false,
                'DISPLAY_WELCOME_PAGE_CONTENT' => false,
                'DISPLAY_WELCOME_PAGE_TOOLBAR_ADDITIONAL_CONTENT' => false,
                'GENERATE_ROOMNAMES_ON_WELCOME_PAGE' => true,
                'ENABLE_WELCOME_PAGE' => true,
                'ENABLE_DIAL_IN' => false,
                'ENABLE_RECORDING' => false,
                'ENABLE_LIVE_STREAMING' => false,
                'ENABLE_TRANSCRIPTION' => false,
                'ENABLE_REACTIONS' => true,
                'ENABLE_AVATAR' => true,
                'ENABLE_EMAIL_REPORTING' => false,
                'ENABLE_IPV6' => true,
                'ENABLE_P2P' => true,
                'ENABLE_FILE_UPLOAD' => true,
                'ENABLE_FILE_SHARING' => true,
                'ENABLE_CHAT' => true,
                'ENABLE_SCREEN_SHARING' => true,
                'ENABLE_KNOCKING_PARTICIPANT' => true,
                'ENABLE_LOBBY' => false,
                'ENABLE_WAITING_ROOM' => false,
                'ENABLE_PREJOIN_PAGE' => true,
                'ENABLE_WELCOME_PAGE' => true,
                'ENABLE_CLOSE_PAGE' => true,
                'ENABLE_AUDIO_FOCUS_DISABLED' => false,
                'ENABLE_DOMINANT_SPEAKER_INDICATOR' => true,
                'ENABLE_FACE_CENTERING' => true,
                'ENABLE_NO_AUDIO_DETECTION' => true,
                'ENABLE_NOISY_MIC_DETECTION' => true,
                'ENABLE_TALK_WHILE_MUTED' => true,
                'ENABLE_REACTIONS' => true,
                'ENABLE_AVATAR' => true,
                'ENABLE_EMAIL_REPORTING' => false,
                'ENABLE_IPV6' => true,
                'ENABLE_P2P' => true,
                'ENABLE_FILE_UPLOAD' => true,
                'ENABLE_FILE_SHARING' => true,
                'ENABLE_CHAT' => true,
                'ENABLE_SCREEN_SHARING' => true,
                'ENABLE_KNOCKING_PARTICIPANT' => true,
                'ENABLE_LOBBY' => false,
                'ENABLE_WAITING_ROOM' => false,
                'ENABLE_PREJOIN_PAGE' => true,
                'ENABLE_WELCOME_PAGE' => true,
                'ENABLE_CLOSE_PAGE' => true,
                'ENABLE_AUDIO_FOCUS_DISABLED' => false,
                'ENABLE_DOMINANT_SPEAKER_INDICATOR' => true,
                'ENABLE_FACE_CENTERING' => true,
                'ENABLE_NO_AUDIO_DETECTION' => true,
                'ENABLE_NOISY_MIC_DETECTION' => true,
                'ENABLE_TALK_WHILE_MUTED' => true,
            ],
            'config_overwrite' => [
                'startWithAudioMuted' => false,
                'startWithVideoMuted' => false,
                'enableWelcomePage' => true,
                'enableClosePage' => true,
                'enablePrejoinPage' => true,
                'enableNoisyMicDetection' => true,
                'enableTalkWhileMuted' => true,
                'enableNoAudioDetection' => true,
                'enableFaceCentering' => true,
                'enableDominantSpeakerIndicator' => true,
                'enableAudioFocus' => true,
                'enableP2P' => true,
                'enableIPv6' => true,
                'enableEmailReporting' => false,
                'enableAvatar' => true,
                'enableReactions' => true,
                'enableTranscription' => false,
                'enableLiveStreaming' => false,
                'enableRecording' => false,
                'enableDialIn' => false,
                'enableWelcomePage' => true,
                'enableClosePage' => true,
                'enablePrejoinPage' => true,
                'enableNoisyMicDetection' => true,
                'enableTalkWhileMuted' => true,
                'enableNoAudioDetection' => true,
                'enableFaceCentering' => true,
                'enableDominantSpeakerIndicator' => true,
                'enableAudioFocus' => true,
                'enableP2P' => true,
                'enableIPv6' => true,
                'enableEmailReporting' => false,
                'enableAvatar' => true,
                'enableReactions' => true,
                'enableTranscription' => false,
                'enableLiveStreaming' => false,
                'enableRecording' => false,
                'enableDialIn' => false,
            ],
            'toolbar_buttons' => [
                'microphone',
                'camera',
                'closedcaptions',
                'desktop',
                'embedmeeting',
                'fullscreen',
                'fodeviceselection',
                'hangup',
                'profile',
                'chat',
                'recording',
                'livestreaming',
                'etherpad',
                'sharedvideo',
                'settings',
                'raisehand',
                'videoquality',
                'filmstrip',
                'invite',
                'feedback',
                'stats',
                'shortcuts',
                'tileview',
                'videobackgroundblur',
                'download',
                'help',
                'mute-everyone',
                'e2ee',
                'security',
                'whiteboard',
                'reactions',
                'transcription',
                'noise-suppression',
                'noise-cancellation',
                'noise-reduction',
                'noise-filtering',
                'noise-isolation',
                'noise-elimination',
                'noise-removal',
                'noise-suppression',
                'noise-cancellation',
                'noise-reduction',
                'noise-filtering',
                'noise-isolation',
                'noise-elimination',
                'noise-removal',
            ],
            'default_timezone' => 'UTC',
            'reminder_settings' => [
                'email_reminder' => true,
                'popup_reminder' => true,
                'reminder_minutes' => [10, 24 * 60]
            ],
            'meeting_settings' => [
                'waiting_room_enabled' => false,
                'mute_on_entry' => false,
                'auto_admit' => true,
                'require_password' => false,
                'enable_knocking' => true,
                'enable_lobby' => false,
            ],
        ]);
    }

    /**
     * Update Jitsi settings
     */
    public static function updateSettings($data)
    {
        $settings = static::current();
        $settings->update($data);
        return $settings;
    }

    /**
     * Check if Jitsi is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->server_url) && $this->enabled;
    }

    /**
     * Get public settings for frontend (without sensitive data)
     */
    public function getPublicSettings(): array
    {
        return [
            'enabled' => $this->enabled,
            'server_url' => $this->server_url,
            'meeting_duration_minutes' => $this->meeting_duration_minutes,
            'recording_enabled' => $this->recording_enabled,
            'chat_enabled' => $this->chat_enabled,
            'screen_sharing_enabled' => $this->screen_sharing_enabled,
            'file_sharing_enabled' => $this->file_sharing_enabled,
            'live_streaming_enabled' => $this->live_streaming_enabled,
            'transcription_enabled' => $this->transcription_enabled,
            'waiting_room_enabled' => $this->waiting_room_enabled,
            'mute_on_entry' => $this->mute_on_entry,
            'require_display_name' => $this->require_display_name,
            'require_password' => $this->require_password,
            'enable_knocking' => $this->enable_knocking,
            'enable_lobby' => $this->enable_lobby,
            'max_participants' => $this->max_participants,
            'default_language' => $this->default_language,
            'interface_config' => $this->interface_config,
            'config_overwrite' => $this->config_overwrite,
            'toolbar_buttons' => $this->toolbar_buttons,
            'default_timezone' => $this->default_timezone,
            'reminder_settings' => $this->reminder_settings,
            'meeting_settings' => $this->meeting_settings,
            'is_configured' => $this->isConfigured(),
        ];
    }

    /**
     * Get client configuration for frontend
     */
    public function getClientConfig(): array
    {
        return [
            'enabled' => $this->enabled,
            'server_url' => $this->server_url,
            'app_id' => $this->app_id,
            'jwt_algorithm' => $this->jwt_algorithm,
            'meeting_duration_minutes' => $this->meeting_duration_minutes,
            'recording_enabled' => $this->recording_enabled,
            'chat_enabled' => $this->chat_enabled,
            'screen_sharing_enabled' => $this->screen_sharing_enabled,
            'file_sharing_enabled' => $this->file_sharing_enabled,
            'live_streaming_enabled' => $this->live_streaming_enabled,
            'transcription_enabled' => $this->transcription_enabled,
            'waiting_room_enabled' => $this->waiting_room_enabled,
            'mute_on_entry' => $this->mute_on_entry,
            'require_display_name' => $this->require_display_name,
            'require_password' => $this->require_password,
            'enable_knocking' => $this->enable_knocking,
            'enable_lobby' => $this->enable_lobby,
            'max_participants' => $this->max_participants,
            'default_language' => $this->default_language,
            'interface_config' => $this->interface_config,
            'config_overwrite' => $this->config_overwrite,
            'toolbar_buttons' => $this->toolbar_buttons,
            'default_timezone' => $this->default_timezone,
            'require_password' => $this->require_password,
            'enable_knocking' => $this->enable_knocking,
            'enable_lobby' => $this->enable_lobby,
        ];
    }
}
