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

    /**
     * Check if the captcha response token is valid.
     *
     * @param string $responseToken
     * @return bool
     */
    private function isCaptchaValid($responseToken)
    {
        $turnstileVerifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $response = $this->makeHttpRequest($turnstileVerifyUrl, [
            'secret' => $this->secretKey,
            'response' => $responseToken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] // ارسال IP کاربر به Cloudflare
        ]);

        return !empty($response['success']) && $response['success'] === true;
    }

    /**
     * Make an HTTP request and return the response as an array.
     *
     * @param string $url
     * @param array $params
     * @return array
     */
    private function makeHttpRequest($url, array $params)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false || $httpCode !== 200) {
            throw new \RuntimeException('Failed to make HTTP request. HTTP Code: ' . $httpCode);
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
