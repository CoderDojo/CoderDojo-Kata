<?php
/**
 * Created on 6 Jan 2009 by Serhii Kutnii
 */

class SES_SignupFields {

	public static function render( $args, $parser ) {
		$args = func_get_args();

		$parser = array_shift( $args );

		$template = new CreateUserFieldsTemplate();

		global $wgEnableEmail, $wgAllowRealName, $wgEmailConfirmToEdit, $wgAuth, $wgUser;

		$template->set( 'link', '' ); // TODO
		$template->set( 'email', '' ); // TODO
		$template->set( 'createemail', $wgEnableEmail && $wgUser->isLoggedIn() );
		$template->set( 'userealname', $wgAllowRealName );
		$template->set( 'useemail', $wgEnableEmail );
		$template->set( 'emailrequired', $wgEmailConfirmToEdit );
		$template->set( 'canreset', $wgAuth->allowPasswordChange() );
		// $template->set( 'remember', $wgUser->getOption( 'rememberpassword' )  );

		global $wgLoginLanguageSelector;
		# Prepare language selection links as needed
		if ( $wgLoginLanguageSelector ) {
			$template->set( 'languages', $this->makeLanguageSelector() ); // FIXME: $this is not accessible in a static context
		}

		// Give authentication and captcha plugins a chance to modify the form
		$type = 'signup';
		$wgAuth->modifyUITemplate( $template, $type );

		if( SemanticSignupSettings::get( 'useCaptcha' ) && isset( $GLOBALS['wgCaptchaClass'] ) ) {
			$captchaObject = new $GLOBALS['wgCaptchaClass'];
			$captchaObject->injectUserCreate( $template );
		}

		ob_start();
		$template->execute();
		$text = ob_get_clean();

		return array(
			$text,
			'noparse' => true,
			'isHTML' => true
		);
	}

}
