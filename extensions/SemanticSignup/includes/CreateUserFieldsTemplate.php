<?php
/**
 * Created on 6 Jan 2009 by Serhii Kutnii
 */

/**
 * Borrowed from standard UsercreateTemplate. Some minor changes have been made
 */

class CreateUserFieldsTemplate extends QuickTemplate {

	function addInputItem( $name, $value, $type, $msg ) {
		$this->data['extraInput'][] = array(
			'name' => $name,
			'value' => $value,
			'type' => $type,
			'msg' => $msg,
		);
	}

	function execute() {
		global $sfgTabIndex;

	?>
<div id="userlogin" style="float:none;">

	<h2><?php $this->msg( 'createaccount' ) ?></h2>
	<p id="userloginlink"><?php $this->html( 'link' ) ?></p>
	<?php $this->html( 'header' ); /* pre-table point for form plugins... */ ?>
	<?php if ( @$this->haveData( 'languages' ) ) { ?><div id="languagelinks"><p><?php $this->html( 'languages' ); ?></p></div><?php } ?>
	<table>
		<tr>
			<td class="mw-label"><label for='wpName2'><?php $this->msg( 'yourname' ) ?></label></td>
			<td class="mw-input">
				<input type='text' class='loginText' name="wpName" id="wpName2"
					tabindex="<?php echo $sfgTabIndex++; ?>"
					size='20' />
			</td>
		</tr>
		<tr>
			<td class="mw-label"><label for='wpPassword2'><?php $this->msg( 'yourpassword' ) ?></label></td>
			<td class="mw-input">
				<input type='password' class='loginPassword' name="wpPassword" id="wpPassword2"
					tabindex="<?php echo $sfgTabIndex++; ?>"
					value="" size='20' />
			</td>
		</tr>
	<?php if ( $this->data['usedomain'] ) {
		$doms = "";
		foreach ( $this->data['domainnames'] as $dom ) {
			$doms .= "<option>" . htmlspecialchars( $dom ) . "</option>";
		}
	?>
		<tr>
			<td class="mw-label"><?php $this->msg( 'yourdomainname' ) ?></td>
			<td class="mw-input">
				<select name="wpDomain" value="<?php $this->text( 'domain' ) ?>"
					tabindex="<?php echo $sfgTabIndex++; ?>">
					<?php echo $doms ?>
				</select>
			</td>
		</tr>
	<?php } ?>
		<tr>
			<td class="mw-label"><label for='wpRetype'><?php $this->msg( 'yourpasswordagain' ) ?></label></td>
			<td class="mw-input">
				<input type='password' class='loginPassword' name="wpRetype" id="wpRetype"
					tabindex="<?php echo $sfgTabIndex++; ?>"
					value=""
					size='20' />
			</td>
		</tr>
		<tr>
			<?php if ( $this->data['useemail'] ) { ?>
				<td class="mw-label"><label for='wpEmail'><?php $this->msg( 'youremail' ) ?></label></td>
				<td class="mw-input">
					<input type='text' class='loginText' name="wpEmail" id="wpEmail"
						tabindex="<?php echo $sfgTabIndex++; ?>"
						value="<?php $this->text( 'email' ) ?>" size='20' />
					<div class="prefsectiontip">
						<?php if ( $this->data['emailrequired'] ) {
									$this->msgWiki( 'prefs-help-email-required' );
						      } else {
									$this->msgWiki( 'prefs-help-email' );
						      } ?>
					</div>
				</td>
			<?php } ?>
			<?php if ( $this->data['userealname'] ) { ?>
				</tr>
				<tr>
					<td class="mw-label"><label for='wpRealName'><?php $this->msg( 'yourrealname' ) ?></label></td>
					<td class="mw-input">
						<input type='text' class='loginText' name="wpRealName" id="wpRealName"
							tabindex="<?php echo $sfgTabIndex++; ?>"
							size='20' />
					</td>
			<?php } ?>
		</tr>
		<tr>
			<td></td>
		</tr>
<?php
		if ( isset( $this->data['extraInput'] ) && is_array( $this->data['extraInput'] ) ) {
			foreach ( $this->data['extraInput'] as $inputItem ) { ?>
		<tr>
			<?php
				if ( !empty( $inputItem['msg'] ) && $inputItem['type'] != 'checkbox' ) {
					?><td class="mw-label"><label for="<?php
					echo htmlspecialchars( $inputItem['name'] ); ?>"><?php
					$this->msgWiki( $inputItem['msg'] ) ?></label><?php
				} else {
					?><td><?php
				}
			?></td>
			<td class="mw-input">
				<input type="<?php echo htmlspecialchars( $inputItem['type'] ) ?>" name="<?php
				echo htmlspecialchars( $inputItem['name'] ); ?>"
					tabindex="<?php echo $sfgTabIndex++; ?>"
					value="<?php
				if ( $inputItem['type'] != 'checkbox' ) {
					echo htmlspecialchars( $inputItem['value'] );
				} else {
					echo '1';
				}
					?>" id="<?php echo htmlspecialchars( $inputItem['name'] ); ?>"
					<?php
				if ( $inputItem['type'] == 'checkbox' && !empty( $inputItem['value'] ) )
					echo 'checked="checked"';
					?> /> <?php
					if ( $inputItem['type'] == 'checkbox' && !empty( $inputItem['msg'] ) ) {
						?>
				<label for="<?php echo htmlspecialchars( $inputItem['name'] ); ?>"><?php
					$this->msg( $inputItem['msg'] ) ?></label><?php
					}
				?>
			</td>
		</tr>
<?php

			}
		}
?>
		<tr>
			<td></td>
		</tr>
	</table>
<?php if ( @$this->haveData( 'uselang' ) ) { ?><input type="hidden" name="uselang" value="<?php $this->text( 'uselang' ); ?>" /><?php } ?>
</div>
<div id="signupend"><?php $this->msgWiki( 'signupend' ); ?></div>
<?php

	}
}
