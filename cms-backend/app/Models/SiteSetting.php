<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the single site setting record or create one if it doesn't exist.
     */
    public static function getSettings()
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'site_title' => 'My CMS Site',
                'language' => 'en',
                'timezone' => 'UTC',
                'mail_mailer' => 'smtp',
            ]
        );
    }
}
