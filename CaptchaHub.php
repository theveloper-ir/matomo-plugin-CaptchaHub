<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CaptchaHub;

use Piwik\Plugins\Login\FormLogin;
use Piwik\Plugins\CaptchaHub\Controller as CaptchaHubController;
use Piwik\Common;
use Piwik\Db;

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


    public function registerEvents()
    {
        return [
            'Request.initAuthenticationObject' => 'addCaptchaFieldToLoginForm',
        ];
    }

    public function addCaptchaFieldToLoginForm()
    {
        $form = new FormLogin();

        if ($this->settings->captchaStatus->getValue() && $form->validate()) {
            $captchaHubController = new CaptchaHubController();
            $captchaHubController->captchaHandle();
        } 
        else  if (isset($_POST['settingValues']['CaptchaHub']) && boolval($_POST['settingValues']['CaptchaHub'][0]['value']))
        {
            $this->addCaptchaToTemplate();
        }
    }

    public function addCaptchaToTemplate()
    {

    }

    private function getTemplateContent($templateAddress)
    {
        $rootPath = PIWIK_INCLUDE_PATH;
        $pluginsPath = $rootPath . '/plugins';
        $path = $pluginsPath . DIRECTORY_SEPARATOR . $templateAddress;

        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            return '';
        }
    }
    
    private function putTemplateContent($templateAddress, $content)
    {
        $rootPath = PIWIK_INCLUDE_PATH;
        $pluginsPath = $rootPath . '/plugins';
        $path = $pluginsPath . DIRECTORY_SEPARATOR . $templateAddress;

        $bytesWritten = file_put_contents($path, $content);

        if ($bytesWritten === false) {
            throw new \Exception('Failed to write to file: ' . $path);
        }

        return $bytesWritten;
    }
    
    private function putHtaccessContent($captchaProvider)
    {
        $rootPath = PIWIK_INCLUDE_PATH;
        $path = $rootPath . DIRECTORY_SEPARATOR . ".htaccess";

        if (!file_exists($path)) {
            file_put_contents($path, '');
        }

        $htaccessContent = file_get_contents($path);

        //return if captcha Provider equal htaccess Content
        $pattern = "/#{$captchaProvider} Headers.*?#End {$captchaProvider} Headers/s";
        if(preg_match($pattern, $htaccessContent))
            return;

        // Remove all previous captcha headers
        $htaccessContent = $this->removeCaptchaHeaders($htaccessContent);

        // Add new captcha headers based on provider
        switch ($captchaProvider) 
        {
            case 'googleRecaptcha':
                $htaccessContent = $this->addGoogleHeaders($htaccessContent);
                break;
        }

        $bytesWritten = file_put_contents($path, $htaccessContent);

        if ($bytesWritten === false) {
            throw new \Exception('Failed to write to file: ' . $path);
        }

        return $bytesWritten;
    }

    private function removeCaptchaHeaders($content)
    {
        // Define patterns for different captcha headers
        $patterns = [
            '/#googleRecaptcha Headers.*?#End googleRecaptcha Headers/s',
        ];

        // Remove all matching patterns
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        return $content;
    }

}
