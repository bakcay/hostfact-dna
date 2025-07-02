<?php
require_once("3rdparty/domain/IRegistrar.php");
require_once("3rdparty/domain/standardfunctions.php");
require_once('library/DomainNameApi/DomainNameAPI_PHPLibrary.php');

use DomainNameApi\DomainNameAPI_PHPLibrary as DNA;
class DomainNameApi implements IRegistrar
{
    public $User;
    public $Password;
    
    public $Error;
    public $Warning;
    public $Success;

    /** @var DNA */
    public $dnaService = null;

    public $Period = 1;
    public $registrarHandles = array();
    
    private $ClassName;
    
	function __construct(){	
		
		$this->ClassName = __CLASS__;
		
		$this->Error = array();
		$this->Warning = array();
		$this->Success = array();		
	}

    
    public function dna(){
        if($this->dnaService == null){
            $this->dnaService = new DNA($this->User, $this->Password,false);
        }
        return $this->dnaService;
    }


	/**
	 * Check whether a domain is already regestered or not. 
	 * 
	 * @param 	string	 $domain	The name of the domain that needs to be checked.
	 * @return 	boolean 			True if free, False if not free, False and $this->Error[] in case of error.
	 */
	function checkDomain($domain) {

	    $domainParts = explode('.', $domain, 2);
        if (count($domainParts) < 2) {
            $this->Error[] = 'Geçersiz domain formatı';
            return false;
        }
        $sld = $domainParts[0];
        $tld = $domainParts[1];
        
		$result = $this->dna()->CheckAvailability([$sld], [$tld], 1, 'create');
		
		if(isset($result[0]['Status']) && $result[0]['Status'] == 'available') {
			return true;
		} else {
			$this->Error[] = 'Domain is not available';
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

		$contact = $this->buildAllContacts($whois);
		
		$result = $this->dna()->RegisterWithContactInfo(
			$domain,
			1,
			$contact,
			$nameservers,
			true,
			false
		);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return true;
		} else {
			$this->Error[] = 'Domain registration failed';
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

		$contact = $this->buildAllContacts($whois);
		
		$result = $this->dna()->Transfer(
			$domain,
			$authcode,
			$contact,
			$nameservers
		);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return true;
		} else {
			$this->Error[] = 'Domain transfer failed';
			return false;
		}
	}
	

	

	/**
	 * Get all available information of the given domain
	 * 
	 * @param 	mixed 	$domain		The domain for which the information is requested.
	 * @return	array				The array containing all information about the given domain
	 */
	function getDomainInformation($domain) {

		$result = $this->dna()->GetDetails($domain);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			$data = $result['data'];
			
			$whois = new whois();
			$whois->ownerHandle = $data['Contacts']['Registrant']['ID'];
			$whois->adminHandle = $data['Contacts']['Administrative']['ID'];
			$whois->techHandle = $data['Contacts']['Technical']['ID'];
			
			$response = array(
				"Domain" => $domain,
				"Information" => array(
					"nameservers" => $data['NameServers'],
					"whois" => $whois,
					"expiration_date" => $data['Dates']['Expiration'],
					"registration_date" => $data['Dates']['Start'],
					"authkey" => $data['AuthCode']
				)
			);
			
			return $response;
		} else {
			$this->Error[] = 'Could not get domain information';
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

		$result = $this->dna()->GetList();


		if(isset($result['result']) && $result['result'] == 'OK') {
			$domain_array = array();


			
			foreach($result['data']['Domains'] as $domain) {
				$whois = new whois();
				$whois->ownerHandle = $domain['Contacts']['Registrant']['ID'];
				$whois->adminHandle = $domain['Contacts']['Administrative']['ID'];
				$whois->techHandle = $domain['Contacts']['Technical']['ID'];
				
				$response = array(
					"Domain" => $domain['DomainName'],
					"Information" => array(
						"nameservers" => $domain['NameServers'],
						"whois" => $whois,
						"expiration_date" => $domain['Dates']['Expiration'],
						"registration_date" => $domain['Dates']['Start'],
						"authkey" => $domain['AuthCode']
					)
				);
				
				$domain_array[] = $response;
			}
			
			return $domain_array;
		} else {
			$this->Error[] = 'Could not get domain list';
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

		if($lock) {
			$result = $this->dna()->EnableTheftProtectionLock($domain);
		} else {
			$result = $this->dna()->DisableTheftProtectionLock($domain);
		}
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return true;
		} else {
			$this->Error[] = 'Could not ' . ($lock ? 'lock' : 'unlock') . ' domain';
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

		$result = $this->dna()->ModifyPrivacyProtectionStatus($domain, $autorenew);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return true;
		} else {
			$this->Error[] = 'Could not set auto renew status';
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

		$result = $this->dna()->GetDetails($domain);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return $result['data']['AuthCode'];
		} else {
			$this->Error[] = 'Could not get auth code';
			return false;
		}
	}
	
