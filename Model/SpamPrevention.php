<?php

/**
 * Class CountryCheck_Model_SpamPrevention
 *
 * @extend XenForo_Model_SpamPrevention
 */
class CountryCheck_Model_SpamPrevention extends XFCP_CountryCheck_Model_SpamPrevention
{
    const API_NAME = 'ip-api';

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
            $countryCode = $this->_getUserCountryCode($user);

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

    protected function _getUserCountryCode(array $user)
    {
        if (!(isset($user['ip']) && !empty($user['ip']))) {
            return false;
        }

        $ip = $user['ip'];

        switch (self::API_NAME) {
            case 'ipstack':
                return $this->_getIpstackResponse($ip);
            case 'ip-api':
                return $this->_getIpApiResponse($ip);
        }
    }

    protected function _getIpstackResponse(string $ip)
    {
        $base_url = "http://api.ipstack.com/";
        $api_key = $this->_getApiKey();
        $apiUrl = $base_url . $ip . '?access_key=' . $api_key;

        $client = XenForo_Helper_Http::getClient($apiUrl);
        
        try
        {
            $response = $client->request('GET');
            $body = $response->getBody();

            $response = @json_decode($body, true);
            
            if (is_array($response) && isset($response['country_code']) && strlen($response['country_code']) === 2) {
                return strtoupper($response['country_code']);
            } else {
                return false;
            }
        }
        catch (Zend_Http_Exception $e)
        {
            //XenForo_Error::logException($e, false);
            return false;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
    
    protected function _getIpApiResponse(string $ip)
    {
        // TODO: Add support for PRO accounts
        // $api_key = $this->_getApiKey();
        $base_url = "http://ip-api.com/json/";
        $apiUrl = $base_url . $ip . '?fields=countryCode,status,message';

        $client = XenForo_Helper_Http::getClient($apiUrl);
        
        try
        {
            $response = $client->request('GET');
            $body = $response->getBody();

            $response = @json_decode($body, true);
            
            if (is_array($response) && isset($response['countryCode']) && strlen($response['countryCode']) === 2) {
                return strtoupper($response['countryCode']);
            } else {
                return false;
            }
        }
        catch (Zend_Http_Exception $e)
        {
            //XenForo_Error::logException($e, false);
            return false;
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
