<?php

/**
 * Class CountryCheck_Model_SpamPrevention
 *
 * @extend XenForo_Model_SpamPrevention
 */
class CountryCheck_Model_SpamPrevention extends XFCP_CountryCheck_Model_SpamPrevention
{
    public function _allowRegistration(array $user, Zend_Controller_Request_Http $request)
    {
        $decisions = parent::_allowRegistration($user, $request);
        $decisions[] = $this->_checkIpCountryResult($user, $request);

        return $decisions;
    }

    protected function _checkIpCountryResult(array $user, Zend_Controller_Request_Http $request)
    {
        $decision = XenForo_Model_SpamPrevention::RESULT_ALLOWED;

        if ($this->_isEnabled()) {
            $countryCode = $this->_getGeoIpApiResponse($user);

            $whitelistCountries = $this->_getWhitelist();
            $blacklistCountries = $this->_getBlacklist();

            if ($countryCode) {
                if (in_array($countryCode, $whitelistCountries)) {
                    $decision = XenForo_Model_SpamPrevention::RESULT_ALLOWED;
                } else if (in_array($countryCode, $blacklistCountries)) {
                    $decision = XenForo_Model_SpamPrevention::RESULT_DENIED;

                    $this->_resultDetails[] = array(
                        'phrase' => 'countrycheck_blacklisted_x',
                        'data' => array(
                            'country' => $countryCode
                        )
                    );
                } else {
                    $decision = XenForo_Model_SpamPrevention::RESULT_MODERATED;

                    $this->_resultDetails[] = array(
                        'phrase' => 'countrycheck_needsmanualcheck_x',
                        'data' => array(
                            'country' => $countryCode
                        )
                    );
                }
            }
        }

        return $decision;
    }

    protected function _getGeoIpApiUrl(array $user)
    {
        $base_url = "http://api.ipstack.com/";
        $api_key = $this->_getApiKey();

        return $base_url . $user['ip'] . '?access_key=' . $api_key;
    }

    protected function _getGeoIpApiResponse(array $user)
    {
        if (!$user['ip']) {
            return false;
        }

        $apiUrl = $this->_getGeoIpApiUrl($user);
        $client = XenForo_Helper_Http::getClient($apiUrl);
        try
        {
            $response = $client->request('GET');
            $body = $response->getBody();

            return $this->_decodeGoeIpApiData($body);
        }
        catch (Zend_Http_Exception $e)
        {
            //XenForo_Error::logException($e, false);
            return false;
        }
    }

    protected function _decodeGoeIpApiData($data)
    {
        try
        {
            $response = json_decode($data, true);
            if (is_array($response) && isset($response['country_code']) && strlen($response['country_code']) === 2) {
                return strtoupper($response['country_code']);
            } else {
                return false;
            }
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    protected function _isEnabled()
    {
        return !!$this->_getCheckCountryOption('countryCheckEnabled');
    }

    protected function _getApiKey()
    {
        return $this->_getCheckCountryOption('countryCheckApiKey');
    }

    protected function _getWhitelist()
    {
        $whitelist = $this->_getCheckCountryOption('countryCheckWhitelist');
        $whitelist = explode(',', $whitelist);
        $whitelist = array_map('trim', $whitelist);
        $whitelist = array_map('strtoupper', $whitelist);
        $whitelist = array_filter($whitelist);

        return $whitelist;
    }

    protected function _getBlacklist()
    {
        $blacklist = $this->_getCheckCountryOption('countryCheckBlacklist');
        $blacklist = explode(',', $blacklist);
        $blacklist = array_map('trim', $blacklist);
        $blacklist = array_map('strtoupper', $blacklist);
        $blacklist = array_filter($blacklist);

        return $blacklist;
    }

    /**
     * Fetches the options for the country check system
     *
     * @param $optId string option id
     * @return mixed
     */
    protected function _getCheckCountryOption($optId)
    {
        return XenForo_Application::getOptions()->{$optId};
    }
}
