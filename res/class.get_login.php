<?php 
class user_getlogin { 
    var $cObj;// The backReference to the mother cObj object set at call time 
    /** 
    * Call it from a USER cObject with 'userFunc = user_randomImage->main_randomImage' 
    */ 
    function main($content,$conf)
    {
	tslib_eidtools::connectDB();  
	$pageUid = $GLOBALS['TSFE']->id;
	// @var tslib_feUserAuth
	$frontEndUser = $GLOBALS['TSFE']->fe_user;
	
	$content = '';
	$loginbox = '<div id="ajaxlogin">
		<div class="tx-ajaxlogin-widget">
		<div class="close"><a href="#" class="ajaxlogin-close">X</a></div>
		<form id="tx-ajaxlogin-form0fd9a48ffd24618b780d581c760c7a6e" name="loginform" action="" onsubmit="tx_rsaauth_feencrypt(this);" method="post">
		<div class="b-form-row">
		<label for="user">
		Username
		</label>
		<div class="b-form-inputs">
		<input type="text" id="user" name="user" value="">
		</div>
		</div>
		<div class="b-form-row">
		<label for="pass">
		Password
		</label>
		<div class="b-form-inputs">
		<input type="password" id="pass" name="pass" value="">
		</div>
		</div>
		<div class="b-form-row">
		<input name="permalogin" class="permalogin" value="1" type="checkbox" id="permalogin">
		<label for="permalogin" class="permalogin">Stay logged in</label>
		</div>
		<div class="b-form-row">
		<div class="login-action-links">
		<a href="#" rel="tx_ajaxlogin[forgot_password]">
		Forgot password?
		</a> |
		<a href="#" rel="tx_ajaxlogin[signup]">
		Sign up!
		</a>
		</div>
		<button type="submit" name="submit" value="Login" class="bu bu-mini">
		Login
		</button>	
		</div>
		<input type="hidden" id="rsa_n" name="n" value="99688E6A64D496BB0348C0F90316D2FD6A903246E0C2CD04442525F72B2204C378761AA0D4BE265D95D151B7B3DE4DFB777BBA7EE7129065A70BF97B4B6CD1B4015BE87AC5A11593CE18039F023ADDD81A72A42B3847DACBA32C7CEA6E3093698937B051C4A2460DF6D3AC899FEDA638528B703F70CD3935606083F8DCB04DB5"><input type="hidden" id="rsa_e" name="e" value="10001">
		</form>
		</div>
		</div>';
	
	if ($this->isFrontendUserActive($frontEndUser)) {
	    $content = '<li id="login-flyout" class="hide-xs"><a href="#">Log out ' . $frontEndUser->username . '</a></li>';
	} else {
	    $content = '<li id="login-flyout" class="hide-xs"><a href="#">Login</a></li>';
	}

	return $content.$loginbox; 
    }

    /**
    * Determines whether a valid frontend user session is currently active.
    *
    * @param tslib_feUserAuth $frontendUser
    * @return boolean
    */
    protected function isFrontendUserActive($frontendUser)
    {
	if (isset($frontendUser->user['uid']) && $frontendUser->user['uid']) {
	    return true;
	}
	return false;
    }
    
    function debug($input)
    {
	print '<pre>';
	print_r($input);
	print '</pre>';
    }
}