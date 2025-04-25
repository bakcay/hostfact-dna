<?php
require_once("3rdparty/domain/IRegistrar.php");
require_once("3rdparty/domain/standardfunctions.php");

/**
 * -------------------------------------------------------------------------------------
 * YourName - IRegistrar
 * 
 * Author		: 		
 * Copyright	: 		
 * Version 		:	
 * 
 * CHANGE LOG:
 * -------------------------------------------------------------------------------------
 *  yyyy-mm-yy		Author 		Initial version
 * -------------------------------------------------------------------------------------
 */

class YourName implements IRegistrar
{
    public $User;
    public $Password;
    
    public $Error;
    public $Warning;
    public $Success;

    public $Period = 1;
    public $registrarHandles = array();
    
    private $ClassName;
    
	function __construct(){	
		
		$this->ClassName = __CLASS__;
		
		$this->Error = array();
		$this->Warning = array();
		$this->Success = array();		
	}

	/**
	 * Check whether a domain is already regestered or not. 
	 * 
	 * @param 	string	 $domain	The name of the domain that needs to be checked.
	 * @return 	boolean 			True if free, False if not free, False and $this->Error[] in case of error.
	 */
	function checkDomain($domain) {
		$my_result = $yourproxy->checkDomain($domain);
		
		if($my_result == 'free'){
			return true;
		}elseif($my_result == 'active'){
			return false;
		}else{
			$this->Error[] = 'Checking availability for domain TLD is not supported via the API.';
			return false;
		}
	}


//		/**
//		 * EXAMPLE IF A FUNCTION IS NOT SUPPORTED
//		 * Check whether a domain is already regestered or not. 
//		 * 
//		 * @param 	string	 $domain	The name of the domain that needs to be checked.
//		 * @return 	boolean 			True if free, False if not free, False and $this->Error[] in case of error.
//		 */
//		function checkDomain($domain) {
//			$this->Warning[] = 'YourName:  checking availability for domain '.$domain.' cannot be checked via the API.';
//
//			if($this->caller == 'register'){
//				return true;
//			}elseif($this->caller == 'transfer'){
//				return false;
//			}
//		}

	
	/**
	 * Register a new domain
	 * 
	 * @param 	string	$domain			The domainname that needs to be registered.
	 * @param 	array	$nameservers	The nameservers for the new domain.
	 * @param 	array	$whois			The customer information for the domain's whois information.
	 * @return 	bool					True on success; False otherwise.
	 */
	function registerDomain($domain, $nameservers = array(), $whois = null) {

        /** if you use DNS management, the following variables are also available
         * $this->DNSTemplateID
         * $this->DNSTemplateName
         */
         
		/**
		 * Step 1) obtain an owner handle
		 */
		$ownerHandle = "";
		// Check if a registrar-specific ownerhandle for this domain already exists.
		if (isset($whois->ownerRegistrarHandles[$this->ClassName]))
		{
			$ownerHandle = $whois->ownerRegistrarHandles[$this->ClassName];
		}
		// If not, check if WHOIS-data for owner contact is available to search or create new handle
		elseif($whois->ownerSurName != "")
		{
			// Search for existing handle, based on WHOIS data
			$ownerHandle = $this->getContactHandle($whois, HANDLE_OWNER);
			
			// If no existing handle is found, create new handle
			if ($ownerHandle == "") 
			{
				// Try to create new handle. In case of failure, quit function
				if(!$ownerHandle = $this->createContact($whois, HANDLE_OWNER))
				{
				    return false;
				}			
			}
			
			// If a new handle is created or found, store in array. HostFact will store this data, which will result in faster registration next time.
			$this->registrarHandles['owner'] = $ownerHandle;
		} else {
			// If no handle can be created, because data is missing, quit function
			$this->Error[] = sprintf("YourName: No domain owner contact given for domain '%s'.", $domain);
			return false;	
		}
		
		/**
		 * Step 2) obtain an admin handle
		 */
		$adminHandle = "";
		// Check if a registrar-specific adminhandle for this domain already exists.
		if (isset($whois->adminRegistrarHandles[$this->ClassName]))
		{
			$adminHandle = $whois->adminRegistrarHandles[$this->ClassName];
		}
		// If not, check if WHOIS-data for admin contact is available to search or create new handle
		elseif($whois->adminSurName != "")
		{
			// Search for existing handle, based on WHOIS data
			$adminHandle = $this->getContactHandle($whois, HANDLE_ADMIN);
			
			// If no existing handle is found, create new handle
			if ($adminHandle == "") 
			{
				// Try to create new handle. In case of failure, quit function
				if(!$adminHandle = $this->createContact($whois, HANDLE_ADMIN))
				{
				    return false;
				}			
			}
			
			// If a new handle is created or found, store in array. HostFact will store this data, which will result in faster registration next time.
			$this->registrarHandles['admin'] = $adminHandle;
		} else {
			// If no handle can be created, because data is missing, quit function
			$this->Error[] = sprintf("YourName: No domain admin contact given for domain '%s'.", $domain);
			return false;	
		}
		
		/**
		 * Step 3) obtain a tech handle
		 */
		$techHandle = "";
		// Check if a registrar-specific techhandle for this domain already exists.
		if (isset($whois->techRegistrarHandles[$this->ClassName]))
		{
			$techHandle = $whois->techRegistrarHandles[$this->ClassName];
		}
		// If not, check if WHOIS-data for tech contact is available to search or create new handle
		elseif($whois->techSurName != "")
		{
			// Search for existing handle, based on WHOIS data
			$techHandle = $this->getContactHandle($whois, HANDLE_TECH);
			
			// If no existing handle is found, create new handle
			if ($techHandle == "") 
			{
				// Try to create new handle. In case of failure, quit function
				if(!$techHandle = $this->createContact($whois, HANDLE_TECH))
				{
				    return false;
				}			
			}
			
			// If a new handle is created or found, store in array. HostFact will store this data, which will result in faster registration next time.
			$this->registrarHandles['tech'] = $techHandle;
		} else {
			// If no handle can be created, because data is missing, quit function
			$this->Error[] = sprintf("YourName: No domain tech contact given for domain '%s'.", $domain);
			return false;	
		}
		
		/**
		 * Step 4) obtain nameserver handles
		 */
		// If your system uses nameserver groups or handles, you can check the $nameserver array and match these hostnames with your own system.
	
		/**
		 * Step 5) check your own default settings
		 */
 		// Determine period for registration in years, based on your TLD properties. 
		// $this->Period is also used in HostFact, for determining the renewal date.
 		$this->Period = 1;
 		
 		/**
		 * Step 6) register domain
		 */
		// Start registering the domain, you can use $domain, $ownerHandle, $adminHandle, $techHandle, $nameservers
		$response 	= true;
		$error_msg 	= '';
		
		/**
		 * Step 7) provide feedback to HostFact
		 */
		if($response === true)
		{
			// Registration is succesfull
			return true;
		}
		else
		{
			// Registration failed
			$this->Error[] 	= sprintf("YourName: Error while registering '%s': ", $error_msg);
			return false;			
		}
	}
	
