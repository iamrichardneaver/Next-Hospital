<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'mail_verify_peer',
        'is_active'
    ];

    protected $casts = [
        'mail_verify_peer' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get current email settings
     */
    public static function current()
    {
        return static::where('is_active', true)->first() ?? static::create([
            'mail_driver' => 'smtp',
            'mail_port' => 587,
            'mail_encryption' => 'tls',
            'is_active' => false
        ]);
    }

    /**
     * Update email settings
     */
    public static function updateSettings($data)
    {
        // Deactivate current settings
        static::where('is_active', true)->update(['is_active' => false]);
        
        // Create new settings
        $settings = static::create(array_merge($data, ['is_active' => true]));
        
        return $settings;
    }

    /**
     * Apply settings to Laravel config
     */
    public function applyToConfig()
    {
        config([
            'mail.default' => $this->mail_driver,
            'mail.mailers.smtp.host' => $this->mail_host,
            'mail.mailers.smtp.port' => $this->mail_port,
            'mail.mailers.smtp.username' => $this->mail_username,
            'mail.mailers.smtp.password' => $this->mail_password,
            'mail.mailers.smtp.encryption' => $this->mail_encryption,
            'mail.from.address' => $this->mail_from_address,
            'mail.from.name' => $this->mail_from_name,
        ]);
    }

    /**
     * Send test email
     */
    public function sendTestEmail($toEmail, $message = null)
    {
        $message = $message ?? 'This is a test email from ' . config('app.name', 'Hospital') . ' system.';
        
        try {
            \Mail::raw($message, function ($mail) use ($toEmail) {
                $mail->to($toEmail)
                     ->subject('Test Email from ' . config('app.name', 'Hospital'));
            });
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to send test email: ' . $e->getMessage());
        }
    }
}
