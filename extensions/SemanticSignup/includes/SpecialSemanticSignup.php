<?php

/**
 * Special page to replace SpecialUserLogin/signup with an equivalent
 * SemanticForms form page and allow for additional (structured) data
 * to be collected on signup and used on the new user's userpage.
 *
 * @file SpecialSemanticSignup.php
 * @ingroup SemanticSignup
 *
 * @author Serhii Kutnii
 * @author Jeroen De Dauw <jeroendedauw@gmail.com>
 */
class SpecialSemanticSignup extends SpecialPage {

	private $mUserDataChecker = null;
	private $mUserPageUrl = '';

	public function __construct() {
		parent::__construct( 'SemanticSignup' );
		$this->mIncludable = false;

		$this->mUserDataChecker = new SES_UserAccountDataChecker();
	}

	private function userSignup() {

        //Hook for dynamic signup control
        wfRunHooks('SemanticSignupUserSignup');

		// Get user input and check the environment
		$this->mUserDataChecker->run();

		// Throw if data getting or environment checks have failed which indicates that account creation is impossible
		$checker_error = $this->mUserDataChecker->getError();
		if ( $checker_error ) {
			throw new Exception( $checker_error );
		}

		$user = $this->mUserDataChecker->mUser;

		$user->setEmail( $this->mUserDataChecker->mEmail );
		$user->setRealName( $this->mUserDataChecker->mRealname );

		$abortError = '';
		if ( !wfRunHooks( 'AbortNewAccount', array( $user, &$abortError ) ) )  {
			// Hook point to add extra creation throttles and blocks
			wfDebug( "LoginForm::addNewAccountInternal: a hook blocked creation\n" );
			throw new Exception( $abortError );
		}

		global $wgAccountCreationThrottle;
		global $wgUser, $wgRequest;

		if ( $wgAccountCreationThrottle && $wgUser->isPingLimitable() )  {
			$key = wfMemcKey( 'acctcreate', 'ip', $wgRequest->getIP() );
			$value = $wgMemc->incr( $key );

			if ( !$value ) {
				$wgMemc->set( $key, 1, 86400 );
			}

			if ( $value > $wgAccountCreationThrottle ) {
				throw new Exception( wfMsg( 'ses-throttlehit' ) );
			}
		}

		global $wgAuth;

		$addedUser = $wgAuth->addUser(
			$user,
			$this->mUserDataChecker->mPassword,
			$this->mUserDataChecker->mEmail,
			$this->mUserDataChecker->mRealname
		);

		if ( !$addedUser ) {
			throw new Exception( 'externaldberror' );
		}


		$user->addToDatabase();

		if ( $wgAuth->allowPasswordChange() )  {
			$user->setPassword( $this->mUserDataChecker->mPassword );
		}

		$user->setToken();

		$wgAuth->initUser( $user, false );

		$user->setOption( 'rememberpassword', $this->mUserDataChecker->mRemember ? 1 : 0 );
		$user->saveSettings();

		# Update user count
		$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$ssUpdate->doUpdate();

		global $wgLoginLanguageSelector;
		$language = $this->mUserDataChecker->mLanguage;

		if ( $wgLoginLanguageSelector && $language ) {
			$user->setOption( 'language', $language );
		}

		global $wgEmailAuthentication;

		if ( $wgEmailAuthentication && Sanitizer::validateEmail( $user->getEmail() ) ) {
			$status = $user->sendConfirmationMail();

			if ( !$status->isGood() ) {
				throw new Exception( wfMsg( 'ses-emailfailed' ) . "\n" . $status->getMessage() );
			}
		}

		$user->saveSettings();
		wfRunHooks( 'AddNewAccount', array( $user ) );
	}

	private function userLogin() {
		$user = $this->mUserDataChecker->mUser;
		$user->saveSettings();
		$user->invalidateCache();
		$user->setCookies();
	}

	private function createUserPage() {
		$form_title = Title::newFromText( SemanticSignupSettings::get( 'formName' ), SF_NS_FORM );
		$form = new Article( $form_title );
		$form_definition = $form->getContent();

		$page_title = Title::newFromText( $this->mUserDataChecker->mUser->getName(), NS_USER );
		$this->mUserPageUrl = htmlspecialchars( $page_title->getFullURL() );

		global $sfgFormPrinter;
		list ( $form_text, $javascript_text, $data_text, $form_page_title, $generated_page_name ) =
			$sfgFormPrinter->formHTML( $form_definition, true, false );

		$user_page = new Article( $page_title );

		global $wgUser;
		$wgUser = $this->mUserDataChecker->mUser;
		// TODO: doEdit removed; use internal API call
		$user_page->doEdit( $data_text, '', EDIT_FORCE_BOT );
	}

	private function printForm() {
		global $wgUser, $sfgFormPrinter, $wgOut, $wgFCKEditorDir;

		/*
		 * SemanticForms disable the form automatically if current user hasn't got edit rights
		 * so we have to use a bot account for the form request. Current user is being saved in
		 * the $old_user variable to be restored afterwards
		 */
		$old_user = null;
		if ( $wgUser->isAnon() ) {
			$old_user = $wgUser;
			$wgUser = User::newFromName( SemanticSignupSettings::get( 'botName' ) );
		}

		$form_title = Title::newFromText( SemanticSignupSettings::get( 'formName' ), SF_NS_FORM );
		$form = new Article( $form_title );
		$form_definition = $form->getContent();

		list ( $form_text, $javascript_text, $data_text, $form_page_title, $generated_page_name ) =
			$sfgFormPrinter->formHTML( $form_definition, false, false );

        /* Run hook allow externals to modify output of form */
        wfRunHooks('SemanticSignupPrintForm', array( &$form_text, &$javascript_text, &$data_text, &$form_page_title, &$generated_page_name ) );

		$text = <<<END
				<form name="createbox" id="sfForm" onsubmit="return validate_all()" action="" method="post" class="createbox">
END;
		$text .= $form_text . '</form>';

	    if ( $wgFCKEditorDir ) {
	    	$wgOut->addScript( '<script type="text/javascript" src="' . "$wgScriptPath/$wgFCKEditorDir" . '/fckeditor.js"></script>' . "\n" );
	    }

		if ( !empty( $javascript_text ) ) {
			$wgOut->addScript( '		<script type="text/javascript">' . "\n" . $javascript_text . '</script>' . "\n" );
		}

		$wgOut->addMeta( 'robots', 'noindex,nofollow' );
		$wgOut->addHTML( $text );

		// Restore the current user.
		if ( $old_user ) {
			$wgUser = $old_user;
		}
	}

	private function executeOnSubmit() {
		global $wgOut;

		try {
			$this->userSignup();
			$this->createUserPage();
			$this->userLogin();
			$wgOut->redirect( $this->mUserPageUrl );
		}
		catch ( Exception $e ) {
			$wgOut->addHTML( '<div class="error">' . $e->getMessage() . '</div>' );
			$this->printForm();
		}

		return true;
	}

	public function execute( $par ) {
		global $wgRequest, $wgOut;

		$this->setHeaders();

        //Hook for dynamic control page access
        if(!wfRunHooks('SemanticSignupUserSignupSpecial')) return true;

		if ( $wgRequest->getCheck( 'wpSave' ) ) {
			return $this->executeOnSubmit();
		} else {
			$this->printForm();
			return true;
		}
	}

}
 