	/**
	 * Transfer a domain to the given user.
	 * 
	 * @param 	string 	$domain			The demainname that needs to be transfered.
	 * @param 	array	$nameservers	The nameservers for the tranfered domain.
	 * @param 	array	$whois			The contact information for the new owner, admin, tech and billing contact.
	 * @return 	bool					True on success; False otherwise;
	 */
	function transferDomain($domain, $nameservers = array(), $whois = null, $authcode = "") {

        /** if you use DNS management, the following variables are also available
         * $this->DNSTemplateID
         * $this->DNSTemplateName
         */
         
		/**
		 * Step 1) obtain an owner handle
		 */
		$ownerHandle = "";
		// Check if a registrar-specific ownerhandle for this domain already exists.
		if (isset($whois->ownerRegistrarHandles[$this->ClassName]))
		{
			$ownerHandle = $whois->ownerRegistrarHandles[$this->ClassName];
		}
		// If not, check if WHOIS-data for owner contact is available to search or create new handle
		elseif($whois->ownerSurName != "")
		{
			// Search for existing handle, based on WHOIS data
			$ownerHandle = $this->getContactHandle($whois, HANDLE_OWNER);
			
			// If no existing handle is found, create new handle
			if ($ownerHandle == "") 
			{
				// Try to create new handle. In case of failure, quit function
				if(!$ownerHandle = $this->createContact($whois, HANDLE_OWNER))
				{
				    return false;
				}			
			}
			
			// If a new handle is created or found, store in array. HostFact will store this data, which will result in faster transfer next time.
			$this->registrarHandles['owner'] = $ownerHandle;
		} else {
			// If no handle can be created, because data is missing, quit function
			$this->Error[] = sprintf("YourName: No domain owner contact given for domain '%s'.", $domain);
			return false;	
		}
		
		/**
		 * Step 2) obtain an admin handle
		 */
		$adminHandle = "";
		// Check if a registrar-specific adminhandle for this domain already exists.
		if (isset($whois->adminRegistrarHandles[$this->ClassName]))
		{
			$adminHandle = $whois->adminRegistrarHandles[$this->ClassName];
		}
		// If not, check if WHOIS-data for admin contact is available to search or create new handle
		elseif($whois->adminSurName != "")
		{
			// Search for existing handle, based on WHOIS data
			$adminHandle = $this->getContactHandle($whois, HANDLE_ADMIN);
			
			// If no existing handle is found, create new handle
			if ($adminHandle == "") 
			{
				// Try to create new handle. In case of failure, quit function
				if(!$adminHandle = $this->createContact($whois, HANDLE_ADMIN))
				{
				    return false;
				}			
			}
			
			// If a new handle is created or found, store in array. HostFact will store this data, which will result in faster transfer next time.
			$this->registrarHandles['admin'] = $adminHandle;
		} else {
			// If no handle can be created, because data is missing, quit function
			$this->Error[] = sprintf("YourName: No domain admin contact given for domain '%s'.", $domain);
			return false;	
		}
		
		/**
		 * Step 3) obtain a tech handle
		 */
		$techHandle = "";
		// Check if a registrar-specific techhandle for this domain already exists.
		if (isset($whois->techRegistrarHandles[$this->ClassName]))
		{
			$techHandle = $whois->techRegistrarHandles[$this->ClassName];
		}
		// If not, check if WHOIS-data for tech contact is available to search or create new handle
		elseif($whois->techSurName != "")
		{
			// Search for existing handle, based on WHOIS data
			$techHandle = $this->getContactHandle($whois, HANDLE_TECH);
			
			// If no existing handle is found, create new handle
			if ($techHandle == "") 
			{
				// Try to create new handle. In case of failure, quit function
				if(!$techHandle = $this->createContact($whois, HANDLE_TECH))
				{
				    return false;
				}			
			}
			
			// If a new handle is created or found, store in array. HostFact will store this data, which will result in faster transfer next time.
			$this->registrarHandles['tech'] = $techHandle;
		} else {
			// If no handle can be created, because data is missing, quit function
			$this->Error[] = sprintf("YourName: No domain tech contact given for domain '%s'.", $domain);
			return false;	
		}
		
		/**
		 * Step 4) obtain nameserver handles
		 */
		// If your system uses nameserver groups or handles, you can check the $nameserver array and match these hostnames with your own system.
	
		/**
		 * Step 5) check your own default settings
		 */
 		// Determine period for transfer in years, based on your TLD properties. 
		// $this->Period is also used in HostFact, for determining the renewal date.
 		$this->Period = 1;
 		
 		/**
		 * Step 6) transfer domain
		 */
		// Start transferring the domain, you can use $domain, $ownerHandle, $adminHandle, $techHandle, $nameservers, $authcode
		$response 	= true;
		$error_msg 	= '';
		
		/**
		 * Step 7) provide feedback to HostFact
		 */
		if($response === true)
		{
			// Transfer is succesfull
			return true;
		}
		else
		{
			// Transfer failed
			$this->Error[] 	= sprintf("YourName: Error while transferring '%s': ", $error_msg);
			return false;			
		}
	}
	
