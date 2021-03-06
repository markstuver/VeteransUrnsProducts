<?php
class ParamSigner {
	var $secret;
	var $params;
	var $lifetime=24;
	var $signatureType='md5';

	function setSecret($secret)
	{
		$this->secret=$secret;
	}

	function setLifeTime($lifetime)
	{
		$this->lifetime=$lifetime;
	}

	function setSignatureType($signatureType)
	{
		if (checkSignatureType($signatureType))
		{
			$this->signatureType=$mode;
			return true;
		}
		else
		{
			user_error("Invalid signatureType :$mode");
			return false;
		}
	}

	function setParam ($param,$value)
	{
		$this->params[$param]=$value;
	}

	function getQueryString()
	{
		return $this->getSignature(true);
	}

	function getSignature ($queryString=false)
	{

		$this->setParam('PS_EXPIRETIME',time()+(3600*$this->lifetime));
		$this->setParam('PS_SIGTYPE',$this->signatureType);
		$sigstring=$this->secret;
		$urlencstring='';
		ksort($this->params,SORT_STRING);
		foreach ($this->params as $key=>$value)
		{
			$sigstring.="&".$key.'='.$value;
			$urlencstring.="&".urlencode($key).'='.urlencode($value);
		}	
		

		switch ($this->params['PS_SIGTYPE'])
		{
			case 'md5':
				$signature=md5($sigstring);
				break;
			case 'sha1':
				$signature=sha1($sigstring);
				break;
			default:
				user_error('Unknown key signatureType');
		}

		if ($queryString)
		{
			return 'PS_SIGNATURE='.urlencode($signature).$urlencstring;
		}
		else
		{
			return $signature;
		}
	}

	function checkSignatureType($value)
	{
		if ($value=='md5') return true;
		if ($value=='sha1') return true;
		return false;
	}
}
?>
