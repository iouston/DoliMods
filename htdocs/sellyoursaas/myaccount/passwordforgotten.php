<?php
/* Copyright (C) 2007-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008-2011	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014       Teddy Andreotti    	<125155@supinfo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/sellyoursaas/myaccount/passwordforgotten.php
 *       \brief      Page to ask a new password
 */

define("NOLOGIN",1);	// This means this output page does not require to be logged.

// Load Dolibarr environment
include ('./mainmyaccount.inc.php');

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';

$langs=new Translate('', $conf);
$langs->setDefaultLang(GETPOST('lang','aZ09')?GETPOST('lang','aZ09'):'auto');

$langsen=new Translate('', $conf);
$langsen->setDefaultLang('en_US');

$langs->loadLangs(array("main","users","ldap","companies","bills","sellyoursaas@sellyoursaas","other","errors",'mails','paypal','paybox','stripe','withdrawals','other'));
$langsen->loadLangs(array("main","users","ldap","companies","bills","sellyoursaas@sellyoursaas","other","errors",'mails','paypal','paybox','stripe','withdrawals','other'));


// Security check
if (! empty($conf->global->SELLYOURSAAS_SECURITY_DISABLEFORGETPASSLINK))
{
    header("Location: ".DOL_URL_ROOT.'/');
    exit;
}

$id=GETPOST('id','int');
$action=GETPOST('action', 'alpha');
$mode=$dolibarr_main_authentication;
if (! $mode) $mode='http';

$username 		= GETPOST('username','alpha');
$hashreset		= GETPOST('hashreset','alpha');
$newpassword1   = GETPOST('newpassword1', 'none');
$newpassword2   = GETPOST('newpassword2', 'none');

$conf->entity 	= (GETPOST('entity','int') ? GETPOST('entity','int') : 1);

// Instantiate hooks of thirdparty module only if not already define
$hookmanager->initHooks(array('passwordforgottenpage'));


if (GETPOST('dol_hide_leftmenu','alpha') || ! empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_hide_topmenu','alpha') || ! empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu=1;
if (GETPOST('dol_optimize_smallscreen','alpha') || ! empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover','alpha') || ! empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile','alpha') || ! empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile=1;

$asknewpass=0;


/**
 * Actions
 */

// Validate new password
if ($hashreset)
{
    $editthirdparty = new Societe($db);
    if ($id > 0)
    {
    	$result=$editthirdparty->fetch($id);
    }
    if ($result <= 0)
    {
        $message = '<div class="error">'.$langs->trans("ErrorBadIdInLinkToResetPassword",$id).'</div>';
    }
    else
    {
    	$tmparray = explode(':', $editthirdparty->array_options['options_pass_temp']);

    	if ($hashreset == $tmparray[0])
        {
        	$maxdate = dol_stringtotime($tmparray[1]);
			if (dol_now() > $maxdate)
			{
				$langs->load("errors");
				$message = '<div class="error">'.$langs->trans("ErrorLinkToResetPasswordHasExpired").'</div>';
			}
			else
			{
        		$username = $editthirdparty->email;
        		if (GETPOST('confirmpasswordreset'))
        		{
        			$MINPASSWORDLENGTH = 6;
        			if (empty($newpassword1) && empty($newpassword2))
        			{
        				$langs->load("install");
        				$message = '<div class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Password")).'</div>';
        				$asknewpass = 1;
        			}
        			else if (empty($newpassword1) || empty($newpassword2) || ($newpassword1 != $newpassword2))
        			{
        				$langs->load("install");
        				$message = '<div class="error">'.$langs->trans("PasswordsMismatch").'</div>';
						$asknewpass = 1;
        			}
					elseif (strlen($newpassword1) < $MINPASSWORDLENGTH)
					{
						$langs->load("other");
						$message = '<div class="error">'.$langs->trans("YourPasswordMustHaveAtLeastXChars", $MINPASSWORDLENGTH).'</div>';
						$asknewpass = 1;
					}
					else
					{
						// Everything is ok to reset password
						$editthirdparty->array_options['options_password']=$newpassword1;
						$editthirdparty->array_options['options_pass_temp']='';
						$result=$editthirdparty->update($editthirdparty->id, $user, 0);
						$message = '<div class="ok">'.$langs->trans("YourPasswordHasBeenReset").'</div>';
						$asknewpass = 2;
					}
        		}
				else
				{
        			$asknewpass = 1;
				}
			}
        }
        else
        {
        	$langs->load("errors");
            $message = '<div class="error">'.$langs->trans("ErrorBadHashInLinkToResetPassword").'</div>';
        }
    }
}

// Action modif mot de passe
if ($action == 'buildnewpassword' && $username)
{
    $sessionkey = 'dol_antispam_value';
    $ok=(array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) == strtolower($_POST['code'])));

    // Verify code
    if (! $ok)
    {
        $message = '<div class="error">'.$langs->trans("ErrorBadValueForCode").'</div>';
    }
    else
    {
    	$thirdparty = new Societe($db);
    	$result = $thirdparty->fetch(0, '', '', '', '', '', '', '', '', '', $username);

        if ($result <= 0)
        {
            $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$username).'</div>';
            $username='';
        }
        else
        {
            /*if (! $edituser->email)
            {
                $message = '<div class="error">'.$langs->trans("ErrorLoginHasNoEmail").'</div>';
            }
            else
            {*/
        	include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
        		$hashreset = getRandomPassword(true);
        		$thirdparty->array_options['options_pass_temp']=$hashreset.':'.dol_print_date(dol_time_plus_duree(dol_now(), 1, 'd'), 'dayhourlog');
        		$result=$thirdparty->update($thirdparty->id, $user, 0);
                if ($result < 0)
                {
                    // Failed
                    $message = '<div class="error">'.$langs->trans("ErrorFailedToSetTemporaryHash").'</div>';
                }
                else
                {
                    // Success
                	include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

                	$url=$conf->global->SELLYOURSAAS_ACCOUNT_URL.'/passwordforgotten.php?id='.$thirdparty->id.'&hashreset='.$hashreset;
                	$trackid='thi'.$thirdparty->id;

                	// Send deployment email
                	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
                	include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
                	$formmail=new FormMail($db);

                	$arraydefaultmessage=$formmail->getEMailTemplate($db, 'thirdparty', $user, $langs, 0, 1, '(PasswordAssistance)');

                	//$mesg.='You may find more information on all different user/password reset process onto <a href="https://www.dolicloud.com/en-faq-i-forgot-my-login-or-password">the following page</a>';

                	$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $thirdparty);
                	complete_substitutions_array($substitutionarray, $langs, $thirdparty);

                	$substitutionarray['__URL_TO_RESET__'] = $url;

                	$subject = make_substitutions($arraydefaultmessage->topic, $substitutionarray, $langs);
                	$mesg = make_substitutions($arraydefaultmessage->content, $substitutionarray, $langs);

                	/*if (empty($subject))
                	{
                		$appli = $conf->global->SELLYOURSAAS_NAME;
                		$subject = '['.$appli.'] '.$langs->transnoentitiesnoconv("SubjectNewPasswordForYouCustomerDashboard");
                		$mesg = make_substitutions($arraydefaultmessage->content, $substitutionarray, $langs);
                	}*/

                	$newemail = new CMailFile($subject, $username, $conf->global->SELLYOURSAAS_MAIN_EMAIL, $mesg, array(),array(),array(),'','',0,-1,'','',$trackid,'','standard');

                	if ($newemail->sendfile() > 0)
                    {
                    	$message = '<div class="ok">'.$langs->trans("PasswordChangeRequestSent", $username, $username).'</div>';
                        $username='';
                    }
                    else
                    {
                    	$message.= '<div class="error">'.$newemail->error.'</div>';
                    }
                }
            //}
        }
    }
}


