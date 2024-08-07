<?php


namespace Piwik\Plugins\CaptchaHub;


use Piwik\Piwik;
use Piwik\Settings\FieldConfig;


class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    public $captchaStatus;
    public $captchaProvider;
    public $siteKey;
    public $secretKey;


    protected function init()
    {
        $this->captchaStatus = $this->createCaptchaStatusSetting();
        $this->captchaProvider = $this->createCaptchaProviderSetting();
        $this->siteKey = $this->createSiteKeySetting();
        $this->secretKey = $this->createSecretKeySetting();
    }


    private function createCaptchaStatusSetting()
    {
        return $this->makeSetting('captchaStatus', $default = false, FieldConfig::TYPE_BOOL, function (FieldConfig $field) {
            $field->title = 'Captcha Status';
            $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
        });
    }


    private function createCaptchaProviderSetting()
    {
        return $this->makeSetting('captchaProvider', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'CAPTCHA Provider';
            $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
            $field->availableValues = [
                ''=>'Select Provider',
                'googleRecaptcha' => 'Google reCAPTCHA',
                'cloudflareTurnstile' => 'Cloudflare Turnstile'
            ];
            $field->validate = function($value) use ($field){
                if($this->captchaStatus->getValue() && empty($value))
                {
                    throw new \Exception(Piwik::translate('General_ValidatorErrorEmptyValue'));
                }
            };
            
        });
    }


    private function createSiteKeySetting()
    {
        return $this->makeSetting('siteKey', $default ='', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Site Key Value';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;

            $field->validate = function($value){

                if($this->captchaStatus->getValue() && empty($value))
                {
                    throw new \Exception(Piwik::translate('General_ValidatorErrorEmptyValue'));
                }
                
                switch($this->captchaProvider->getValue())
                {
                    case 'googleRecaptcha':
                        if(!preg_match('/^[a-zA-Z0-9_-]{40}$/', $value))
                            throw new \Exception('Invalid Site Key for Google reCAPTCHA.');
                        break;
                    
                    case 'cloudflareTurnstile':
                        if(!preg_match('/^[a-zA-Z0-9_-]{20,64}$/', $value))
                            throw new \Exception('Invalid Site Key for Cloudflare Turnstile.');
                        break;
                }
            };

        });
        
    }


    private function createSecretKeySetting()
    {
        return $this->makeSetting('secretKey', $default = '', FieldConfig::TYPE_STRING, function (FieldConfig $field) {
            $field->title = 'Secret Key Value';
            $field->uiControl = FieldConfig::UI_CONTROL_TEXT;

            $field->validate = function($value){
                
               if($this->captchaStatus->getValue() && empty($value))
                {
                    throw new \Exception(Piwik::translate('General_ValidatorErrorEmptyValue'));
                }

                switch ($this->captchaProvider->getValue()) {
                    case 'googleRecaptcha':
                        if (!preg_match('/^[a-zA-Z0-9_-]{40}$/', $value))
                            throw new \Exception('Invalid Secret Key for Google reCAPTCHA.');
                        break;
                    
                    case 'cloudflareTurnstile':
                        if (!preg_match('/^[a-zA-Z0-9_-]{20,64}$/', $value))
                            throw new \Exception('Invalid Secret Key for Cloudflare Turnstile.');
                        break;
                    
                    default:
                        throw new \Exception('Unknown CAPTCHA provider selected.');
                }
            };

        });
    }

}
