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
use Piwik\Plugin;

class CaptchaHub extends Plugin
{
    private $settings;
    protected $pluginName = 'CaptchaHub';

    const START_CAPTCHA = '<!-- Start Captcha -->';
    const END_CAPTCHA = '<!-- End Captcha -->';
    const GOOGLE_RECAPTCHA_TAGS = 'class="g-recaptcha"';
    const CLOUDFLARE_TURNSTILE_TAGS = 'class="cf-turnstile"';
    const LOGIN_TEMPLATE_PATH = PIWIK_INCLUDE_PATH . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR ."Login/templates/login.twig";

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
        
        if ($this->settings->captchaStatus->getValue() && $form->validate()) 
        {
            $captchaHubController = new CaptchaHubController();
            $captchaHubController->captchaHandle();
        } 
        else if(isset($_POST['settingValues']['CaptchaHub']) && $_POST['settingValues']['CaptchaHub'][0]['value'] == 0)
        {
            $htaccessContent = $this->getHtaccessContent();
    
            if($this->removeCaptchaHeaders($htaccessContent) !== false)
                $this->removeTextFromFile(self::LOGIN_TEMPLATE_PATH, self::START_CAPTCHA, self::END_CAPTCHA);
        }
        else if (isset($_POST['settingValues']['CaptchaHub']) && $_POST['settingValues']['CaptchaHub'][0]['value'] == 1)
            $this->addCaptchaToTemplate();
    }

    public function addCaptchaToTemplate()
    {
        $provider = $_POST['settingValues']['CaptchaHub'][1]['value'];

        switch ($provider) 
        {
            case 'googleRecaptcha':
                $this->addCaptchaToTemplateHelper(
                    'https://www.google.com/recaptcha/api.js',
                    self::GOOGLE_RECAPTCHA_TAGS
                );
                break;
                
            case 'cloudflareTurnstile':
                $this->addCaptchaToTemplateHelper(
                    'https://challenges.cloudflare.com/turnstile/v0/api.js',
                    self::CLOUDFLARE_TURNSTILE_TAGS
                );
                break;
        }
    }

    private function getTemplateContent($filePath)
    {
        if (file_exists($filePath)) 
            return file_get_contents($filePath);
        else 
            throw new \Exception("File {$filePath} Not Found ");
    }
    
    private function putTemplateContent($filePath, $content)
    {
        if (!file_exists($filePath)) 
            throw new \Exception("File {$filePath} Not Found ");

        $bytesWritten = file_put_contents($filePath, $content);

        if ($bytesWritten === false)
            throw new \Exception('Failed to write on file: ' . $filePath);

        return $bytesWritten;
    }
    
    private function putHtaccessContent($htaccessContent)
    {
        $filePath = PIWIK_INCLUDE_PATH . DIRECTORY_SEPARATOR . ".htaccess";

        if (!file_exists($filePath)) 
            throw new \Exception("File {$filePath} Not Found ");

        $bytesWritten = file_put_contents($filePath, $htaccessContent);

        if ($bytesWritten === false) 
            throw new \Exception('Failed to write to file: ' . $filePath);

        return $bytesWritten;
    }

    private function removeCaptchaHeaders($content)
    {
        // Define patterns for different captcha headers
        $patterns = [
            '/#googleRecaptcha Headers.*?#End googleRecaptcha Headers/s',
            '/#cloudflareTurnstile Headers.*?#End cloudflareTurnstile Headers/s'
        ];

        // Remove all matching patterns
        foreach ($patterns as $pattern) 
            $content = trim(preg_replace($pattern, '', $content));

        if($this->putHtaccessContent($content) !== false)
            return $content;
    }

    private function addGoogleHeaders($content)
    {
        $googleHeaders = <<<EOD
        #googleRecaptcha Headers
        Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://www.google.com https://www.gstatic.com 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com; frame-src 'self' https://www.google.com;"
        #End googleRecaptcha Headers
        EOD;

        return trim($content) . PHP_EOL . $googleHeaders;
    }

    private function addCloudflareHeaders($content)
    {
        $cloudflareHeaders = <<<EOD
        #cloudflareTurnstile Headers
        Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://challenges.cloudflare.com 'unsafe-inline' 'unsafe-eval'; style-src 'self' https://fonts.googleapis.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://fonts.gstatic.com; frame-src 'self' https://challenges.cloudflare.com; connect-src 'self' https://challenges.cloudflare.com;"
        #End cloudflareTurnstile Headers
        EOD;
            return trim($content) . PHP_EOL . $cloudflareHeaders;
    }

    private function addCaptchaToTemplateHelper($scriptSrc, $captchaClass)
    {
        if(!$this->settings->captchaStatus->getValue())
        {
            $db = Db::get();
            $db->update(Common::prefixTable('plugin_setting'),['setting_value'=>1],'plugin_name = "CaptchaHub" and setting_name = "captchaStatus"');
        }

        $siteKey = isset($_POST['settingValues']['CaptchaHub'][2]['value'])?$_POST['settingValues']['CaptchaHub'][2]['value']:$this->settings->siteKey->getValue();
        $templateContent = $this->getTemplateContent(self::LOGIN_TEMPLATE_PATH);

        $containsStartCaptcha = strpos($templateContent, self::START_CAPTCHA) !== false;
        $containsEndCaptcha = strpos($templateContent, self::END_CAPTCHA) !== false;
        $containsCaptchaTag = strpos($templateContent, $captchaClass) !== false;

        $captchaHtml = PHP_EOL.'
        ' . self::START_CAPTCHA . '
        <div class="row">
            <div class="col s12 input-field">
                <script src="' . $scriptSrc . '" async defer></script>
                <div  '. $captchaClass .' data-sitekey="' . $siteKey . '" tabindex="30"></div>
            </div>
        </div>
        ' . self::END_CAPTCHA . PHP_EOL;


        if (!$containsStartCaptcha && !$containsEndCaptcha) {

            $search = '<label for="login_form_password"><i class="icon-locked icon"></i> {{ \'General_Password\'|translate }}</label>
                        </div>
                    </div>';

            $position = strpos($templateContent, $search);

            if ($position !== false) 
            {
                
                $position += strlen($search);

                $newFileContent = substr($templateContent, 0, $position) . $captchaHtml . substr($templateContent, $position);

                $this->putTemplateContent(self::LOGIN_TEMPLATE_PATH, $newFileContent);
                $this->setHtaccessProvider(isset($_POST['settingValues']['CaptchaHub'][1]['value'])?$_POST['settingValues']['CaptchaHub'][1]['value']:$this->settings->captchaProvider->getValue());

            }
        } 
        else if (!$containsCaptchaTag || $siteKey != $this->settings->siteKey->getValue()) 
        {

            $startCaptchaPos = strpos($templateContent, self::START_CAPTCHA);
            $endCaptchaPos = strpos($templateContent, self::END_CAPTCHA);

            $newFileContent = substr($templateContent, 0, $startCaptchaPos) . $captchaHtml . substr($templateContent, $endCaptchaPos + strlen(self::END_CAPTCHA));
            
            $this->putTemplateContent(self::LOGIN_TEMPLATE_PATH, $newFileContent);
            $this->setHtaccessProvider($_POST['settingValues']['CaptchaHub'][1]['value']);

        }
    }
    
    public function uninstall()
    {
        $this->deactivate();
    }

    public function activate()
    {
        if($this->settings->captchaStatus->getValue())
        {
            $provider = $this->settings->captchaProvider->getValue();

            switch ($provider) 
            {
                case 'googleRecaptcha':
                    $this->addCaptchaToTemplateHelper(
                        'https://www.google.com/recaptcha/api.js',
                        self::GOOGLE_RECAPTCHA_TAGS
                    );
                    break;
                case 'cloudflareTurnstile':
                    $this->addCaptchaToTemplateHelper(
                        'https://challenges.cloudflare.com/turnstile/v0/api.js',
                        self::CLOUDFLARE_TURNSTILE_TAGS
                    );
                    break;
            }
        }
    }

    public function deactivate()
    {
        $htaccessContent = $this->getHtaccessContent();
        
        if($this->removeCaptchaHeaders($htaccessContent) !== false)
        {
            if($this->removeTextFromFile(self::LOGIN_TEMPLATE_PATH, self::START_CAPTCHA, self::END_CAPTCHA))
            {
                $db =  Db::get();
                $db->update(Common::prefixTable('plugin_setting'),['setting_value'=>0],'plugin_name = "CaptchaHub" and setting_name = "captchaStatus"');
            }
        }
    }
    
    private function removeTextFromFile($filePath, $startString, $endString) 
    {
        if (!file_exists($filePath)) 
            throw new \Exception("File {$filePath} Not Found ");

        $content = file_get_contents($filePath);

        if ($content === false) 
            throw new \Exception('Failed to Open File : ' . $filePath);

        //Change Pattern For Get All Spaces And Convert To PHP_EOL
        $pattern = '/(\r?\n)*' . preg_quote($startString, '/') . '.*?' . preg_quote($endString, '/') . '(\r?\n)*/s';


        $newContent = preg_replace($pattern, PHP_EOL, $content);

        $result = file_put_contents($filePath, $newContent);
        
        if ($result === false) 
            throw new \Exception('Failed to write on file: ' . $filePath);  

        echo "Text Deleted Successfully";
        return true;
    }

    private function getHtaccessContent()
    {
        $filePath = PIWIK_INCLUDE_PATH . DIRECTORY_SEPARATOR . ".htaccess";

        if (file_exists($filePath)) 
            return file_get_contents($filePath);
        else 
        {
            file_put_contents($filePath,'');
            return '';
        }
    }

    private function setHtaccessProvider($captchaProvider)
    {
        $htaccessContent = $this->getHtaccessContent();

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
                
            case 'cloudflareTurnstile':
                $htaccessContent = $this->addCloudflareHeaders($htaccessContent);
                break;
        }

        $this->putHtaccessContent($htaccessContent);
    }
}