	/**
	 * Delete a domain
	 * 
	 * @param 	string $domain		The name of the domain that you want to delete.
	 * @param 	string $delType     end|now
	 * @return	bool				True if the domain was succesfully removed; False otherwise; 
	 */
	function deleteDomain($domain, $delType = 'end') {

		if($delType == "end"){
			return $this->setDomainAutoRenew($domain, false);	 
		}else{
			
			/**
			 * Step 1) delete domain
			 */
			// Delete the domain, you can use $domain
			$response 	= true;
			$error_msg 	= '';
			
			/**
			 * Step 2) provide feedback to HostFact
			 */
			if($response === true)
			{
				// Deleting is succesfull
				return true;
			}
			else
			{
				// Deleting failed
				$this->Error[] 	= sprintf("YourName: Error while terminating '%s': %s", $domain, $error_msg);
				return false;			
			}
		}
	}
	

	/**
	 * Get all available information of the given domain
	 * 
	 * @param 	mixed 	$domain		The domain for which the information is requested.
	 * @return	array				The array containing all information about the given domain
	 */
	function getDomainInformation($domain) {
     	
		 /**
		 * Step 1) query domain
		 */
		// Query the domain, you can use $domain
		$response 	= true;
		$error_msg 	= '';
		
		$nameservers_array = array('ns1.hostfact.com','ns2.hostfact.com');
		$whois = new whois();
		$whois->ownerHandle = 'ABCD001';
		$whois->adminHandle = 'ABCD001';
		$whois->techHandle 	= 'ABCD002';
		
		$expirationdate 	= '2012-05-01';
		$registrationdate 	= '2000-05-01';
		$authkey			= '1234567';
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response === true)
		{
			// Return array with data
			$response = array(	"Domain" => $domain,
	                        	"Information" => array(	"nameservers" => $nameservers_array, // Array with 1, 2 or 3 elements (hostnames)
	                                               		"whois" => $whois, // Whois object
	                                               		"expiration_date" => $expirationdate,  // Empty or date in yyyy-mm-dd
	                                               		"registration_date" => $registrationdate,  // Empty or date in yyyy-mm-dd
												   		"authkey" => $authkey));
			return $response;
		}
		else
		{
			// No information can be found
			$this->Error[] 	= sprintf("YourName: Error while retreiving information about '%s': %s", $domain, $error_msg);
			return false;			
		}
	}
	
	/**
	 * Get a list of all the domains.
	 * 
	 * @param 	string 	$contactHandle		The handle of a contact, so the list could be filtered (usefull for updating domain whois data)
	 * @return	array						A list of all domains available in the system.
	 */
	function getDomainList($contactHandle = "") {
        /**
		 * Step 1) query domain
		 */
		// Query the domain, you can use $domain
		$response 	= true;
		$error_msg 	= '';
		
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response === true)
		{
			$domain_array = array();
			
			// Loop for all domains:
			
				$nameservers_array = array('ns1.hostfact.com','ns2.hostfact.com');
				$whois = new whois();
				$whois->ownerHandle = 'ABCD001';
				$whois->adminHandle = 'ABCD001';
				$whois->techHandle 	= 'ABCD002';
				
				$expirationdate 	= '2012-05-01';
				$registrationdate 	= '2000-05-01';
				$authkey			= '1234567';
			
				// Return array with data
				$response = array(	"Domain" => $domain,
		                        	"Information" => array(	"nameservers" => $nameservers_array, // Array with 1, 2 or 3 elements (hostnames)
		                                               		"whois" => $whois, // Whois object
		                                               		"expiration_date" => $expirationdate,  // Empty or date in yyyy-mm-dd
		                                               		"registration_date" => $registrationdate,  // Empty or date in yyyy-mm-dd
													   		"authkey" => $authkey));
				$domain_array[] = $response;
				
			// When loop is ready, return array
			return $domain_array;
		}
		else
		{
			// No domains can be found
			$this->Error[] 	= sprintf("YourName: Error while retreiving domains: %s", $error_msg);
			return false;			
		}
  
	}
	
	/**
	 * Change the lock status of the specified domain.
	 * 
	 * @param 	string 	$domain		The domain to change the lock state for
	 * @param 	bool 	$lock		The new lock state (True|False)
	 * @return	bool				True is the lock state was changed succesfully
	 */
	function lockDomain($domain, $lock = true) {
     	/**
		 * Step 1) (un)lock domain
		 */
		// (un)lock the domain, you can use $domain and $lock
		$response 	= true;
		$error_msg 	= '';
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response === true)
		{
			// (un)locking is succesfull
			return true;
		}
		else
		{
			// (un)locking failed
			$this->Error[] 	= sprintf("YourName: Error while (un)locking '%s': %s", $domain, $error_msg);
			return false;			
		}
	}
	
	/**
	 * Change the autorenew state of the given domain. When autorenew is enabled, the domain will be extended.
	 * 
	 * @param 	string	$domain			The domainname to change the autorenew setting for,
	 * @param 	bool	$autorenew		The new autorenew setting (True = On|False = Off)
	 * @return	bool					True when the setting is succesfully changed; False otherwise
	 */
	function setDomainAutoRenew($domain, $autorenew = true) {
		/**
		 * Step 1) change autorenew for domain
		 */
		// change autorenew for the domain, you can use $domain and $autorenew
		$response 	= true;
		$error_msg 	= '';
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response === true)
		{
			// change is succesfull
			return true;
		}
		else
		{
			// change failed
			$this->Error[] 	= sprintf("YourName: Error while changing auto renew '%s': %s", $domain, $error_msg);
			return false;			
		}
	}
	
	/**
	 * Get EPP code/token
	 * 
	 * @param mixed $domain
	 * @return 
	 */
	public function getToken($domain){			
		/**
		 * Step 1) get EPP code/token
		 */
		$response 	= true;
		$auth_code 	= 'XYZ0123';
		$error_msg 	= '';
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response === true)
		{
			// EPP code is retrieved
			return $auth_code;
		}
		else
		{
			// EPP code cannot be retrieved
			$this->Error[] 	= sprintf("YourName: Error while retreiving authorization code '%s': %s", $domain, $error_msg);
			return false;			
		}
	}
	
	/**
	 * getSyncData()
	 * Check domain information for one or more domains
	 * @param mixed $list_domains	Array with list of domains. Key is domain, value must be filled.
	 * @return mixed $list_domains
	 */
	public function getSyncData($list_domains)
	{
		/**
		 * There are two scenario's for retrieving the information
		 * 1. Get the domain list which provide sufficient information for this function
		 * 2. We must request the info per domain. Take care of script timeout
		 * 
		 * Both scenario's can be found below
		 */
	 	$scenario = 'list'; // list | onebyone
	 	
	 	if($scenario == 'list')
	 	{
	 		/**
	 		 * Scenario 1: Get the domain list which provide sufficient information for this function
	 		 */
	 		
			// First copy the array to $check_domains, so we can check if domains doesn't exist anymore
			$check_domains = $list_domains;
			  
	  		// Ask registrar for information of all domains
			$response = array(); // Array with all domains and their information
	 		 
			if($response === FALSE)
			{
				$this->Error[] = 'Domain(s) could not be retrieved at the registrar';
	        	return FALSE;
			}
			
			// loop trough the domain list from the registrar
			foreach($response as $_domain_info)
			{
				$domain_name 		= 'hostfact.com';
				
				if(!isset($list_domains[$domain_name]))
				{
					// Not in list to sync
					continue;
				}
				
				// Add data
				$nameservers_array  = array('ns1.hostfact.com','ns2.hostfact.com');
		        $expirationdate     = '2014-05-01';
		        $auto_renew         = 'on'; // or 'off'
		        
		        // extend the list_domains array with data from the registrar
		        $list_domains[$domain_name]['Information']['nameservers']        = $nameservers_array;
		        $list_domains[$domain_name]['Information']['expiration_date']    = (isset($expirationdate)) ? $expirationdate : '';
		        $list_domains[$domain_name]['Information']['auto_renew']         = (isset($auto_renew)) ? $auto_renew : '';
		        
		        $list_domains[$domain_name]['Status'] = 'success';
		        
		        unset($check_domains[$domain_key]);
			}
			
			// add errors to the domains that weren't found with the registrar 
	        if(count($check_domains) > 0)
	        {
	            foreach($check_domains as $domain_key => $value)
	            {
	                $list_domains[$domain_key]['Status']    = 'error';
	                $list_domains[$domain_key]['Error_msg'] = 'Domain not found';
	            }
	        }
	        
	        // Return list
	        return $list_domains;
	 	}
	 	elseif($scenario == 'onebyone')
	 	{
	 		/**
	 		 * Scenario 2: We must request the info per domain. Take care of script timeout
	 		 */
	 		 
			$max_domains_to_check = 10;
			
			$checked_domains = 0;
			// Check domain one for one
			foreach($list_domains as $domain_name => $value)
			{
				// Ask registrar for information of domain
				$response = array(); // Array with domain information
				
				if($response === FALSE)
				{
					$list_domains[$domain_name]['Status']    = 'error';
	                $list_domains[$domain_name]['Error_msg'] = 'Domain not found';
	                continue;
				}
			
				// Add data
				$nameservers_array  = array('ns1.hostfact.com','ns2.hostfact.com');
		        $expirationdate     = '2014-05-01';
		        $auto_renew         = 'on'; // or 'off'
		        
		        // extend the list_domains array with data from the registrar
		        $list_domains[$domain_name]['Information']['nameservers']        = $nameservers_array;
		        $list_domains[$domain_name]['Information']['expiration_date']    = (isset($expirationdate)) ? $expirationdate : '';
		        $list_domains[$domain_name]['Information']['auto_renew']         = (isset($auto_renew)) ? $auto_renew : '';
		        
		        $list_domains[$domain_name]['Status'] = 'success';
			
				// Increment counter
				$checked_domains++;	
				
				// Stop loop after max domains
				if($checked_domains > $max_domains_to_check)
				{
					break;
				}		
			}
			
			
			// Return list  (domains which aren't completed with data, will be synced by a next cronjob)
	        return $list_domains;
	 	}
	}
	
	/**
	 * Update the domain Whois data, but only if no handles are used by the registrar.
	 * 
	 * @param mixed $domain
	 * @param mixed $whois
	 * @return boolean True if succesfull, false otherwise
	 */
	function updateDomainWhois($domain,$whois){
		/**
		 * Step 1) update WHOIS data for domain.
		 */
		// update owner WHOIS for the domain, you can use $domain and $whois
		$response 	= true;
		$error_msg 	= '';
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response === true)
		{
			// update is succesfull
			return true;
		}
		else
		{
			// update failed
			$this->Error[] 	= sprintf("YourName: Error while changing contact for '%s': %s", $domain, $error_msg);
			return false;			
		}
	}
	
	/**
	 * get domain whois handles
	 * 
	 * @param mixed $domain
	 * @return array with handles
	 */
	function getDomainWhois($domain){
		/**
		 * Step 1) get handles for WHOIS contacts
		 */
		$contacts = array();
		$contacts['ownerHandle'] 	= 'ABC001';
		$contacts['adminHandle'] 	= 'ABC002';
		$contacts['techHandle'] 	= 'ABC003';

		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		return $contacts;
	}
	
	/**
	 * Create a new whois contact
	 * 
	 * @param 	array		 $whois		The whois information for the new contact.
	 * @param 	mixed 	 	 $type		The contact type. This is only used to access the right data in the $whois object.
	 * @return	bool					Handle when the new contact was created succesfully; False otherwise.		
	 */
	function createContact($whois, $type = HANDLE_OWNER) {
		// Determine which contact type should be found
		switch($type) {
			case HANDLE_OWNER:	$prefix = "owner"; 	break;	
			case HANDLE_ADMIN:	$prefix = "admin"; 	break;	
			case HANDLE_TECH:	$prefix = "tech"; 	break;	
			default:			$prefix = ""; 		break;	
			
		}

		// Some help-functions, to obtain more formatted data
		$whois->getParam($prefix,'StreetName');		// Not only Address, but also StreetName, StreetNumber and StreetNumberAddon are available after calling this function.
		$whois->getParam($prefix,'CountryCode');	// Phone and faxnumber are split. CountryCode, PhoneNumber and FaxNumber available. CountryCode contains for example '+31'. PhoneNumber contains number without leading zero e.g. '123456789'.

		/**
		 * Step 1) Create the contact
		 */
		// Create the contact
		
		// Available variables, see example in Appendix A of documentation.
		// e.g.
		// $whois->ownerSurName			To obtain surname of domain owner
		// $whois->adminPhoneNumber   	To obtain phone number of admin contact data
		
		$handle 	= 'ABCDEF';
		$error_msg 	= '';
			
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($handle)
		{
			// A handle is created
			return $handle;
		}
		else
		{
			// Creating handle failed
			$this->Error[] 	= sprintf("YourName: Error while creating contact: %s", $error_msg);
			return false;			
		}
	}
	
	/**
	 * Update the whois information for the given contact person.
	 * 
	 * @param string $handle	The handle of the contact to be changed.
	 * @param array $whois The new whois information for the given contact.
	 * @param mixed $type The of contact. This is used to access the right fields in the whois array
	 * @return
	 */
	function updateContact($handle, $whois, $type = HANDLE_OWNER) {
		// Determine which contact type should be found
		switch($type) {
			case HANDLE_OWNER:	$prefix = "owner"; 	break;	
			case HANDLE_ADMIN:	$prefix = "admin"; 	break;	
			case HANDLE_TECH:	$prefix = "tech"; 	break;	
			default:			$prefix = ""; 		break;	
			
		}

		// Some help-functions, to obtain more formatted data
		$whois->getParam($prefix,'StreetName');		// Not only Address, but also StreetName, StreetNumber and StreetNumberAddon are available after calling this function.
		$whois->getParam($prefix,'CountryCode');	// Phone and faxnumber are split. CountryCode, PhoneNumber and FaxNumber available. CountryCode contains for example '+31'. PhoneNumber contains number without leading zero e.g. '123456789'.

		/**
		 * Step 1) Update the contact, it can depend on the modified data which action you should take.
		 */
		// Update the contact
		
		// Available variables, see example in Appendix A of documentation.
		// e.g.
		// $whois->ownerSurName			To obtain surname of domain owner
		// $whois->adminPhoneNumber   	To obtain phone number of admin contact data
		
		$response 	= true;
		$error_msg 	= '';
			
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response)
		{
			// If a new handle is created, you can use:
			// $this->registrarHandles[$prefix] = "ABCDEF";
			
			// handle is updated
			return true;
		}
		else
		{
			// Updating handle failed
			$this->Error[] 	= sprintf("YourName: Error while editting contact: %s", $error_msg);
			return false;			
		}
	}
	
	/**
     * Get information availabe of the requested contact.
     * 
     * @param string $handle The handle of the contact to request.
     * @return array Information available about the requested contact.
     */
    function getContact($handle){        
      /**
		 * Step 1) Create the contact
		 */
		// Create the contact
		$response 	= true;
		$whois 		= new whois();
		$error_msg 	= '';
			
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response)
		{
			// The contact is found
			$whois->ownerCompanyName 	= "BusinessName";
			$whois->ownerInitials		= "C.";
			$whois->ownerSurName		= "Jackson";
			$whois->ownerAddress 		= "222 E 14th Street";
			$whois->ownerZipCode 		= "10003";
			$whois->ownerCity 			= "New York";
			
			$whois->ownerCountry 		= 'US';		
			$whois->ownerPhoneNumber	= "+1 23 45 67 80";
			$whois->ownerFaxNumber 		= "+1 23 45 67 81";    
			$whois->ownerEmailAddress	= "example@hostfact.com";
			
			return $whois;
		}
		else
		{
			// Contact cannot be found
			$this->Error[] 	= sprintf("YourName: Error while retreiving contact: %s", $error_msg);
			return $whois;			
		}
	}
		
	
	/**
     * Get the handle of a contact.
     * 
     * @param array $whois The whois information of contact
     * @param string $type The type of person. This is used to access the right fields in the whois object.
     * @return string handle of the requested contact; False if the contact could not be found.
     */
    function getContactHandle($whois = array(), $type = HANDLE_OWNER) {
		
		// Determine which contact type should be found
		switch($type) {
			case HANDLE_OWNER:  $prefix = "owner";  break;	
			case HANDLE_ADMIN:  $prefix = "admin";  break;	
			case HANDLE_TECH:   $prefix = "tech";   break;	
			default:            $prefix = "";       break;	
		}

		/**
		 * Step 1) Search for contact data
		 */
		// Search for a handle which can be used
		$handle 	= 'ABCDEF';
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($handle)
		{
			// A handle is found
			return $handle;
		}
		else
		{
			// No handle is found
			return false;			
		}
   	}
	
	/**
     * Get a list of contact handles available
     * 
     * @param string $surname Surname to limit the number of records in the list.
     * @return array List of all contact matching the $surname search criteria.
     */
    function getContactList($surname = "") {
		/**
		 * Step 1) Search for contact data
		 */
		$contact_list = array(array("Handle" 		=> "C0222-042",
									"CompanyName"	=> "BusinessName",
									"SurName" 		=> "Jackson",
									"Initials"		=> "C."
							),
							array(	"Handle" 		=> "C0241-001",
									"CompanyName"	=> "",
									"SurName" 		=> "Smith",
									"Initials"		=> "John"
							)
						);


		/**
		 * Step 2) provide feedback to HostFact
		 */
		if(count($contact_list) > 0)
		{
			// Return handle list
			return $contact_list;
		}
		else
		{
			// No handles are found
			return array();			
		}
   	}

	/**
   	 * Update the nameservers for the given domain.
   	 * 
   	 * @param string $domain The domain to be changed.
   	 * @param array $nameservers The new set of nameservers.
   	 * @return bool True if the update was succesfull; False otherwise;
   	 */
   	function updateNameServers($domain, $nameservers = array()) {
		/**
		 * Step 1) update nameservers for domain
		 */
		$response 	= true;
		$error_msg 	= '';
		
		/**
		 * Step 2) provide feedback to HostFact
		 */
		if($response === true)
		{
			// Change nameservers is succesfull
			return true;
		}
		else
		{
			// Nameservers cannot be changed
			$this->Error[] 	= sprintf("YourName: Error while changing nameservers for '%s': %s", $domain, $error_msg);
			return false;			
		}
	}
    
    
    /**
     * Retrieve all DNS templates from registrar
     * 
   	 * @return array List of all DNS templates
     */
    function getDNSTemplates() {
    	/**
    	 * Step 1) get all DNS templates
    	 */
    	$response 	= true;
    	
    	/**
    	 * Step 2) provide feedback to HostFact
    	 */
    	if($response === true)
    	{
            $dns_templates = array();
    		foreach($response['templates'] as $key => $_dns_template)
            {
                $dns_templates['templates'][$key]['id']      = $_dns_template['id'];
                $dns_templates['templates'][$key]['name']    = $_dns_template['name'];
            }
                
    		return $dns_templates;
    	}
    	else
    	{
    		// Registrar has/supports no DNS templates
    		$this->Error[] = 'DNS Templates could not be retrieved at the registrar';
            return FALSE;
    	}
    }
    
    /**
   	 * Get the DNS zone of a domain
   	 * 
   	 * @param string $domain The domain 
   	 * @return array Array with DNS zone info and DNS records
   	 */
    function getDNSZone($domain) {
    	/**
    	 * Step 1) get DNS zone
    	 */
    	$response 	= true;
    	
    	/**
    	 * Step 2) provide feedback to HostFact
    	 */
    	if($response === true)
    	{
            $i = 0;
            $dns_zone = array();
            
            foreach($response['records'] as $record)
            {
                // optionally, you can define records as readonly, these records won't be editable in HostFact
                if(strtolower($record['type']) == 'soa')
                {
                    $record_type = 'records_readonly';
                }
                else
                {
                    $record_type = 'records';
                }
                
                $dns_zone[$record_type][$i]['name']        = $record['name'];
                $dns_zone[$record_type][$i]['type']        = $record['type'];
                $dns_zone[$record_type][$i]['value']       = $record['value'];
                $dns_zone[$record_type][$i]['priority']    = $record['prio'];
                $dns_zone[$record_type][$i]['ttl']         = $record['ttl'];
                $dns_zone[$record_type][$i]['id']          = $record['id']; // not required
    
                $i++;
            }
            
            return $dns_zone;
    	}
    	else
    	{
    		// DNS zone does not exist or API call failed
    		return FALSE;
    	}
    }
    
    /**
   	 * Update the DNS zone of a domain at the registrar
   	 * 
   	 * @param string $domain The domain
     * @param array  $dns_zone Array with DNS zone info and DNS records
   	 * @return array Array with DNS zone info & DNS records
   	 */
    function saveDNSZone($domain, $dns_zone) {
				    
        /*
        if the registrar does not support a update command, but only add/delete commands
        use the getDNSZone command and compare it's output with the $dns_zone array
        this way you can check which records are edited and add/delete them accordingly
        */
                        
    	/**
    	 * Step 1) update DNS zone at registrar
    	 */
    	$response 	= true;
    	
    	/**
    	 * Step 2) provide feedback to HostFact
    	 */
    	if($response === true)
    	{
            return TRUE;
    	}
    	else
    	{
    		return FALSE;
    	}
    }

    /**
     * Retrieve all SSL products from registrar
     *
     * @param string $ssl_type  Optional filter for SSL validation type
     * @return array
     */
    public function ssl_list_products($ssl_type = '')
    {
        /**
         * Step 1) get all SSL products filtered by the $ssl_type
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        $products_array = array();

        if($response === true)
        {
            foreach($response->response->products->product as $product)
            {
                $products_array[] = array(	'name' => $product->name,
                    'brand' => $product->brand,
                    'templatename' => $product->code,
                    'type' => 'domain'); // domain, extended or organization
            }
        }

        return $products_array;
    }

    /**
     * Get SSL product from registrar
     *
     * @param string $templatename     ID of the product as retrieved by ssl_list_products()
     * @return array
     */
    public function ssl_get_product($templatename)
    {
        /**
         * Step 1) get SSL product
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        $product_info = array();

        if($response === true)
        {
            $product_info['name'] 			= $response['name'];
            $product_info['brand']          = $response['brand'];
            $product_info['templatename'] 	= $response['id'];
            $product_info['type'] 			= $response['type']; // domain, extended or organization

            $product_info['wildcard']		        = ($response['wildcard'] == 'Y') ? TRUE : FALSE;
            $product_info['multidomain']			= ($response['multidomain'] == 'Y') ? TRUE : FALSE;
            $product_info['multidomain_included']	= ($response['domains']) ? $response['domains'] : 0;
            $product_info['multidomain_max']		= ($response['domains_max']) ? $response['domains_max'] : 0;

            // Pricing-periods
            $product_info['periods'] = array();
            if(isset($response['pricing']))
            {
                foreach($response['pricing']['period'] as $index => $period_fee)
                {
                    // please note: periods should be in years, not months
                    $product_info['periods'][] = array('periods' => (int) $period_fee['years'], 'price' => (float) $period_fee['price']);
                }
            }
        }

        return $product_info;
    }

    /**
     * Request the SSL certificate at the registrar
     *
     * @param array $ssl_info   Array with info regarding the SSL certificate
     * @param object $whois     Object with WHOIS data
     * @return bool
     */
    public function ssl_request_certificate($ssl_info, $whois)
    {
        /**
         * Step 1) request SSL certificate at registrar
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        if($response === FALSE)
        {
            $this->Error[] = 'ERROR MESSAGE';
            return FALSE;
        }

        return $response['ORDERID'];
    }

    /**
     * Retrieve the approver emailadresses for a SSL certificate from the registrar
     *
     * @param string $commonname    Domain name
     * @param string $templatename  ID of the product as retrieved by ssl_list_products()
     * @return array
     */
    public function ssl_get_approver_list($commonname, $templatename): array
    {
        /**
         * Step 1) get list of approver email adresses at registrar
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        if($response === FALSE)
        {
            $this->Error[] = 'ERROR MESSAGE';
            return [];
        }

        return is_array($response['approver_list']) ? array_unique($response['approver_list']) : []; // array with e-mailadresses
    }

    /**
     * Retrieve the status of a SSL certificate from the registrar
     *
     * @param string $ssl_order_id  Order id from a SSL certificate
     * @return array
     */
    public function ssl_get_request_status($ssl_order_id)
    {
        /**
         * Step 1) get status of SSL certificate
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        $order_info = array();
        if($response === FALSE)
        {
            $order_info['status'] = 'error';
        }

        switch(strtolower($response['status']))
        {
            default:
            case 'pending':
                $order_info['status'] = 'inrequest';
                break;

            case 'failed':
                $order_info['status'] = 'error';
                break;

            case 'active':
                $order_info['status'] = 'install';
                $order_info['activation_date'] = $response['start_date'];
                $order_info['expiration_date'] = $response['end_date'];
                break;
        }

        return $order_info;
    }

    /**
     * Download the CSR of a SSL certificate from the registrar
     *
     * @param string $ssl_order_id  Order id from a SSL certificate
     * @return bool|string
     */
    public function ssl_download_ssl_certificate($ssl_order_id)
    {
        /**
         * Step 1) download the SSL certificate
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        if($response === FALSE)
        {
            $this->Error[] = 'ERROR MESSAGE';
            return FALSE;
        }

        return $response['certificate'];
    }

    /**
     * Reissue a SSL certificate at the registrar
     *
     * @param string $ssl_order_id  Order id from a SSL certificate
     * @param array $ssl_info       Array with info regarding the SSL certificate
     * @param object $whois         Object with WHOIS data
     * @return bool
     */
    public function ssl_reissue_certificate($ssl_order_id, $ssl_info, $whois)
    {
        /**
         * Step 1) reissue the SSL certificate at the registrar
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        if($response === FALSE)
        {
            $this->Error[] = 'ERROR MESSAGE';
            return FALSE;
        }

        return $response['ORDERID'];
    }

    /**
     * Renew the SSL certificate at the registrar
     *
     * @param array $ssl_info       Array with info regarding the SSL certificate
     * @param object $whois         Object with WHOIS data
     * @return bool
     */
    public function ssl_renew_certificate($ssl_info, $whois)
    {
        /**
         * Step 1) renew the SSL certificate at the registrar
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        if($response === FALSE)
        {
            $this->Error[] = 'ERROR MESSAGE';
            return FALSE;
        }

        return $response['ORDERID'];
    }

    /**
     * Resend the approver email
     *
     * @param string $ssl_order_id      Order id from a SSL certificate
     * @param $approver_emailaddress    Approver emailaddress
     * @return bool
     */
    public function ssl_resend_approver_email($ssl_order_id, $approver_emailaddress)
    {
        /**
         * Step 1) resend the approver e-mail
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        if($response === FALSE)
        {
            $this->Error[] = 'ERROR MESSAGE';
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Revoke SSL certificate at the registrar
     *
     * @param string $ssl_order_id      Order id from a SSL certificate
     * @return bool
     */
    public function ssl_revoke_ssl_certificate($ssl_order_id)
    {
        /**
         * Step 1) revoke the SSL certificate at the registrar
         */
        $response 	= true;

        /**
         * Step 2) provide feedback to HostFact
         */
        if($response === FALSE)
        {
            $this->Error[] = 'ERROR MESSAGE';
            return FALSE;
        }

        return TRUE;
    }
    
	
	/**
	 * Get class version information.
	 * 
	 * @return array()
	 */
	static function getVersionInformation() {
		require_once("3rdparty/domain/YOURCLASSNAME/version.php"); //TODO: change your class name
		return $version;	
	}	
	
}