/**
 * View
 */

$dol_url_root = '';

// Title
$title='Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;
$title=$langs->trans("YourCustomerDashboard");

// Select templates
$template_dir = dirname(__FILE__).'/tpl/';

if (! $username) $focus_element = 'username';
else $focus_element = 'password';

// Send password button enabled ?
$disabled='disabled';
if (preg_match('/dolibarr/i',$mode)) $disabled='';
if (! empty($conf->global->MAIN_SECURITY_ENABLE_SENDPASSWORD)) $disabled='';	 // To force button enabled

// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
$width=0;
$rowspan=2;

// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
$width=0;
$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
if (! empty($conf->global->SELLYOURSAAS_LOGO_SMALL) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$conf->global->SELLYOURSAAS_LOGO_SMALL))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('thumbs/'.$conf->global->SELLYOURSAAS_LOGO_SMALL);
}
elseif (! empty($conf->global->SELLYOURSAAS_LOGO) && is_readable($conf->mycompany->dir_output.'/logos/'.$conf->global->SELLYOURSAAS_LOGO))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode($conf->global->SELLYOURSAAS_LOGO);
	$width=128;
}
elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png'))
{
	$urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png';
}
elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
{
	$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
}

// Security graphical code
if (function_exists("imagecreatefrompng") && ! $disabled)
{
	$captcha = 1;
	$captcha_refresh = img_picto($langs->trans("Refresh"),'refresh','id="captcha_refresh_img"');
}

// Execute hook getPasswordForgottenPageOptions (for table)
$parameters=array('entity' => GETPOST('entity','int'));
$hookmanager->executeHooks('getPasswordForgottenPageOptions',$parameters);    // Note that $action and $object may have been modified by some hooks
if (is_array($hookmanager->resArray) && ! empty($hookmanager->resArray)) {
	$morelogincontent = $hookmanager->resArray; // (deprecated) For compatibility
} else {
	$morelogincontent = $hookmanager->resPrint;
}

// Execute hook getPasswordForgottenPageExtraOptions (eg for js)
$parameters=array('entity' => GETPOST('entity','int'));
$reshook = $hookmanager->executeHooks('getPasswordForgottenPageExtraOptions',$parameters);    // Note that $action and $object may have been modified by some hooks.
$moreloginextracontent = $hookmanager->resPrint;

include $template_dir.'passwordforgotten.tpl.php';	// To use native PHP

