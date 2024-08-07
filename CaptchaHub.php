<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CaptchaHub;

class CaptchaHub extends \Piwik\Plugin
{
    private $settings;
    protected $pluginName = 'CaptchaHub';

    const START_CAPTCHA = '<!-- Start Captcha -->';
    const END_CAPTCHA = '<!-- End Captcha -->';
    const GOOGLE_RECAPTCHA_TAGS = 'class="g-recaptcha"';
    const CLOUDFLARE_TURNSTILE_TAGS = 'class="cf-turnstile"';


    public function __construct()
    {
        $this->settings = new SystemSettings();
    }

}
