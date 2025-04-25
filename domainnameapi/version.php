<?php
$version['name']            = "YourName";
$version['api_version']     = "YourAPIVersion";
$version['date']            = "2016-01-01"; // Last modification date
$version['version']         = "3.6"; // Version released for HostFact
$version['autorenew']       = true; // AutoRenew is default?  true | false
$version['handle_support']  = true; // Handles are supported? true | false
$version['cancel_direct']   = true; // Possible to terminate domains immediately?  true | false
$version['cancel_expire']   = true; // Possible to stop auto-renew for domains? true | false

// Information for customer (will be showed at registrar-show-page)
$version['dev_logo']		= ''; // URL to your logo
$version['dev_author']		= 'Your Company Name'; // Your companyname
$version['dev_website']		= ''; // URL website
$version['dev_email']		= ''; // Your e-mailaddress for support questions
//$version['dev_phone']		= ''; // Your phone number for support questions

// Does this registrar integration support functions related to domains?
$version['domain_support']  = true;
// Does this registrar integration support functions related to SSL certificates?
$version['ssl_support']   	= true;

// Does this registrar integration support functions related to DNS management?
$version['dns_management_support']   	= true;
// Does this registrar integration support DNS templates?
$version['dns_templates_support']       = true;
// Does this registrar integration support DNS records?
$version['dns_records_support']         = false;
?>