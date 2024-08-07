<?php


namespace Piwik\Plugins\CaptchaHub;


use Piwik\Plugin\ControllerAdmin as BaseController;


class Controller extends BaseController
{
    private $settings;

    public function __construct()
    {
        $this->settings = new SystemSettings();
    }

   public function captchaHandle()
   {
        
   }
}
