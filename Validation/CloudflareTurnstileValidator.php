<?php

namespace Piwik\Plugins\CaptchaHub\Validation;

use Piwik\Piwik;
use Piwik\Plugins\CaptchaHub\SystemSettings;

class CloudflareTurnstileValidator
{
    private $secretKey;

    public function __construct()
    {
        $settings = new SystemSettings();
        $this->secretKey = $settings->secretKey->getValue();
    }

    /**
     * Validate the captcha response token.
     *
     * @param string $responseToken
     * @return bool
     */
    public function validate($responseToken)
    {
        if (empty($responseToken)) {
            throw new \InvalidArgumentException(Piwik::translate('CaptchaHub_CaptchaNotChecked'));
        }

        return $this->isCaptchaValid($responseToken);
    }
    
}
