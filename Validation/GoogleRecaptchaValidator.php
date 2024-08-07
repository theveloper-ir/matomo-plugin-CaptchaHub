<?php

namespace Piwik\Plugins\CaptchaHub\Validation;


use Piwik\Plugins\CaptchaHub\SystemSettings;

class GoogleRecaptchaValidator
{
    private $secretKey;

    public function __construct()
    {
        $settings = new SystemSettings();
        $this->secretKey = $settings->secretKey->getValue();
    }
}
