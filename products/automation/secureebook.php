<?php
####################################################################################
##                                                                                ##
##          Secure-Ebook.com Integration Example for AShop Deluxe                 ##
##                                                                                ##
##          This is a modified version of the SDK provided by Secure-Ebook        ##
##          It should be used through the Automation Fulfilment feature in        ##
##          AShop. Change $testmode to false when you have tested the script.     ##
##          Edit the code at the bottom of the script to customize the email      ##
##          that is generated and sent to your customers with the activation      ##
##          keys when they have completed a purchase.						      ##
##                                                                                ##
####################################################################################

// Change this to false to use the script in live mode...
$testmode = true;


// Only edit the code below this point if you have good knowledge of PHP...

	// global array collecting parser data
	$com_secure_ebook_data = array();
	
	class SebSDK
	{
		var $sdk_url	= "https://www.secure-ebook.com/sdk.jsp";
		var $vendor_code;
		var $sdk_key;
		var $testMode;
		
		var $xml_parser;
		var $xml_data;
		
		var $last_error = 0;
		var $last_error_id = 0;
		
		var $curl_handle = false;
		
		/**
		 * Constructor
		 * [in] $vendorCode.  The Secure-eBook vendor code
		 * [in] $sdkKey.  The Secure-eBook account's SDK key
		 * [in] $sdkURL.  The Secure-eBook SDK URL (optional)
		 */
		function SebSDK($vendorCode, $sdkKey, $testMode = false, $sdkURL = false)
		{
			global $com_secure_ebook_data;
		
			$this->vendor_code = $vendorCode;
			$this->sdk_key = $sdkKey;
			$this->testMode = $testMode;
			
			if ($sdkURL)
				$this->sdk_url = $sdkURL;
		}

		
		/**
		 * Request activation keys from Secure-eBook
		 * [in] $product - array of product information.  Product information can either be
		 *      a string containing the product code or an array where the first item is the 
		 *      product code and the second item is the required quantity.
		 *      When no quantity is specified, 1 is assumed.
		 *
		 * [in] $secureebookproductcode - optional information that Specifies if the “product code” 
		 *          will be the Secure-eBook product code or the shopping cart product code.
		 *
		 * [in] $userdata - optional information that identifies your transaction.  Will be stored in
		 *			Secure-eBook with the transaction
		 *
		 * [in] $orderinfo - hash map containing order information.  All information is optional.
		 *      accepted info: name, email, country, stateprov, note, total, taxes, currency, description.
		 * 
		 * If successful, request will return an array of keys.
		 * Keys are associative arrays with the following values:
		 * 	'productcode' the code of the product they are associated with
		 *  'key' the activation key generated for the product
		 */
		function request($products, $secureebookproductcode= true, $userdata = false, $orderinfo = false)
		{
			// initialize message
			$message = '<?xml version="1.0"?><request vendorcode="' .			
									$this->xmlentities($this->vendor_code) . '"';
			
			if ($secureebookproductcode)
				$message.= ' secureebookproductcode="true"';
			else
				$message.= ' secureebookproductcode="false"';
									
			if ($userdata)
				$message.= ' userdata="' . $this->xmlentities($userdata) . '"';
				
			if ($this->testMode)
				$message.= ' testmode="true"';
				
			$message .= '>';
			
			
			// Add products
			foreach ($products as $product)
			{
				if (is_array($product) && count($product) > 0)
				{
					$qty = 1;
					if (count($product) > 1)
						$qty = $product[1];

					if ($qty > 1)
						$qty = ' qty="' . $qty . '"';
					else
						$qty = '';

					$message .= '<product code="' . $this->xmlentities($product[0]) . '"'.$qty.'/>';
				}
				else
				{
					$message .= '<product code="' . $this->xmlentities($product) . '"/>';
				}
			}
			
			// add orderinfo
			
			if ($orderinfo && is_array($orderinfo))
			{
				$info = '';
				$info .= $this->getXMLOrderInfo($orderinfo['name'], 'name');
				$info .= $this->getXMLOrderInfo($orderinfo['email'], 'email');
				$info .= $this->getXMLOrderInfo($orderinfo['country'], 'country');
				$info .= $this->getXMLOrderInfo($orderinfo['stateprov'], 'stateprov');
				$info .= $this->getXMLOrderInfo($orderinfo['note'], 'note');
				$info .= $this->getXMLOrderInfo($orderinfo['total'], 'total');
				$info .= $this->getXMLOrderInfo($orderinfo['taxes'], 'taxes');
				$info .= $this->getXMLOrderInfo($orderinfo['currency'], 'currency');
				$info .= $this->getXMLOrderInfo($orderinfo['description'], 'description');
				
				if ($info)
					$message .= '<orderinfo' . $info . '/>';
			}
			
			$message .= '</request>';

			// =========== [ REQUEST ] ======================
			$res = $this->postMessage($message);
			if ($res === false)
			{
				if (!$this->last_error_id)
				{
					$this->last_error = 'Error communicating with server';
					$this->last_error_id = 1000;
				}	

				$this->finalizeCurl();					
				return false;
			}	
			
			if (!$this->parseXML($res))
			{
				$this->finalizeCurl();
				return false;
			}

			$token = $this->data['token'];
						
			
			if ( !$token)
			{
				if (!$this->last_error_id)
				{
					$this->last_error = 'SDK token not received';
					$this->last_error_id = 1010;
					
				}	
					
				$this->finalizeCurl();	
				return false;
			}
			
			// =========== [ CONFIRM ] ======================
			$code = strtolower(md5($token . $this->sdk_key));
			$message = '<?xml version="1.0"?><confirm code="' . $this->xmlentities($code) . '"/>';
			
			$res = $this->postMessage($message);
			if ($res === false)
			{
				if (!$this->last_error_id)
				{
					$this->last_error = 'Error communicating with server';
					$this->last_error_id = 1000;
				}	
					
				$this->finalizeCurl();	
				return false;
			}
			
			if (!$this->parseXML($res))
			{
				$this->finalizeCurl();
				return false;
			}
				
			$keys = $this->data['keys'];
			$links = $this->data['links'];
			
			if ( $secureebookproductcode && !$keys && !$links)
			{
				if (!$this->last_error_id)
				{
					$this->last_error = 'No activation keys or links were returned';
					$this->last_error_id = 1020;
				}	
					
				$this->finalizeCurl();	
				return false;
			}
				
			$warnings = $this->data['warnings'];
			
			$this->finalizeCurl();
			return array('keys' => $keys, 'warnings' => $warnings, 'links' => $links);
		}
		
		/**
		 * Allow subclasses to further parametrize curl for proxies.
		 */
		function setupCurl(&$ch)
		{
				
			/*
			 * If you are trying to use CURLOPT_FOLLOWLOCATION and you get this warning:
			 * Warning: curl_setopt() [function.curl-setopt]: CURLOPT_FOLLOWLOCATION cannot 
			 * be activated when in safe_mode or an open_basedir is set...
			 * then you will want to read http://www.php.net/ChangeLog-4.php which says 
			 * "Disabled CURLOPT_FOLLOWLOCATION in curl when open_basedir or safe_mode are enabled." 
			 * as of PHP 4.4.4/5.1.5.  This is due to the fact that curl is not part of PHP and doesn't 
			 * know the values of open_basedir or safe_mode, so you could comprimise your webserver 
			 * operating in safe_mode by redirecting (using header('Location: ...')) to "file://" urls, 
			 * which curl would have gladly retrieved.
			 * 
			 * Until the curl extension is changed in PHP or curl (if it ever will) to deal with 
			 * "Location:" headers, here is a far from perfect remake of the curl_exec function 
			 * that I am using.
			 *
			 * Since there's no curl_getopt function equivalent, you'll have to tweak the function 
			 * to make it work for your specific use.  As it is here, it returns the body of the 
			 * response and not the header.  It also doesn't deal with redirection urls with username 
			 * and passwords in them.
			 */
					 
  		// curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);			 
			// curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
			// curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			// curl_setopt ($ch, CURLOPT_PROXY, PROXY_URL);
			// curl_setopt ($ch, CURLOPT_TIMEOUT, 120);
		}	
		
		/**
		 * [PRIVATE] Initializes the CURL connection.
		 */
		function initializeCurl()
		{
			if ($this->curl_handle === false)
			{
				$this->curl_handle = curl_init();
				
				curl_setopt($this->curl_handle, CURLOPT_URL, $this->sdk_url);
				curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYHOST, 1);			
				curl_setopt($this->curl_handle, CURLOPT_POST, 1);				
				
				$this->setupCurl($this->curl_handle);
			}
			
			return $this->curl_handle;
		}
		
		/**
		 * [PRIVATE] Initializes the CURL connection.
		 */
		function finalizeCurl()
		{
			if ($this->curl_handle !== false)
			{
				curl_close($this->curl_handle);
				$this->curl_handle = false;
			}
		}
		
		/**
		 * [PRIVATE]
		 * Posts a message to the server.  
		 * Returns response or false.
		 * You can fetch error from $this->last_error
		 */
		function postMessage($message)
		{
			$this->last_error = 0;
			$this->last_error_id = 0;
			
			$ch = $this->initializeCurl();
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'xml=' . urlencode($message));			
			
			$res = curl_exec($ch);	
			$info = curl_getinfo($ch);
			$curlerror = curl_error($ch);
			
			/*
			echo "<hr/>Msg<pre>".htmlentities($message)."</pre><hr/>";
			echo "Res<pre>".htmlentities($res)."</pre><hr/>";
			foreach($info as $k=>$v){ echo "$k = $v<br/>"; }			
			echo "<hr/>";
			*/
			
			if ($info['http_code'] != 200)
			{
				$this->last_error = 'HTTP Error ' . $info['http_code'];
				$this->last_error_id = 10000 + $info['http_code'];
				
				return false;
			}
			else
			{
				return $res;
			}
		}
	
		/**
		 * [PRIVATE]
		 * Fixes a string to be valid XML
		 */	
		function xmlentities($string) 
		{
	  	return str_replace ( 
	  		array ( '&', '"', "'", '<', '>'), 
	  		array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;'), 
	  		$string);
	  }
	  
	  /**
	   * [PRIVATE]
	   * Returns an XML parameter matching specified order info
	   */
	  function getXMLOrderInfo($param, $name)
	  {
	  	if (!$param)
	  		return '';
	  		
	  	return ' ' . $name . '="' . $this->xmlentities($param) . '"';
	  }		
		
		/**
		 * [PRIVATE]
		 * Reset private data
		 */
		function resetXMLData()
		{
			$this->data = array();
		}
		
		/**
		 * Parse give XML data
		 */
		function parseXML($data)
		{
			global $com_secure_ebook_data;
			
			$this->xml_parser = xml_parser_create();
			xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($this->xml_parser, sebXMLStartElement, sebXMLEndElement);
			xml_set_character_data_handler($this->xml_parser, sebXMLData);
			
			$com_secure_ebook_data[$this->xml_parser] = &$this->data;
			
			$this->resetXMLData();
			
			if (!xml_parse($this->xml_parser, $data, true))
			{
				$this->last_error = 'invalid XML message received: ' . xml_error_string(xml_get_error_code ($this->xml_parser));
				$this->last_error_id = 1030;
				unset($com_secure_ebook_data[$this->xml_parser]);
				xml_parser_free($this->xml_parser);
				return false;
			}
			
			$this->last_error = $this->data['error'];
			$this->last_error_id = $this->data['error_id'];

			unset($com_secure_ebook_data[$this->xml_parser]);			
			xml_parser_free($this->xml_parser);
			
			return true;
		}
	}
	
	/**
	 * [PRIVATE]
	 * Called by XML parser when opening a new element
	 */	 
	function sebXMLStartElement($parser, $name, $atts)
	{
		global $com_secure_ebook_data;
		$sdk = &$com_secure_ebook_data[$parser];
		
		if (!$sdk['root'])
			$sdk['root'] = $name;
		
		switch ($name)
		{
		case 'token':
			$sdk['token'] = $atts['token'];
			break;
			
		case 'error':
			$sdk['error_id'] = $atts['messageid'];
			break;
			
		case 'success':
			$sdk['txnid'] = $atts['txnid'];
			break;
			
		case 'link':
			if ($sdk['root'] == 'success')
			{
				if (!$sdk['links'])
					$sdk['links'] = array($atts);
				else
					$sdk['links'][] = $atts;
			}
			break;

		case 'key':
			if ($sdk['root'] == 'success')
			{
				if (!$sdk['keys'])
					$sdk['keys'] = array($atts);
				else
					$sdk['keys'][] = $atts;
			}
			break;
		
		case 'warning':
			if ($sdk['root'] == 'success')
			{
				if (!$sdk['warnings'])
					$sdk['warnings'] = array($atts);
				else
					$sdk['warnings'][] = $atts;
			}
			break;
				
		}
	}
	
	/**
	 * [PRIVATE]
	 * Called by XML parser when closing an opened element
	 */	
	function sebXMLEndElement($parser, $name)
	{
	}	
	
	/**
	 * [PRIVATE]
	 * Called by XML parser when receiving data
	 */
	function sebXMLData($parser, $data)
	{
		global $com_secure_ebook_data;
		$sdk = &$com_secure_ebook_data[$parser];
		
		if ($sdk['root'] == 'error')
		{
			if ($sdk['error'])
				$sdk['error'] .= $data;
			else
				$sdk['error'] = $data;
		}	
	}

	// Replace Vendor code and SDK Secret by the information matching the account you would like to connect to
	
	$sdk = new SebSDK(
		$_POST["vendorcode"], 
		$_POST["sdksecret"],
		$testmode);	// This sample connects in test mode.  In test mode, no orders are stored in Secure-eBook and no keys are consumed.
		
	// Fill in the order request - change the info to build your own request
	
	$res = $sdk->request(
	
		// Book code and number of copies of that book that are rested.  
		// The array can contain multiple book code/ quantity arrays.  See online documentation for more information
 
		array(array($_POST["productid"], 1)),							
		false,						//Specifies that the “product code” will be the shopping cart code.
		$_POST["orderid"], array(													// order code / order detail
			'name' => $_POST["firstname"]." ".$_POST["lastname"],								// client name.  Required.
			'email' => $_POST["email"],							// client email. Required.  No email is sent to your client.
			
			// Rest of order info is optional.
			'country' => $_POST["country"], 						// country name
			'stateprov' => $_POST["state"], 		// state or province
			'note'=>'Processed by AShop V' 								// internal note, will be stored in order information on secure-ebook server
		));
	
	if ($res === false)
		{
			// Error codes are described in the SDK documentation 
			// http://www.secure-ebook.com/help/sdk:error
			
			$msg = "ERROR " . $sdk->last_error_id . "\n" . $sdk->last_error;
		}
		else	
		{	
			// Generated keys are returned here
			if ( $res['keys'] )
			{
				foreach($res['keys'] as $key)
				{
					$msg = "This is your activation key for ".$_POST["productname"].":\n" . $key['key'];
				}
			}
			
			// Generated links are returned here
			if ($res['links'])
			{
				foreach($res['links'] as $link)
				{
					$msg .= "Your copy of ".$_POST["productname"]." can be downloaded here:\n" . $link['url'];
				}
			}
			
			// warnings are returned here
			if ( $res['warnings'] )
			{
				foreach($res['warnings'] as $warning)
				{
					$msg .= "WARNING " .  $warning['messageid'] . " for " . $_POST["productname"];
					$msg .= "\n".$warning['text'];
				}
			}

			$headers = "From: ".$_POST["shopname"]."<".$_POST["shopemail"].">\nX-Sender: <".$_POST["shopemail"].">\nX-Mailer: PHP\nX-Priority: 3\nReturn-Path: <".$_POST["shopemail"].">\n";

			if ($_POST["email"] && $msg) @mail($_POST["email"], "Secure Ebook Delivery", $msg, $headers);
		}
?>