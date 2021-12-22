# Country check XenForo 1.x Add-on
A XenForo 1.x Add-on that checks the user country on registration, 
user that tries to register from a not whitelisted country will be sent to the moderation queue. 
Any user that try to register from a blacklisted country will be denied from complete the account creation.

## Geo IP
Geo IP is accomplished with the favor of [ip-api.com](https://ip-api.com) 

## Configuration
You can customize the options of this add-on from the admin of XenForo (country check options group):

- Enabled: Enable/Disable country check on registration
- API key: (Optional) Enter here your ip-api.com API key (Not used at this moment)
- Whitelist: Any user that register from a whitelisted country will be automatically approved (if he pass every other spam prevention system that you have enabled on your system)
- Blacklist: Any user that register from a blacklisted country will be automatically denied from complete registration

## Installation
1) Copy the contents of this repository into `library/CountryCheck` relative to your XenForo installation. 
2) Import `addon-countryCheck.xml` from the admin of XenForo > Add-ons > Install Add-on
