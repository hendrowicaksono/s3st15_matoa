<?php
/**
 *
 * Librarian login page
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com), Hendro Wicaksono (hendrowicaksono@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

// be sure that this file not accessed directly
if (!defined('INDEX_AUTH')) {
    die("can not access this file directly");
} elseif (INDEX_AUTH != 1) { 
    die("can not access this file directly");
}

if (defined('LIGHTWEIGHT_MODE')) {
    header('Location: index.php');
}

// required file
require LIB_DIR.'admin_logon.inc.php';

// https connection (if enabled)
if ($sysconf['https_enable']) {
    simbio_security::doCheckHttps($sysconf['https_port']);
}

// check if session browser cookie already exists
if (isset($_COOKIE['admin_logged_in'])) {
    header('location: admin/index.php');
}

// start the output buffering for main content
ob_start();

// if there is login action
if (isset($_POST['logMeIn'])) {
    if($_SESSION['csrfToken'] != $_POST['csrfToken']) {
        echo '<script type="text/javascript">alert(\''.__('Invalid Session').'\');</script>';
        die ('<a href="index.php?p=login">Try relogin</a>');
    }
    $username = strip_tags($_POST['userName']);
    $password = strip_tags($_POST['passWord']);
    if (!$username OR !$password) {
        echo '<script type="text/javascript">alert(\''.__('Please supply valid username and password').'\');</script>';
    } else {
        // destroy previous session set in OPAC
        simbio_security::destroySessionCookie(null, SENAYAN_MEMBER_SESSION_COOKIES_NAME, SENAYAN_WEB_ROOT_DIR, false);
        require SENAYAN_BASE_DIR.'admin/default/session.inc.php';
        // regenerate session ID to prevent session hijacking
        session_regenerate_id(true);
        // create logon class instance
        $logon = new admin_logon($username, $password, $sysconf['auth']['user']['method']);
        if ($sysconf['auth']['user']['method'] == 'ldap') {
            $ldap_configs = $sysconf['auth']['user'];
        }
        if ($logon->adminValid($dbs)) {

            # <!-- Captcha form processing - start -->
            if ($sysconf['captcha']['smc']['enable']) {
                if ($sysconf['captcha']['smc']['type'] == 'recaptcha') {
                    require_once LIB_DIR.$sysconf['captcha']['smc']['folder'].'/'.$sysconf['captcha']['smc']['incfile'];
                    $privatekey = $sysconf['captcha']['smc']['privatekey'];
                    $resp = recaptcha_check_answer ($privatekey,
                                          $_SERVER["REMOTE_ADDR"],
                                          $_POST["recaptcha_challenge_field"],
                                          $_POST["recaptcha_response_field"]);

                    if (!$resp->is_valid) {
                        // What happens when the CAPTCHA was entered incorrectly
                        session_unset();
                        header("location:index.php?p=login");
                        die();
                    }
                } elseif ($sysconf['captcha']['smc']['type'] == 'others') {
                    # other captchas here
                }
            }
            # <!-- Captcha form processing - end -->

            // set cookie admin flag
            setcookie('admin_logged_in', true, time()+14400, SENAYAN_WEB_ROOT_DIR);
            // write log
            utility::writeLogs($dbs, 'staff', $username, 'Login', 'Login success for user '.$username.' from address '.$_SERVER['REMOTE_ADDR']);
            echo '<script type="text/javascript">';
            if ($sysconf['login_message']) {
                echo 'alert(\''.__('Welcome to Library Automation, ').$logon->real_name.'\');';
            }
            #echo 'location.href = \'admin/index.php\';';
            echo 'location.href = \''.SENAYAN_WEB_ROOT_DIR.'admin/index.php\';';
            echo '</script>';
            exit();
        } else {
            // write log
            utility::writeLogs($dbs, 'staff', $username, 'Login', 'Login FAILED for user '.$username.' from address '.$_SERVER['REMOTE_ADDR']);
            // message
            $msg = '<script type="text/javascript">';
            $msg .= 'alert(\''.__('Wrong Username or Password. ACCESS DENIED').'\');';
            $msg .= 'history.back();';
            $msg .= '</script>';
            simbio_security::destroySessionCookie($msg, SENAYAN_SESSION_COOKIES_NAME, SENAYAN_WEB_ROOT_DIR.'admin', false);
            exit();
        }
    }
}
?>
<div id="loginForm">
    <noscript>
        <div style="font-weight: bold; color: #FF0000;"><?php echo __('Your browser does not support Javascript or Javascript is disabled. Application won\'t run without Javascript!'); ?><div>
    </noscript>
    <!-- Captcha preloaded javascript - start -->
    <?php if ($sysconf['captcha']['smc']['enable']) { ?>
      <?php if ($sysconf['captcha']['smc']['type'] == "recaptcha") { ?>
      <script type="text/javascript">
        var RecaptchaOptions = {
          theme : '<?php echo$sysconf['captcha']['smc']['recaptcha']['theme']; ?>',
          lang : '<?php echo$sysconf['captcha']['smc']['recaptcha']['lang']; ?>',
          <?php if($sysconf['captcha']['smc']['recaptcha']['customlang']['enable']) { ?>
                custom_translations : {
                        instructions_visual : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['instructions_visual']; ?>",
                        instructions_audio : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['instructions_audio']; ?>",
                        play_again : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['play_again']; ?>",
                        cant_hear_this : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['cant_hear_this']; ?>",
                        visual_challenge : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['visual_challenge']; ?>",
                        audio_challenge : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['audio_challenge']; ?>",
                        refresh_btn : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['refresh_btn']; ?>",
                        help_btn : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['help_btn']; ?>",
                        incorrect_try_again : "<?php echo $sysconf['captcha']['smc']['recaptcha']['customlang']['incorrect_try_again']; ?>",
                },
          <?php } ?>
        };
      </script>
      <?php } ?>
    <?php } ?>
    <!-- Captcha preloaded javascript - end -->

    <form action="index.php?p=login" method="post">
    <div class="heading1">Username</div>
    <div><input type="text" name="userName" id="userName" style="width: 80%;" /></div>
    <div class="heading1 marginTop">Password</div>
    <div><input type="password" name="passWord" style="width: 80%;" /></div>
    <?php $token = md5(date("Y/m/d h:i:j").uniqid(rand(), TRUE)); $_SESSION['csrfToken'] = $token; ?>
    <div><input type="hidden" name="csrfToken" value="<?php echo $token; ?>" /></div>

    <!-- Captcha in form - start -->
    <?php if ($sysconf['captcha']['smc']['enable']) { ?>
      <?php if ($sysconf['captcha']['smc']['type'] == "recaptcha") { ?>
      <div style="margin-left:40px; margin-right:auto; margin-top:10px;">
      <?php
        require_once LIB_DIR.$sysconf['captcha']['smc']['folder'].'/'.$sysconf['captcha']['smc']['incfile'];
        $publickey = $sysconf['captcha']['smc']['publickey'];
        echo recaptcha_get_html($publickey);
      ?>
      </div>
      <!-- <div><input type="text" name="captcha_code" id="captcha-form" style="width: 80%;" /></div> -->
    <?php 
      } elseif ($sysconf['captcha']['smc']['type'] == "others") {

      }
      #debugging
      #echo SENAYAN_WEB_ROOT_DIR.'lib/'.$sysconf['captcha']['folder'].'/'.$sysconf['captcha']['webfile'];
    } ?>
    <!-- Captcha in form - end -->

    <div class="marginTop"><input type="submit" name="logMeIn" value="Logon" id="loginButton" />
        <input type="button" value="Home" id="homeButton" class="button" onclick="javascript: location.href = 'index.php';" />
    </div>
    </form>
</div>
<script type="text/javascript">jQuery('#userName').focus();</script>

<?php
// main content
$main_content = ob_get_clean();

// page title
$page_title = $sysconf['library_name'].' :: Library Automation LOGIN';

if ($sysconf['template']['base'] == 'html') {
    // create the template object
    $template = new simbio_template_parser($sysconf['template']['dir'].'/'.$sysconf['template']['theme'].'/login_template.html');
    // assign content to markers
    $template->assign('<!--PAGE_TITLE-->', $page_title);
    $template->assign('<!--CSS-->', $sysconf['template']['css']);
    $template->assign('<!--MAIN_CONTENT-->', $main_content);
    // print out the template
    $template->printOut();
} else if ($sysconf['template']['base'] == 'php') {
    require_once $sysconf['template']['dir'].'/'.$sysconf['template']['theme'].'/login_template.inc.php';
}
exit();
?>
