<?php
$version['name']            = "DomainNameApi";
$version['api_version']     = "2.1.20"; // API version of the registrar
$version['date']            = "2025-08-07"; // Last modification date
$version['version']         = "1.0.8"; // Version released for HostFact
$version['autorenew']       = true; // AutoRenew is default?  true | false
$version['handle_support']  = true; // Handles are supported? true | false
$version['cancel_direct']   = false; // Possible to terminate domains immediately?  true | false
$version['cancel_expire']   = true; // Possible to stop auto-renew for domains? true | false

// Information for customer (will be showed at registrar-show-page)
$version['dev_logo']		= 'https://cdn.domainnameapi.com/design/images/logo-domain-name-api.png'; // URL to your logo
$version['dev_author']		= 'Domain Name Api'; // Your companyname
$version['dev_website']		= 'https://www.domainnameapi.com'; // URL website
$version['dev_email']		= 'support@domainnameapi.com'; // Your e-mailaddress for support questions
$version['dev_phone']		= '+90 (0262) 325 92 22'; // Your phone number for support questions

// Does this registrar integration support functions related to domains?
$version['domain_support']  = true;
// Does this registrar integration support functions related to SSL certificates?
$version['ssl_support']   	= false;

// Does this registrar integration support functions related to DNS management?
$version['dns_management_support']   	= false;
// Does this registrar integration support DNS templates?
$version['dns_templates_support']       = false;
// Does this registrar integration support DNS records?
$version['dns_records_support']         = false;
