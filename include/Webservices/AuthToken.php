<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
	
	function vtws_getchallenge($username){
		global $adb;
		
		if(empty($username)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"No username given");
		}

		$user = new Users();
		$userid = $user->retrieve_user_id_by_email1($username);

        if(empty($userid)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"email does not exists");
		}

		$authToken = uniqid();
		$servertime = time();
		$expireTime = time()+3600;
		
		$sql = "delete from vtiger_ws_userauthtoken where userid=?";
		$adb->pquery($sql,array($userid));
		
		$sql = "insert into vtiger_ws_userauthtoken(userid,token,expireTime) values (?,?,?)";
		$adb->pquery($sql,array($userid,$authToken,$expireTime));
		
		//return array("token"=>$authToken,"serverTime"=>$servertime,"expireTime"=>$expireTime);


		$token = vtws_getActiveToken($userid);
		if($token == null){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDTOKEN,"Specified token is invalid or expired");
		}
		
		$accessKey = vtws_getUserAccessKey($userid);
		
		if($accessKey == null){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSKEYUNDEFINED,"Access key for the user is undefined");
		}
		
		if(strcmp($accessKey,"w5IlpfkkevdCO01h")!==0){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDUSERPWD,"Invalid email or accesskey");
		}
		$user = $user->retrieveCurrentUserInfoFromFile($userid);
		if($user->status != 'Inactive'){

		return $user;
		}
		throw new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,'Given user is inactive');
	}

	function vtws_getActiveToken($userId){
		global $adb;
		
		$sql = "select token from vtiger_ws_userauthtoken where userid=? and expiretime >= ?";
		$result = $adb->pquery($sql,array($userId,time()));
		if($result != null && isset($result)){
			if($adb->num_rows($result)>0){
				return $adb->query_result($result,0,"token");
			}
		}
		return null;
	}

	function vtws_getUserAccessKey($userId){
		global $adb;
		
		$sql = "select accesskey from vtiger_users where id=?";
		$result = $adb->pquery($sql,array($userId));
		if($result != null && isset($result)){
			if($adb->num_rows($result)>0){
				return $adb->query_result($result,0,"accesskey");
			}
		}
		return null;
	}

?>