	/**
	 * getSyncData()
	 * Check domain information for one or more domains
	 * @param mixed $list_domains	Array with list of domains. Key is domain, value must be filled.
	 * @return mixed $list_domains
	 */
	public function getSyncData($list_domains) {

		$result = $this->dna()->GetList();
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			foreach($result['data'] as $domain) {
				$domain_name = $domain['DomainName'];
				
				if(isset($list_domains[$domain_name])) {
					$list_domains[$domain_name]['Information']['nameservers'] = $domain['NameServers'];
					$list_domains[$domain_name]['Information']['expiration_date'] = $domain['Dates']['Expiration'];
					$list_domains[$domain_name]['Information']['auto_renew'] = $domain['PrivacyProtectionStatus'];
					$list_domains[$domain_name]['Status'] = 'success';
				}
			}
			return $list_domains;
		} else {
			$this->Error[] = 'Could not sync domain data';
			return false;
		}
	}
	
	/**
	 * Update the domain Whois data, but only if no handles are used by the registrar.
	 * 
	 * @param mixed $domain
	 * @param mixed $whois
	 * @return boolean True if succesfull, false otherwise
	 */
	function updateDomainWhois($domain, $whois) {

		$contact = $this->buildAllContacts($whois);
		
		$result = $this->dna()->SaveContacts($domain, $contact);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return true;
		} else {
			$this->Error[] = 'Could not update whois information';
			return false;
		}
	}
	
	/**
	 * get domain whois handles
	 * 
	 * @param mixed $domain
	 * @return array with handles
	 */
	function getDomainWhois($domain) {

		$result = $this->dna()->GetDetails($domain);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			$contacts = array();
			$contacts['ownerHandle'] = $result['data']['Contacts']['Registrant']['ID'];
			$contacts['adminHandle'] = $result['data']['Contacts']['Administrative']['ID'];
			$contacts['techHandle'] = $result['data']['Contacts']['Technical']['ID'];
			
			return $contacts;
		} else {
			$this->Error[] = 'Could not get whois information';
			return false;
		}
	}
	
	/**
	 * Create a new whois contact
	 * 
	 * @param 	array		 $whois		The whois information for the new contact.
	 * @param 	mixed 	 	 $type		The contact type. This is only used to access the right data in the $whois object.
	 * @return	bool					Handle when the new contact was created succesfully; False otherwise.		
	 */
	function createContact($whois, $type = HANDLE_OWNER) {

		$contact = $this->buildAllContacts($whois);
		
		$result = $this->dna()->SaveContacts('', $contact);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return $result['data']['Contacts']['Registrant']['ID'];
		} else {
			$this->Error[] = 'Could not create contact';
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

		$contact = $this->buildAllContacts($whois);
		
		$result = $this->dna()->SaveContacts('', $contact);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return true;
		} else {
			$this->Error[] = 'Could not update contact';
			return false;
		}
	}
	
	/**
     * Get information availabe of the requested contact.
     * 
     * @param string $handle The handle of the contact to request.
     * @return array Information available about the requested contact.
     */
    function getContact($handle) {

        $result = $this->dna()->GetContacts($handle);
        
        if(isset($result['result']) && $result['result'] == 'OK') {
            $whois = new whois();
            $contact = $result['data'][0];
            
            $whois->ownerCompanyName = $contact['Company'];
            $whois->ownerSurName = $contact['LastName'];
            $whois->ownerAddress = $contact['AddressLine1'];
            $whois->ownerZipCode = $contact['ZipCode'];
            $whois->ownerCity = $contact['City'];
            $whois->ownerCountry = $contact['Country'];
            $whois->ownerPhoneNumber = $contact['Phone'];
            $whois->ownerCountryCode = $contact['PhoneCountryCode'];
            $whois->ownerEmailAddress = $contact['EMail'];
            
            return $whois;
        } else {
            $this->Error[] = 'Could not get contact';
            return false;
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

		$result = $this->dna()->ModifyNameServer($domain, $nameservers);
		
		if(isset($result['result']) && $result['result'] == 'OK') {
			return true;
		} else {
			$this->Error[] = 'Could not update nameservers';
			return false;
		}
	}
	
	/**
	 * Get class version information.
	 * 
	 * @return array()
	 */
	static function getVersionInformation() {
		require_once("3rdparty/domain/domainnameapi/version.php");
		return $version;	
	}



    function deleteDomain($domain, $delType = 'end') {

		return false;
	}
	
    private function buildContactArray($whois): array
    {
        return [
            "FirstName" => $whois->ownerSurName,
            "LastName" => $whois->ownerSurName,
            "Company" => $whois->ownerCompanyName,
            "EMail" => $whois->ownerEmailAddress,
            "AddressLine1" => $whois->ownerAddress,
            "City" => $whois->ownerCity,
            "Country" => $whois->ownerCountry,
            "Phone" => $whois->ownerPhoneNumber,
            "PhoneCountryCode" => $whois->ownerCountryCode,
            "Type" => "Contact",
            "ZipCode" => $whois->ownerZipCode,
            "State" => $whois->ownerState
        ];
    }

    private function buildAllContacts($whois): array
    {
        $contact = $this->buildContactArray($whois);
        return [
            'Administrative' => $contact,
            'Billing' => $contact,
            'Technical' => $contact,
            'Registrant' => $contact
        ];
    }
}
