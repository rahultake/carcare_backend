<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    function setting($key = null, $default = null) {
        if (is_array($key)) {
            return new class($key) {
                private $settings;
                
                public function __construct($settings) {
                    $this->settings = $settings;
                }
                
                public function save() {
                    foreach ($this->settings as $k => $v) {
                        Setting::updateOrCreate(
                            ['key' => $k],
                            ['value' => is_array($v) || is_object($v) ? json_encode($v) : $v]
                        );
                    }
                }
            };
        }

        if (is_null($key)) {
            return null;
        }

        $setting = Setting::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        // Check if value is JSON (e.g. for structured settings)
        $value = $setting->value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}
