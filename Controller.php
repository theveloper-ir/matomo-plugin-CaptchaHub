<?php


namespace Piwik\Plugins\CaptchaHub;


use Piwik\Plugin\ControllerAdmin as BaseController;
use Piwik\Plugins\CaptchaHub\Validation\GoogleRecaptchaValidator;


class Controller extends BaseController
{
    private $settings;

    public function __construct()
    {
        $this->settings = new SystemSettings();
    }

   public function captchaHandle()
   {
    switch($this->settings->captchaProvider->getValue())
    {
        case "googleRecaptcha":

            (new GoogleRecaptchaValidator)->validate($_POST['g-recaptcha-response']);

            break;         
    }
   }
}
