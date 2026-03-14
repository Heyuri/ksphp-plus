<?php
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo 'Error: PHP version is '.phpversion().'. This script is compatible with PHP 8.0 and above.';
    exit();
}
if (ini_get('register_globals') == 1) {
    #print 'Error: register_globals is turned on. Please turn it off in your PHP configuration for security reasons.';
    #exit();
}

/*
The instructions have been moved to readme.md.
*/

// Configuration file
require_once("./conf.php");

// Version (for copyright notice)
$CONF['VERSION'] = '[20251109] (<span title="Heyuri Applicable Research & Development">Heyuri</span>, <span title="Hiru-ga-take">ヶ</span>, ＠Links, <span title="Giko-neko">擬古猫</span>)';

/* Launch */

// Determine language-specific subdirectory
// eg. TEMPLATE_LANGUAGE = 'en' -> './sub/en/'
$tmpl_lang = $CONF['TEMPLATE_LANGUAGE'] ?? 'ja';
$SUBDIR = './sub/' . $tmpl_lang . '/';

// Load language strings to be used in this file
$langfile = $SUBDIR . 'lang.php';
if (file_exists($langfile)) {
    require_once $langfile;
} else {
    die("Language file not found: $langfile");
}
// Translation helper
function T($key) {
    return $GLOBALS['MSG'][$key] ?? $key;
}

// Set error output level
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Demote "Undefined array key" warnings to notice
// https://github.com/php/php-src/issues/8906#issuecomment-1172810362
set_error_handler(function($errno, $error){
    if (!str_starts_with($error, 'Undefined array key')){
        return false;  //default error handler.
    }else{
        trigger_error($error, E_USER_NOTICE);
        return true;
    }
}, E_WARNING);

if ($CONF['RUNMODE'] == 2) {
    print T('BBS_OUT_OF_SERVICE');
    exit();
}
/* Process to prohibit access by host name */
if (Func::hostname_match($CONF['HOSTNAME_BANNED'],$CONF['HOSTAGENT_BANNED'])) {
    print T('ACCESS_PROHIBITED');
    exit();
}

// Override template file paths according to language
$CONF['TEMPLATE']          = $SUBDIR . 'template.html';
$CONF['TEMPLATE_ADMIN']    = $SUBDIR . 'tmpladmin.html';
$CONF['TEMPLATE_LOG']      = $SUBDIR . 'tmpllog.html';
$CONF['TEMPLATE_TREEVIEW'] = $SUBDIR . 'tmpltree.html';

// ----------------------------------------------------------------------
// Include file paths
// ----------------------------------------------------------------------

/**
 * Message log search module
 * @const PHP_GETLOG
 */
define('PHP_GETLOG', $SUBDIR . 'bbslog.php');

/**
 * Admin module
 * @const PHP_BBSADMIN
 */
define('PHP_BBSADMIN', $SUBDIR . 'bbsadmin.php');

/**
 * Tree view module
 * @const PHP_TREEVIEW
 */
define('PHP_TREEVIEW', $SUBDIR . 'bbstree.php');

/**
 * BBS with image upload function module
 * @const PHP_IMAGEBBS
 */
define('PHP_IMAGEBBS', $SUBDIR . 'bbsimage.php');

/**
 * HTML template library
 * (not language-dependent)
 * @const LIB_TEMPLATE
 */
define('LIB_TEMPLATE', './sub/patTemplate.php');

/**
 * ZIP file creation library
 * (not language-dependent)
 * @const LIB_PHPZIP
 */
define('LIB_PHPZIP', './sub/phpzip.inc.php');

/**
 * Constant for file include detection
 * @const INCLUDED_FROM_BBS
 */
define('INCLUDED_FROM_BBS', TRUE);

/**
 * Constant for current time
 * @const CURRENT_TIME
 */
define('CURRENT_TIME', time() - $CONF['DIFFTIME'] * 60 * 60 + $CONF['DIFFSEC']);

/* Execute */
{
    require_once(LIB_TEMPLATE);
    script_run();
}

/**
 * Script execution main process
 *
 * Basically, this is where the module branches are described
 */
function script_run() {

    $CONF = &$GLOBALS['CONF'];
    # Password setting page (bbsadmin.php)
    if ($CONF['ADMINPOST'] == '') {
        require_once(PHP_BBSADMIN);
        $bbsadmin = new Bbsadmin();
        $bbsadmin->procForm();
        $bbsadmin->refcustom();
        $bbsadmin->setusersession();
        if ($_POST['ad'] == 'ps') {
            $bbsadmin->prtpass($_POST['ps']);
        }
        else {
            $bbsadmin->prtsetpass();
        }
    }

    # Message log search mode (sub/bbslog.php)
    elseif ($_GET['m'] == 'g' or $_POST['m'] == 'g') {
        require_once(PHP_GETLOG);
        $getlog = new Getlog();
        $getlog->main();
    }
    # Admin mode (sub/bbsadmin.php)
    elseif ($_POST['m'] == 'ad') {
        if ($CONF['ADMINPOST'] and $CONF['ADMINKEY'] and $_POST['v'] == $CONF['ADMINKEY']
            and crypt($_POST['u'], $CONF['ADMINPOST']) == $CONF['ADMINPOST']) {
            require_once(PHP_BBSADMIN);
            $bbsadmin = new Bbsadmin();
            $bbsadmin->main();
        }
        elseif ($CONF['BBSMODE_IMAGE'] == 1) {
            require_once(PHP_IMAGEBBS);
            $imagebbs = new Imagebbs();
            $imagebbs->main();
        }
        else {
            $bbs = new Bbs();
            $bbs->main();
        }
    }
    # Tree view (sub/bbstree.php)
    elseif ($_GET['m'] == 'tree' or $_POST['m'] == 'tree') {
        require_once(PHP_TREEVIEW);
        $treeview = new Treeview();
        $treeview->main();
    }
    # Image bulletin board (sub/bbsimage.php)
    elseif ($CONF['BBSMODE_IMAGE'] == 1) {
        require_once(PHP_IMAGEBBS);
        $imagebbs = new Imagebbs();
        $imagebbs->main();
    }
    # Bulletin board mode (bbs.php)
    else {
        $bbs = new Bbs();
        $bbs->main();
    }
    exit();

}

/**
 * Detects if any honeypot fields are filled (for spam prevention)
 * @param array $fields Optional array of field values (defaults to $_POST)
 * @return bool True if any honeypot field is filled, false otherwise
 */
function detectHoneyPot($fields = null) {
    // If no fields are provided, use $_POST by default
    if ($fields === null) {
        $fields = $_POST;
    }

    // List of honeypot field names to check
    $honeypots = [
        'e-mail',
        'username',
        'subject',
        'comment',
        'firstname',
        'lastname',
        'city',
        'state',
        'zipcode'
    ];
    
    // Check if any of the honeypot fields are filled
    foreach ($honeypots as $name) {
        if (!empty($fields[$name])) {
            return true;
        }
    }

    // No honeypot fields are filled
    return false;
}

/**
 * Base web application class - Webapp
 *
 * Super class for each mode. Describes the processing common to each module.
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Webapp {

    var $c; /* Settings information */
    var $f; /* Form input */
    var $s = array(); /* Session-specific information such as the user's host */
    var $t; /* HTML template object */

    /**
     * Constructor
     *
     */
    function __construct() {
        $this->c = &$GLOBALS['CONF'];
        $this->t = new patTemplate();
        $this->t->readTemplatesFromFile($this->c['TEMPLATE']);
    }

    /**
     * Destructor
     */
    function destroy() {
    }

    /*20210625 Neko/2chtrip http://www.mits-jp.com/2ch/ */

function tripuse($key) {
    #$tripkey = '#istrip';? // String to be used as password (with #)
            $key = mb_convert_encoding($key, "SJIS-win", "UTF-8,SJIS-win");	// to, from
    #		$key = '#'.substr($key, strpos($key, '#'));
    
    # Trip
    # $trip is used for 0thello
    $trip = '';
    if (preg_match("/([^\#]*)\#(.+)/", $key, $match)) {
        if (strlen($match[2]) >= 12){
        # New conversion method
            $mark = substr($match[2], 0, 1);
            if ($mark == '#' || $mark == '$'){
                if (preg_match('|^#([[:xdigit:]]{16})([./0-9A-Za-z]{0,2})$|',$match[2],$str)){
                    $trip = substr(crypt(pack('H*', $str[1]), "$str[2].."), -10);
                } else {
                # For future expansion
                    $trip = '???';
                }
            } else {
    //		$trip = substr(base64_encode(pack('H*', sha1($match[2]))), 0, 12);
            $trip = substr(base64_encode(sha1($match[2],TRUE)),0,12);
            $trip = str_replace('+','.',$trip);
            }
        } else {
            $salt = substr($match[2]."H.", 1, 2);
            $salt = preg_replace("/[^\.-z]/", ".", $salt);
            $salt = strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef");
            $trip = substr(crypt($match[2], $salt),-10);
        }
    #	$match[1] = str_replace("◆", "◇", $match[1]);
    #	$_POST['FROM'] = $match[1]."</b> ◆".$trip."<b>";
    $trip ="◆".$trip;
    } else {
        $trip = str_replace("◆", "◇", $key);
    }
    return $trip;
    }
    


    /**
     * Form acquisition preprocessing
     */
    function procForm() {
        if (!$this->c['BBSMODE_IMAGE'] and $_SERVER['CONTENT_LENGTH'] > $this->c['MAXMSGSIZE'] * 5) {
            $this->prterror(T('POST_TOO_LARGE'));
        }
        if ($this->c['BBSHOST'] and $_SERVER['HTTP_HOST'] != $this->c['BBSHOST']) {
            $this->prterror(T('INVALID_CALLER'));
        }
        # Limited to POST or GET only
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->f = $_POST;
        }
        else {
            $this->f = $_GET;
        }
        # String replacement
        foreach ($this->f as $name => $value) {
            if (is_array($value)) {
                foreach (array_keys($value) as $valuekey) {
                    $value[$valuekey] = Func::html_escape($value[$valuekey]);
                }
            }
            else {
                $value = Func::html_escape($value);
            }
            $this->f[$name] = $value;
        }
    }

    /**
     * Session-specific information settings
     */
    function setusersession() {

        $this->s['U'] = $this->f['u'];
        $this->s['I'] = $this->f['i'];
        $this->s['C'] = $this->f['c'];
        $this->s['MSGDISP'] = $this->f['d'];
        $this->s['TOPPOSTID'] = $this->f['p'];
        # Get settings information cookies
        if ($this->c['COOKIE'] and $_COOKIE['c']
            and preg_match("/u=([^&]*)&i=([^&]*)&c=([^&]*)/", $_COOKIE['c'], $matches)) {
            if (!isset($this->f['u'])) {
                $this->s['U'] = urldecode($matches[1]);
            }
            if (!isset($this->f['i'])) {
                $this->s['I'] = urldecode($matches[2]);
            }
            if (!isset($this->f['c'])) {
                $this->s['C'] = $matches[3];
            }
        }
        # Get cookie for the UNDO button
        if ($this->c['COOKIE'] and $this->c['ALLOW_UNDO'] and $_COOKIE['undo']
            and preg_match("/p=([^&]*)&k=([^&]*)/", $_COOKIE['undo'], $matches)) {
            $this->s['UNDO_P'] = $matches[1];
            $this->s['UNDO_K'] = $matches[2];
        }
        # Default query
        $this->s['QUERY'] = "c=".$this->s['C'];
        if ($this->s['MSGDISP']) {
            $this->s['QUERY'] .= "&amp;d=".$this->s['MSGDISP'];
        }
        if ($this->s['TOPPOSTID']) {
            $this->s['QUERY'] .= "&amp;p=".$this->s['TOPPOSTID'];
        }
        # Default URL
        $this->s['DEFURL'] = $this->c['CGIURL'] . '?' . $this->s['QUERY'];
        # Initialize template variables
        $tmp = array_merge($this->c, $this->s);
        foreach ($tmp as $key => $val) {
            if (is_array($val)) unset($tmp[$key]);
        }
        $this->t->addGlobalVars($tmp);
    }

    /**
     * Error indication
     *
     * @access  public
     * @param   String  $err_message  Error message
     */
    function prterror($err_message) {
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' Error');
        $this->t->addVar('error', 'ERR_MESSAGE', $err_message);
        if (isset($this->s['DEFURL'])) {
            $this->t->setAttribute('backnavi', 'visibility', 'visible');
        }
        $this->t->displayParsedTemplate('error');
        print $this->prthtmlfoot ();
        $this->destroy();
        exit();
    }

    /**
     * Display HTML header section
     *
     * @access  public
     * @param   String  $title        HTML title
     * @param   String  $customhead   Custom header in the head tag
     * @param   String  $customstyle  Custom style sheets in the style tag
     * @return  String  HTML data
     */
    function prthtmlhead($title = "", $customhead = "", $customstyle = "") {
        $this->t->clearTemplate('header');
        $this->t->addVars('header', array(
            'TITLE' => $title,
            'CUSTOMHEAD' => $customhead,
            'CUSTOMSTYLE' => $customstyle,
        ));
        $htmlstr = $this->t->getParsedTemplate('header');
        return $htmlstr;
    }

    /**
     * Display HTML footer section
     *
     * @access  public
     * @return  String  HTML data
     */
    function prthtmlfoot() {
        if ($this->c['SHOW_PRCTIME'] and $this->s['START_TIME']) {
            $duration = Func::microtime_diff($this->s['START_TIME'], microtime());
            $duration = sprintf("%0.6f", $duration);
            $this->t->setAttribute('duration', 'visibility', 'visible');
            $this->t->addVar('duration', 'DURATION', $duration);
        }
        $htmlstr = $this->t->getParsedTemplate('footer');
        return $htmlstr;
    }

    /**
     * Copyright notice
     */
    function prtcopyright() {
        $copyright = $this->t->getParsedTemplate('copyright');
        return $copyright;
    }

    /**
     * Redirector output with META tags
     *
     * @access  public
     * @param   String  $redirecturl    URL to redirect
     */
    function prtredirect($redirecturl) {
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' - ' . T('URL_REDIRECTION'),
            "<meta http-equiv=\"refresh\" content=\"1;url={$redirecturl}\">\n");
        $this->t->addVar('redirect', 'REDIRECTURL', $redirecturl);
        $this->t->displayParsedTemplate('redirect');
        print $this->prthtmlfoot ();
    }

    /**
     * Display message contents definition
     */
    function setmessage($message, $mode = 0, $tlog = '') {

        if (count($message) < 10) {
            return;
        }
        $message['WDATE'] = Func::getdatestr($message['NDATE'], $this->c['DATEFORMAT']);
		#20181102 Gikoneko: Escape special characters
		$message['MSG'] = preg_replace("/{/i","&#123;", $message['MSG'], -1);
        $message['MSG'] = preg_replace("/}/i","&#125;", $message['MSG'], -1);

	#20241016 Heyuri: Deprecated by ytthumb.js, embedding each video in browser slows stuff down a lot
        ##20200524 Gikoneko: youtube embedding
        #$message['MSG'] = preg_replace("/<a href=\"https:\/\/youtu.be\/([^\"]+?)\" target=\"link\">([^<]+?)<\/a>/",
        #"<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allow=\"autoplay; encrypted-media\" allowfullscreen></iframe>\r<a href=\"https://youtu.be/$1\">$2</a>", $message['MSG']);
        ##20200524 Gikoneko: youtube embedding 2
        #$message['MSG'] = preg_replace("/<a href=\"https:\/\/www.youtube.com\/watch\?v=([^\"]+?)\" target=\"link\">([^<]+?)<\/a>/",
        #"<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allow=\"autoplay; encrypted-media\" allowfullscreen></iframe>\r<a href=\"https://www.youtube.com/watch?v=$1\">$2</a>", $message['MSG']);
        ##20200524 Gikoneko: youtube embedding 3
        #$message['MSG'] = preg_replace("/<a href=\"https:\/\/m.youtube.com\/watch\?v=([^\"]+?)\" target=\"link\">([^<]+?)<\/a>/",
        #"<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/$1\" frameborder=\"0\" allow=\"autoplay; encrypted-media\" allowfullscreen></iframe>\r<a href=\"https://m.youtube.com/watch?v=$1\">$2</a>", $message['MSG']);

        # "Reference"
        if (!$mode) {
            $message['MSG'] = preg_replace("/<a href=\"m=f&s=(\d+)[^>]+>([^<]+)<\/a>$/i",
                "<a href=\"{$this->c['CGIURL']}?m=f&amp;s=$1&amp;{$this->s['QUERY']}\">$2</a>", $message['MSG'], 1);
            $message['MSG'] = preg_replace("/<a href=\"mode=follow&search=(\d+)[^>]+>([^<]+)<\/a>$/i",
                "<a href=\"{$this->c['CGIURL']}?m=f&amp;s=$1&amp;{$this->s['QUERY']}\">$2</a>", $message['MSG'], 1);
        } else {
            $message['MSG'] = preg_replace("/<a href=\"m=f&s=(\d+)[^>]+>([^<]+)<\/a>$/i",
                "<a href=\"#a$1\">$2</a>", $message['MSG'], 1);
            $message['MSG'] = preg_replace("/<a href=\"mode=follow&search=(\d+)[^>]+>([^<]+)<\/a>$/i",
                "<a href=\"#a$1\">$2</a>", $message['MSG'], 1);
        }
        if ($mode == 0 or ($mode == 1 and $this->c['OLDLOGBTN'])) {

            if (!$this->c['FOLLOWWIN']) { $newwin = " target=\"link\""; }
            else { $newwin = ''; }
            $spacer = "&nbsp;&nbsp;&nbsp;";
            $lnk_class = "class=\"internal\"";
            # Follow-up post button
            $message['BTNFOLLOW'] = '';
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNFOLLOW'] = "$spacer<a href=\"{$this->c['CGIURL']}"
                    ."?m=f&amp;s={$message['POSTID']}&amp;".$this->s['QUERY'];
                if ($this->f['w']) {
                    $message['BTNFOLLOW'] .= "&amp;w=".$this->f['w'];
                }
                if ($mode == 1) {
                    $message['BTNFOLLOW'] .= "&amp;ff=$tlog";
                }
                $message['BTNFOLLOW'] .= "\"$newwin $lnk_class title=\"" . T('TITLE_FOLLOWUP') . "\" >{$this->c['TXTFOLLOW']}</a>";
            }
            # Search by user button
            $message['BTNAUTHOR'] = '';
            if ($message['USER'] != $this->c['ANONY_NAME'] and $this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNAUTHOR'] = "$spacer<a href=\"{$this->c['CGIURL']}"
                    ."?m=s&amp;s=". urlencode(preg_replace("/<[^>]*>/", '', $message['USER'])) ."&amp;".$this->s['QUERY'];
                if ($this->f['w']) {
                    $message['BTNAUTHOR'] .= "&amp;w=".$this->f['w'];
                }
                if ($mode == 1) {
                    $message['BTNAUTHOR'] .= "&amp;ff=$tlog";
                }
                $message['BTNAUTHOR'] .= "\" target=\"link\" $lnk_class title=\"" . T('TITLE_SEARCH_BY_USER') . "\" >{$this->c['TXTAUTHOR']}</a>";
            }
            # Thread view button
            if (!$message['THREAD']) {
                $message['THREAD'] = $message['POSTID'];
            }
            $message['BTNTHREAD'] = '';
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNTHREAD'] = "$spacer<a href=\"{$this->c['CGIURL']}?m=t&amp;s={$message['THREAD']}&amp;".$this->s['QUERY'];
                if ($mode == 1) {
                    $message['BTNTHREAD'] .= "&amp;ff=$tlog";
                }
                $message['BTNTHREAD'] .= "\" target=\"link\" $lnk_class title=\"" . T('TITLE_THREAD_VIEW') . "\" >{$this->c['TXTTHREAD']}</a>";
            }
            # Tree view button
            $message['BTNTREE'] = '';
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                $message['BTNTREE'] = "$spacer<a href=\"{$this->c['CGIURL']}?m=tree&amp;s={$message['THREAD']}&amp;".$this->s['QUERY'];
                if ($mode == 1) {
                    $message['BTNTREE'] .= "&amp;ff=$tlog";
                }
                $message['BTNTREE'] .= "\" target=\"link\" $lnk_class title=\"" . T('TITLE_TREE_VIEW') . "\" >{$this->c['TXTTREE']}</a>";
            }
            # UNDO button
            $message['BTNUNDO'] = '';
            if ($this->c['ALLOW_UNDO'] and isset($this->s['UNDO_P']) and $this->s['UNDO_P'] == $message['POSTID']) {
                $message['BTNUNDO'] = "$spacer<a href=\"{$this->c['CGIURL']}?m=u&amp;s={$message['POSTID']}&amp;".$this->s['QUERY'];
                $message['BTNUNDO'] .= "\" $lnk_class title=\"" . T('TITLE_DELETE_POST') . "\" >{$this->c['TXTUNDO']}</a>";
            }
            # Button integration
            $message['BTN'] = $message['BTNFOLLOW']. $message['BTNAUTHOR']. $message['BTNTHREAD']. $message['BTNTREE']. $message['BTNUNDO'];
        }
        # Email address
        if ($message['MAIL']) {
            $message['USER'] = "<a href=\"mailto:{$message['MAIL']}\">{$message['USER']}</a>";
        }
        # Change quote color
        $message['MSG'] = preg_replace("/(^|\r)(\&gt;[^\r]*)/", "$1<span class=\"q\">$2</span>", $message['MSG']);
        $message['MSG'] = str_replace("</span>\r<span class=\"q\">", "\r", $message['MSG']);
        # Environment variables
        $message['ENVADDR'] = '';
        $message['ENVUA'] = '';
        $message['ENVBR'] = '';
        if ($this->c['IPPRINT'] or $this->c['UAPRINT']) {
            if ($this->c['IPPRINT']) {
                $message['ENVADDR'] = $message['PHOST'];
            }
            if ($this->c['UAPRINT']) {
                $message['ENVUA'] = $message['AGENT'];
            }
            if ($this->c['IPPRINT'] and $this->c['UAPRINT']) {
                $message['ENVBR'] = '<br>';
            }
            if ($message['ENVADDR'] or $message['ENVUA']) {
                $this->t->clearTemplate('envlist');
                $this->t->setAttribute("envlist", "visibility", "visible");
                $this->t->addVars('envlist', array(
                    'ENVADDR' => $message['ENVADDR'],
                    'ENVUA' => $message['ENVUA'],
                    'ENVBR' => $message['ENVBR'],
                ));
            }
        }
        # Whether or not to display images on the image BBS
        if (!$this->c['SHOWIMG']) {
            $message['MSG'] = Func::conv_imgtag($message['MSG']);
        }
        # Convert img tags even if there is no image file
        elseif (preg_match("/<a href=[^>]+><img [^>]*?src=\"([^\"]+)\"[^>]+><\/a>/i", $message['MSG'], $matches)) {
            if (!file_exists($matches[1])) {
                $message['MSG'] = Func::conv_imgtag($message['MSG']);
            }
        }
        # Message display content definition
        $this->t->clearTemplate('message');
        $this->t->addVars('message', $message);
    }

    /**
     * Single message output
     *
     * Outputs the HTML of a message based on the message array.
     * Supports the message log module.
     *
     * @access  public
     * @param   Array   $message    Message
     * @param   Integer $mode       0: Bulletin board / 1: Message log search (with buttons displayed) / 2: Message log search (without buttons displayed) / 3: For message log output file
     * @param   String  $tlog       Specified log file
     * @return  String  Message HTML data
     */
    function prtmessage($message, $mode = 0, $tlog = '') {
        $this->setmessage($message, $mode, $tlog);
        $prtmessage = $this->t->getParsedTemplate('message');
        return $prtmessage;
    }

    /**
     * Log reading
     *
     * Reads the log file, returns it as a line array.
     *
     * @access  public
     * @param   String  $logfilename  Log file name (optional)
     * @return  Array   Log line array
     */
    function loadmessage($logfilename = "") {
        if ($logfilename) {
            preg_match("/^([\w.]*)$/", $logfilename, $matches);
            $logfilename = $this->c['OLDLOGFILEDIR']."/".$matches[1];
        }
        else {
            $logfilename = $this->c['LOGFILENAME'];
        }
        if (!file_exists($logfilename)) {
            $this->prterror(T('FAILED_TO_READ_MESSAGE'));
        }
        $logdata = file($logfilename);
        return $logdata;
    }

    /**
     * Get single message
     *
     * Converts a log line to a message array and returns it.
     *
     * @access  public
     * @param   String  $logline  Log line
     * @return  Array   Message array
     */
    function getmessage($logline) {

        $logsplit = @explode (',', rtrim($logline));
        if (count($logsplit) < 10) {
            return;
        }
        $i = 6;
        while ($i <= 9) {
            $logsplit[$i] = strtr ($logsplit[$i], "\0", ",");
            $logsplit[$i] = str_replace ("&#44;", ",", $logsplit[$i]);
            $i++;
        }
        $message = array();
        $messagekey = array('NDATE', 'POSTID', 'PROTECT', 'THREAD', 'PHOST', 'AGENT', 'USER', 'MAIL', 'TITLE', 'MSG', 'REFID', 'RESERVED1', 'RESERVED2', 'RESERVED3', );
        $logsplitcount = count($logsplit);
        $i = 0;
        while ($i < $logsplitcount) {
            if ($i > 12) { break; }
            $message[$messagekey[$i]] = $logsplit[$i];
            $i++;
        }
        return $message;
    }

    /**
     * Reflect user settings
     */
    function refcustom() {

        $this->c['LINKOFF'] = 0;
        $this->c['HIDEFORM'] = 0;
        $this->c['RELTYPE'] = 0;
        if (!isset($this->c['SHOWIMG'])) {
            $this->c['SHOWIMG'] = 0;
        }
        $flgcolorchanged = FALSE;

        $colors = array(
            'C_BACKGROUND',
            'C_TEXT',
            'C_A_COLOR',
            'C_A_VISITED',
            'C_SUBJ',
            'C_QMSG',
            'C_A_ACTIVE',
            'C_A_HOVER',
        );
        $flags = array(
            'GZIPU',
            'RELTYPE',
            'AUTOLINK',
            'FOLLOWWIN',
            'COOKIE',
            'LINKOFF',
            'HIDEFORM',
            'SHOWIMG',
        );
        # Update from settings string
        if ($this->f['c']) {
            $strflag = '';
            $formc = $this->f['c'];
            if (strlen($formc) > 5) {
                $formclen = strlen($formc);
                $strflag = substr($formc, 0, 2);
                $currentpos = 2;
                foreach ($colors as $confname) {
                    $colorval = Func::base64_threebytehex(substr($formc, $currentpos, 4));
                    if (strlen($colorval) == 6 and strcasecmp($this->c[$confname], $colorval) != 0) {
                        $flgcolorchanged = TRUE;
                        $this->c[$confname] = $colorval;
                    }
                    $currentpos += 4;
                    if ($currentpos > $formclen) {
                        break;
                    }
                }
            }
            elseif (strlen($formc) == 2) {
                $strflag = $formc;
            }
            if ($strflag) {
                $flagbin = str_pad(base_convert ($strflag, 32, 2), count($flags), "0", STR_PAD_LEFT);
                $currentpos = 0;
                foreach ($flags as $confname) {
                    $this->c[$confname] = substr($flagbin, $currentpos, 1);
                    $currentpos++;
                }
            }
        }
        # Update settings information
        if ($this->f['m'] == 'p' or $this->f['m'] == 'c' or $this->f['m'] == 'g') {
            $this->f['a'] ? $this->c['AUTOLINK'] = 1 : $this->c['AUTOLINK'] = 0;
            $this->f['g'] ? $this->c['GZIPU'] = 1 : $this->c['GZIPU'] = 0;
            $this->f['loff'] ? $this->c['LINKOFF'] = 1 : $this->c['LINKOFF'] = 0;
            $this->f['hide'] ? $this->c['HIDEFORM'] = 1 : $this->c['HIDEFORM'] = 0;
            $this->f['sim'] ? $this->c['SHOWIMG'] = 1 : $this->c['SHOWIMG'] = 0;
            if ($this->f['m'] == 'c') {
                $this->f['fw'] ? $this->c['FOLLOWWIN'] = 1 : $this->c['FOLLOWWIN'] = 0;
                $this->f['rt'] ? $this->c['RELTYPE'] = 1 : $this->c['RELTYPE'] = 0;
                $this->f['cookie'] ? $this->c['COOKIE'] = 1 : $this->c['COOKIE'] = 0;
            }
        }
        # Special conditions
        if ($this->c['BBSMODE_ADMINONLY'] != 0) {
            ($this->f['m'] == 'f' or ($this->f['m'] == 'p' and $this->f['write'])) ? $this->c['HIDEFORM'] = 0 : $this->c['HIDEFORM'] = 1;
        }
        # Update the settings string
        {
            $flagbin = '';
            foreach ($flags as $confname) {
                $this->c[$confname] ? $flagbin .= '1' : $flagbin .= '0';
            }
            $flagvalue = str_pad(base_convert ($flagbin, 2, 32), 2, "0", STR_PAD_LEFT);

            if ($flgcolorchanged) {
                $this->f['c'] = $flagvalue . substr($this->f['c'], 2);
            }
            else {
                $this->f['c'] = $flagvalue;
            }
        }
    }

    /**
     * HTTP header settings
     */
    function sethttpheader() {
        header('Content-Type: text/html; charset=UTF-8');
        header("X-XSS-Protection: 1; mode=block");
        // Remove X-Frame-Options (not needed when using CSP)
        header_remove("X-Frame-Options");
        // Allow embedding from anywhere
        header("Content-Security-Policy: frame-ancestors *;");

    }

    /**
     * Start execution time measurement
     */
    function setstarttime() {
        $this->s['START_TIME'] = microtime();
    }

}

/**
 * Standard bulletin board class - Bbs
 *
 * A bulletin board display class for PC.
 * If you want to customize/extend the bulletin board function itself, inherit this class.
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Bbs extends Webapp {

    /**
     * Constructor
     *
     */
    function __construct() {
        parent::__construct();
    }

    /**
     * Main process
     */
    function main() {
        # Start execution time measurement
        $this->setstarttime();
        # Form acquisition preprocessing
        $this->procForm();
        # Reflect user settings
        $this->refcustom();
        $this->setusersession();
        # gzip compression transfer
        if ($this->c['GZIPU']) {
            ob_start("ob_gzhandler");
        }
	# Prevent accidental posting when opening settings
	if ($this->f['setup']) {
	$this->prtcustom();
	return;
	}
        # Post operation
        if ($this->f['m'] == 'p' and trim($this->f['v'])) {
            # Get environment variables
            $this->setuserenv();
            # Parameter check
            $posterr = $this->chkmessage();
            # Post operation
            if (!$posterr) {
                $posterr = $this->putmessage($this->getformmessage());
            }
            # Douple post error, etc.
            if ($posterr == 1) {
                $this->prtmain();
            }
            # Protect code redisplayed due to time lapse
            elseif ($posterr == 2) {
                if ($this->f['f']) {
                    $this->prtfollow(TRUE);
                }
                elseif ($this->f['write']) {
                    $this->prtnewpost(TRUE);
                }
                else {
                    $this->prtmain(TRUE);
                }
            }
            # Entering admin mode
            elseif ($posterr == 3) {
                define('BBS_ACTIVATED', TRUE);
                require_once(PHP_BBSADMIN);
                $bbsadmin = new Bbsadmin($this);
                $bbsadmin->main();
            }
            # Post completion page
            elseif ($this->f['f']) {
                $this->prtputcomplete();
            }
            else {
                $this->prtmain();
            }
        }
        # Display follow-up page
        elseif ($this->f['m'] == 'f') {
            $this->prtfollow();
        }
        # Post search
        elseif ($this->f['m'] == 't' or $this->f['m'] == 's') {
            $this->prtsearchlist();
        }
        # Display user settings page
        elseif ($this->f['setup']) {
            $this->prtcustom();
        }
        # User settings process
        elseif ($this->f['m'] == 'c') {
            $this->setcustom();
        }
        # New post
        elseif ($this->f['m'] == 'p' and $this->f['write']) {
            $this->prtnewpost();
        }
        # UNDO process
        elseif ($this->f['m'] == 'u') {
            $this->prtundo();
        }
        # Default: bulletin board display
        else {
            $this->prtmain();
        }

        if ($this->c['GZIPU']) {
            ob_end_flush();
        }
    }

    /**
     * Display bulletin board
     *
     * @access  public
     * @param   Boolean  $retry  Retry flag
     */
    function prtmain($retry = FALSE) {
        # Get display message
        list ($logdatadisp, $bindex, $eindex, $lastindex) = $this->getdispmessage();
        # Form section settings
        $dtitle = "";
        $dmsg = "";
        $dlink = "";
        if ($retry) {
            $dtitle = $this->f['t'];
            $dmsg = $this->f['v'];
            $dlink = $this->f['l'];
        }
        $this->setform ($dtitle, $dmsg, $dlink);
        # HTML header partial output
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE']);
        # Upper main section
        $this->t->displayParsedTemplate('main_upper');
        # Display message
        foreach ( $logdatadisp as $msgdata) {
            print $this->prtmessage($this->getmessage($msgdata), 0, 0);
        }
        # Message information
        if ($this->s['MSGDISP'] < 0) {
            $msgmore = '';
        }
        elseif ($eindex > 0) {
		$msgmore = str_replace(['{BINDEX}','{EINDEX}'], [$bindex,$eindex], T('POSTS_RANGE_NEWEST_TO_OLDEST'));
        }
        else {
            $msgmore = T('NO_UNREAD_MESSAGES') . ' ';
        }
        if ($eindex >= $lastindex) {
            $msgmore .= T('NO_POSTS_BELOW');
        }
        $this->t->addVar('main_lower', 'MSGMORE', $msgmore);
        # Navigation buttons
        if ($eindex > 0) {
            if ($eindex >= $lastindex) {
                $this->t->setAttribute("nextpage", "visibility", "hidden");
            }
            else {
                $this->t->addVar('nextpage', 'EINDEX', $eindex);
            }
            if (!$this->c['SHOW_READNEWBTN']) {
                $this->t->setAttribute("readnew", "visibility", "hidden");
            }
        }
        # Post as administrator
        if ($this->c['BBSMODE_ADMINONLY'] == 0) {
            $this->t->setAttribute("adminlogin", "visibility", "hidden");
        }
        # Lower main section
        $this->t->displayParsedTemplate('main_lower');
        print $this->prthtmlfoot ();
    }

    /**
     * Get display range messages and parameters
     *
     * @access  public
     * @return  Array   $logdatadisp  Log line array
     * @return  Integer $bindex       Beginning of index
     * @return  Integer $eindex       End of index
     * @return  Integer $lastindex    End of all logs index
     */
    function getdispmessage() {

        $logdata = $this->loadmessage();
        # Unread pointer (latest POSTID)
        $items = @explode (',', $logdata[0], 3);
        $toppostid = $items[1];
        # Number of posts displayed
        $msgdisp = Func::fixnumberstr($this->f['d']);
        if ($msgdisp === FALSE) {
            $msgdisp = $this->c['MSGDISP'];
        }
        elseif ($msgdisp < 0) {
            $msgdisp = -1;
        }
        elseif ($msgdisp > $this->c['LOGSAVE']) {
            $msgdisp = $this->c['LOGSAVE'];
        }
        if ($this->f['readzero']) {
            $msgdisp = 0;
        }
        # Beginning of index
        $bindex = $this->f['b'];
        if (!$bindex) {
            $bindex = 0;
        }
        # For the next and subsequent pages
        if ($bindex > 1) {
            # If there are new posts, shift the beginning of the index
            if ($toppostid > $this->f['p']) {
                $bindex += ($toppostid - $this->f['p']);
            }
            # Don't update unread pointer
            $toppostid = $this->f['p'];
        }
        # End of index
        $eindex = $bindex + $msgdisp;
        # Unread reload
        if ($this->f['readnew'] or ($msgdisp == '0' and $bindex == 0)) {
            $bindex = 0;
            $eindex = $toppostid - $this->f['p'];
        }
        # For the last page, truncate
        $lastindex = count($logdata);
        if ($eindex > $lastindex) {
            $eindex = $lastindex;
        }
        # Display posts -1
        if ($msgdisp < 0) {
            $bindex = 0;
            $eindex = 0;
        }
        # Display messages
        if ($bindex == 0 and $eindex == 0) {
            $logdatadisp = array();
        }
        else {
            $logdatadisp = array_splice ($logdata, $bindex, ($eindex - $bindex));
            if ($this->c['RELTYPE'] and ($this->f['readnew'] or ($msgdisp == '0' and $bindex == 0))) {
                $logdatadisp = array_reverse($logdatadisp);
            }
        }
        $this->s['TOPPOSTID'] = $toppostid;
        $this->s['MSGDISP'] = $msgdisp;
        $this->t->addGlobalVars(array(
            'TOPPOSTID' => $this->s['TOPPOSTID'],
            'MSGDISP' => $this->s['MSGDISP']
        ));
        return array($logdatadisp, $bindex + 1, $eindex, $lastindex);
    }

    /**
     * Form section settings
     *
     * @access  public
     * @param   String  $dtitle     Initial value of the form title
     * @param   String  $dmsg       Initial value for the form contents
     * @param   String  $dlink      Initial value for the form link
     */
    function setform($dtitle, $dmsg, $dlink, $mode = '') {
        # Protect code generation
        $pcode = Func::pcode();
        if (!$mode) {
            $mode = '<input type="hidden" name="m" value="p" />';
        }
        $this->t->addVars('form', array(
            'MODE' => $mode,
            'PCODE' => $pcode,
        ));
        # Hide post form
        if ($this->c['HIDEFORM'] and $this->f['m'] != 'f' and !$this->f['write']) {
            $this->t->addVar('postform', 'mode', 'hide');
        }
        else {
            $this->t->addVars('postform', array(
                'DTITLE' => $dtitle,
                'DMSG' => $dmsg,
                'DLINK' => $dlink,
            ));
        }
        # Settings and links lines
        if ($this->f['m'] != 'f' and !isset($this->f['f']) and !$this->f['write']) {
            # Counter
            if ($this->c['SHOW_COUNTER']) {
                $counter = $this->counter();
                $counter = number_format($counter);
                $this->t->addVar("counter", 'COUNTER', $counter);
                $this->t->setAttribute("counter", "visibility", "visible");
            }
            if ($this->c['CNTFILENAME']) {
                $mbrcount = $this->mbrcount();
                $mbrcount = number_format($mbrcount);
                $this->t->addVar("mbrcount", 'MBRCOUNT', $mbrcount);
                $this->t->setAttribute("mbrcount", "visibility", "visible");
            }
            if (!$this->c['SHOW_COUNTER'] and !$this->c['CNTFILENAME']) {
                $this->t->setAttribute("counterrow", "visibility", "hidden");
            }
            if ($this->c['BBSMODE_ADMINONLY'] == 0) {
                if ($this->c['AUTOLINK']) $this->t->addVar('formconfig', 'CHK_A', ' checked="checked"');
                if ($this->c['HIDEFORM']) $this->t->addVar('formconfig', 'CHK_HIDE', ' checked="checked"');
            }
            else {
                $this->t->setAttribute("formconfig", "visibility", "hidden");
            }
            # Hide link line
            if ($this->c['LINKOFF']) {
                $this->t->addVar('extraform', 'CHK_LOFF', ' checked="checked"');
                $this->t->setAttribute("linkrow", "visibility", "hidden");
            }
            # Hide help line
            if ($this->c['BBSMODE_ADMINONLY'] != 1) {
                if (!$this->c['ALLOW_UNDO']) {
                    $this->t->setAttribute("helpundo", "visibility", "hidden");
                }
            }
            else {
                $this->t->setAttribute("helprow", "visibility", "hidden");
            }
            # Navigation buttons line
            if (!$this->c['SHOW_READNEWBTN']) {
                $this->t->setAttribute("readnewbtn", "visibility", "hidden");
            }
            if (!($this->c['HIDEFORM'] and $this->c['BBSMODE_ADMINONLY'] == 0)) {
                $this->t->setAttribute("newpostbtn", "visibility", "hidden");
            }
        }
        else {
            $this->t->setAttribute("extraform", "visibility", "hidden");
        }
    }

    /**
     * Display follow-up page
     *
     * @access  public
     * @param   Boolean $retry  Retry flag
     */
    function prtfollow($retry = FALSE) {

        if (!$this->f['s']) {
            $this->prterror(T('NO_PARAMETERS'));
        }

        # Administrator authentication
        if ($this->c['BBSMODE_ADMINONLY'] == 1
            and crypt($this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
            $this->prterror(T('INVALID_PASSWORD'));
        }
        $filename = '';
        if ($this->f['ff']) {
            $filename = trim($this->f['ff']);
        }
        $result = $this->searchmessage('POSTID', $this->f['s'], FALSE, $filename);
        if (!$result) {
            $this->prterror(T('MESSAGE_NOT_FOUND'));
        }
        # Get message
        $message = $this->getmessage($result[0]);

        if (!$retry) {
            $formmsg = $message['MSG'];
            $formmsg = preg_replace ("/&gt; &gt;[^\r]+\r/", "", $formmsg);
            $formmsg = preg_replace ("/<a href=\"m=f\S+\"[^>]*>[^<]+<\/a>/i", "", $formmsg);
            $formmsg = preg_replace ("/<a href=\"[^>]+>([^<]+)<\/a>/i", "$1", $formmsg);
            $formmsg = preg_replace ("/\r*<a href=[^>]+><img [^>]+><\/a>/i", "", $formmsg);
            $formmsg = preg_replace ("/\r/", "\r> ", $formmsg);
            $formmsg = "> $formmsg\r";
            $formmsg = preg_replace ("/\r>\s+\r/", "\r", $formmsg);
            $formmsg = preg_replace ("/\r>\s+\r$/", "\r", $formmsg);
        } else {
            $formmsg = $this->f['v'];
            $formmsg = preg_replace ("/<a href=\"m=f\S+\"[^>]*>[^<]+<\/a>/i", "", $formmsg);
        }
        $formmsg .= "\r";

        $this->setform ( "＞" . preg_replace("/<[^>]*>/", '', $message['USER']) . $this->c['FSUBJ'], $formmsg, '');

        if (!$message['THREAD']) {
            $message['THREAD'] = $message['POSTID'];
        }
        $filename ? $mode = 1 : $mode = 0;
        $this->setmessage ($message, $mode, $filename);

        if ($this->c['AUTOLINK']) $this->t->addVar('follow', 'CHK_A', ' checked="checked"');
        $this->t->addVar('follow', 'FOLLOWID', $message['POSTID']);
        $this->t->addVar('follow', 'SEARCHID', $this->f['s']);
        $this->t->addVar('follow', 'FF', $this->f['ff']);
        # Display
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('FOLLOW_UP_POST'));
        $this->t->displayParsedTemplate('follow');
        print $this->prthtmlfoot ();

    }

    /**
     * Display new post page
     *
     * @access  public
     */
    function prtnewpost($retry = FALSE) {

        # Administrator authentication
        if ($this->c['BBSMODE_ADMINONLY'] != 0
            and crypt($this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
            $this->prterror(T('INVALID_PASSWORD'));
        }
        # Form section
        $dtitle = "";
        $dmsg = "";
        $dlink = "";
        if ($retry) {
            $dtitle = $this->f['t'];
            $dmsg = $this->f['v'];
            $dlink = $this->f['l'];
        }
        $this->setform ($dtitle, $dmsg, $dlink);

        if ($this->c['AUTOLINK']) $this->t->addVar('newpost', 'CHK_A', ' checked="checked"');

        $this->sethttpheader();
        print $this->prthtmlhead ( $this->c['BBSTITLE'] . ' ' . T('NEW_POST') );
        $this->t->displayParsedTemplate('newpost');
        print $this->prthtmlfoot ();

    }

    /**
     * Post search
     *
     * @param   Integer $mode       0: Bulletin board / 1: Message log search (with buttons displayed) / 2: Message log search (without buttons displayed) / 3: For message log file output
     */
    function prtsearchlist($mode = "") {

        if (!$this->f['s']) {
            $this->prterror(T('NO_PARAMETERS'));
        }
        if (!$mode) {
            $mode = $this->f['m'];
        }
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('POST_SEARCH'));
        $this->t->displayParsedTemplate('searchlist_upper');

        $result = $this->msgsearchlist($mode);
        foreach ($result as $message) {
            print $this->prtmessage ($message, $mode, $this->f['ff']);
        }
        $success = count($result);

        $this->t->addVar('searchlist_lower', 'SUCCESS', $success);
        $this->t->displayParsedTemplate('searchlist_lower');
        print $this->prthtmlfoot ();

    }

    /**
     * Post search process
     */
    function msgsearchlist($mode) {

        $fh = NULL;
        if ($this->f['ff']) {
            if (preg_match("/^[\w.]+$/", $this->f['ff'])) {
                $fh = @fopen($this->c['OLDLOGFILEDIR'] . $this->f['ff'], "rb");
            }
            if (!$fh) {
                $this->prterror( sprintf(T('FAILED_TO_OPEN_LOG'), $this->f['ff']) );
            }
            flock ($fh, 1);
        }

        $result = array();

        if ($fh) {
            $linecount = 0;
            $threadstart = FALSE;
            while (($logline = Func::fgetline($fh)) !== FALSE) {
                if ($threadstart) {
                    $linecount++;
                }
                if ($linecount > $this->c['LOGSAVE']) {
                    break;
                }
                $message = $this->getmessage($logline);
                # Search by user
                if ($mode == 's' and preg_replace("/<[^>]*>/", '', $message['USER']) == $this->f['s']) {
                    $result[] = $message;
                }
                # Search by thread
                elseif ($mode == 't'
                    and ($message['THREAD'] == $this->f['s'] or $message['POSTID'] == $this->f['s'])) {
                    $result[] = $message;
                    if (!$threadstart) {
                        $threadstart = TRUE;
                    }
                }
            }
            flock ($fh, 3);
            fclose ($fh);
        }
        else {
            $logdata = $this->loadmessage();
            foreach ($logdata as $logline) {
                $message = $this->getmessage($logline);
                # Search by user
                if ($mode == 's' and preg_replace("/<[^>]*>/", '', $message['USER']) == $this->f['s']) {
                    $result[] = $message;
                }
                # Search by thread
                elseif ($mode == 't'
                    and ($message['THREAD'] == $this->f['s'] or $message['POSTID'] == $this->f['s'])) {
                    $result[] = $message;
                    if ($message['POSTID'] == $this->f['s']) {
                        break;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Post complete
     */
    function prtputcomplete() {

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('POST_COMPLETE'));
        $this->t->displayParsedTemplate('postcomplete');
        print $this->prthtmlfoot ();

    }

    /**
     * Display user settings page
     */
    function prtcustom($mode = '') {

        if ($this->c['GZIPU']) $this->t->addVar('custom', 'CHK_G', ' checked="checked"');
        if ($this->c['AUTOLINK']) $this->t->addVar('custom', 'CHK_A', ' checked="checked"');
        if ($this->c['LINKOFF']) $this->t->addVar('custom', 'CHK_LOFF', ' checked="checked"');
        if ($this->c['HIDEFORM']) $this->t->addVar('custom', 'CHK_HIDE', ' checked="checked"');
        if ($this->c['SHOWIMG']) $this->t->addVar('custom', 'CHK_SI', ' checked="checked"');
        if ($this->c['COOKIE']) $this->t->addVar('custom', 'CHK_COOKIE', ' checked="checked"');

        $this->c['FOLLOWWIN'] ? $this->t->addVar('custom', 'CHK_FW_1', ' checked="checked"')
            : $this->t->addVar('custom', 'CHK_FW_0', ' checked="checked"');
        $this->c['RELTYPE'] ? $this->t->addVar('custom', 'CHK_RT_1', ' checked="checked"')
            : $this->t->addVar('custom', 'CHK_RT_0', ' checked="checked"');

        $this->t->addVar('custom_hide', 'BBSMODE_ADMINONLY', $this->c['BBSMODE_ADMINONLY']);
        $this->t->addVar('custom_a', 'BBSMODE_ADMINONLY', $this->c['BBSMODE_ADMINONLY']);
        $this->t->addVar('custom', 'MODE', $mode);

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('USER_SETTINGS'));
        $this->t->displayParsedTemplate('custom');
        print $this->prthtmlfoot ();
    }

    /**
     * User settings process
     */
    function setcustom() {

        $redirecturl = $this->c['CGIURL'];

        # Cookie消去
        if ($this->f['cr']) {
            $this->f['c'] = '';
            setcookie('c');
            setcookie('undo');
            $this->s['UNDO_P'] = '';
            $this->s['UNDO_K'] = '';
        }
        else {
            $colors = array(
                'C_BACKGROUND',
                'C_TEXT',
                'C_A_COLOR',
                'C_A_VISITED',
                'C_SUBJ',
                'C_QMSG',
                'C_A_ACTIVE',
                'C_A_HOVER',
            );

            $flgchgindex = -1;
            $cindex = 0;
            foreach ($colors as $confname) {
                if (strlen($this->f[$confname]) == 6 and preg_match("/^[0-9a-fA-F]{6}$/", $this->f[$confname])
                    and $this->f[$confname] != $this->c[$confname]) {
                    $this->c[$confname] = $this->f[$confname];
                    $flgchgindex = $cindex;
                }
                $cindex++;
            }

            $cbase64str = '';
            for ($i = 0; $i <= $flgchgindex; $i++) {
                $cbase64str .= Func::threebytehex_base64($this->c[$colors[$i]]);
            }
            $this->refcustom();

            $this->f['c'] = substr($this->f['c'], 0, 2) . $cbase64str;

            $redirecturl .= "?c=".$this->f['c'];
            foreach (array('w', 'd',) as $key) {
                if ($this->f[$key] != '') {
                    $redirecturl .= "&{$key}=".$this->f[$key];
                }
            }
            if ($this->f['nm']) {
                $redirecturl .= "&m=".$this->f['nm'];
            }
            if ($this->c['COOKIE']) {
                $this->setbbscookie();
            }
        }
        # Redirect
        if (preg_match("/^(https?):\/\//", $this->c['CGIURL'])) {
            header ("Location: {$redirecturl}");
        }
        else {
            $this->prtredirect(htmlentities($redirecturl));
        }
    }

    /**
     * UNDO process
     */
    function prtundo() {
        if (!$this->f['s']) {
            $this->prterror(T('NO_PARAMETERS'));
        }
        if (isset($this->s['UNDO_P']) and $this->s['UNDO_P'] == $this->f['s']) {
            $loglines = $this->searchmessage('POSTID', $this->s['UNDO_P']);
            if (count($loglines) < 1) {
                $this->prterror ( T('UNDO_POST_NOT_FOUND') );
            }
            $message = $this->getmessage($loglines[0]);
            $undokey = substr (preg_replace("/\W/", "", crypt($message['PROTECT'], $this->c['ADMINPOST'])), -8);
            if ($undokey != $this->s['UNDO_K']) {
                $this->prterror ( T('UNDO_NOT_PERMITTED') );
            }
            # Erase operation
            require_once(PHP_BBSADMIN);
            $bbsadmin = new Bbsadmin();
            $bbsadmin->killmessage($this->s['UNDO_P']);

            $this->s['UNDO_P'] = '';
            $this->s['UNDO_K'] = '';
            setcookie('undo');
        }
        else {
            $this->prterror ( T('UNDO_NOT_PERMITTED') );
        }
        $this->sethttpheader();
        print $this->prthtmlhead($this->c['BBSTITLE'] . ' ' . T('DELETION_COMPLETE'));
        $this->t->displayParsedTemplate('undocomplete');
        print $this->prthtmlfoot ();
    }

    /**
     * Message search (exact match)
     *
     * @access  public
     * @param   String  $varname      Variable name
     * @param   String  $searchvalue  Search string
     * @param   Boolean $ismultiple   Multiple search flag
     * @return  Array   Log line array
     */
    function searchmessage($varname, $searchvalue, $ismultiple = FALSE, $filename = "") {
        $result = array();
        $logdata = $this->loadmessage($filename);
        foreach ($logdata as $logline) {
            $message = $this->getmessage($logline);
            if (isset($message[$varname]) and $message[$varname] == $searchvalue) {
                $result[] = $logline;
                if (!$ismultiple) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Post check
     *
     * @access  public
     * @param   Boolean   $limithost  Whether or not to check for same host
     * @return  Integer   Error code
     */
    function chkmessage($limithost = TRUE) {
        $posterr = 0;
        if ($this->c['RUNMODE'] == 1) {
            $this->prterror(T('POSTING_SUSPENDED'));
        }
        /* Prohibit access by host name process */
        if (Func::hostname_match($this->c['HOSTNAME_POSTDENIED'], $this->c['HOSTAGENT_BANNED'])) {
            $this->prterror(T('POSTING_SUSPENDED'));
        }
        
        /* Catch spambots */
        if(detectHoneyPot()) {
            $this->prterror(T('SPAM_KUN'));
        }

        if ($this->c['BBSMODE_ADMINONLY'] == 1 or ($this->c['BBSMODE_ADMINONLY'] == 2 and !$this->f['f'])) {
            if (crypt($this->f['u'], $this->c['ADMINPOST']) != $this->c['ADMINPOST']) {
                $this->prterror(T('ADMIN_ONLY_POSTING'));
            }
        }
        if ($_SERVER['HTTP_REFERER'] and $this->c['REFCHECKURL']
            and (strpos($_SERVER['HTTP_REFERER'], $this->c['REFCHECKURL']) === FALSE
            or strpos($_SERVER['HTTP_REFERER'], $this->c['REFCHECKURL']) > 0)) {
            $this->prterror(T('BAD_REFERER') . "<br>{$this->c['REFCHECKURL']}.");
        }
        foreach (explode ("\r", $this->f['v']) as $line) {
            if (strlen ($line) > $this->c['MAXMSGCOL']) {
                $this->prterror(T('POST_TOO_WIDE'));
            }
        }
        if (substr_count ($this->f['v'], "\r") > $this->c['MAXMSGLINE'] - 1) {
            $this->prterror(T('POST_TOO_MANY_LINES'));
        }
        if (strlen ($this->f['v']) > $this->c['MAXMSGSIZE']) {
            $this->prterror(T('POST_TOO_LARGE'));
        }
        if (strlen ($this->f['u']) > $this->c['MAXNAMELENGTH']) {
            $this->prterror(T('NAME_TOO_LONG'));
        }
        if (strlen ($this->f['i']) > $this->c['MAXMAILLENGTH']) {
            $this->prterror(T('EMAIL_TOO_LONG'));
        }
//        if ($this->f['i']) { ## mod
//            $this->prterror(T('SPAM_KUN')); ## mod
//        } ## mod
        if (strlen ($this->f['t']) > $this->c['MAXTITLELENGTH']) {
            $this->prterror(T('TITLE_TOO_LONG'));
        }
        {
            $timestamp = Func::pcode_verify ($this->f['pc'], $limithost);

            if ((CURRENT_TIME - $timestamp ) < $this->c['MINPOSTSEC'] ) {
                $this->prterror(T('POST_TOO_FAST'));
            }
/*            if ((CURRENT_TIME - $timestamp ) > $this->c['MAXPOSTSEC'] ) {
                $this->prterror ( 'The time between posts is too long. Please try again.');
                $posterr = 2;
                return $posterr;
            } */
        }

        if (trim($this->f['v']) == '') {
            $posterr = 2;
            return $posterr;
        }

        ## if ($this->c['NGWORD']) {
        ##     foreach ($this->c['NGWORD'] as $ngword) {
        ##         if (strpos($this->f['v'], $ngword) !== FALSE
        ##             or strpos($this->f['l'], $ngword) !== FALSE
        ##             or strpos($this->f['t'], $ngword) !== FALSE
        ##             or strpos($this->f['u'], $ngword) !== FALSE
        ##             or strpos($this->f['i'], $ngword) !== FALSE) {
        ##            $this->prterror( T('NGWORD_FOUND') );
        ##         }
        ##     }
        ## }
        if ($this->c['NGWORD']) { ## mod
            foreach ($this->c['NGWORD'] as $ngword) {
                $ngword = strtolower($ngword); // Convert prohibited word to lowercase
                if (
                    strpos(strtolower($this->f['v']), $ngword) !== FALSE ||
                    strpos(strtolower($this->f['l']), $ngword) !== FALSE ||
                    strpos(strtolower($this->f['t']), $ngword) !== FALSE ||
                    strpos(strtolower($this->f['u']), $ngword) !== FALSE ||
                    strpos(strtolower($this->f['i']), $ngword) !== FALSE
                ) {
                    $this->prterror( T('NGWORD_FOUND') );
                }
            }
        } ## mod end

        #20240204 猫 spam detection (https://php.o0o0.jp/article/php-spam)
        # Number of characters: char_num = mb_strlen( $this->f['v'], 'UTF8');
        # Number of bytes: byte_num = strlen( $this->f['v']);

        ## $char_num = mb_strlen( $this->f['v'], 'UTF8');
        ## $byte_num = strlen( $this->f['v']);

        # When single-byte characters makes up more than 90% of the total
        ## if ((($char_num * 3 - $byte_num) / 2 / $char_num * 100) > 90) {
        ##     # Treat as spam
        ##     $this->prterror('This bulletin board\'s post function is currently disabled.');
        ## }
        ## disabled by TL: not suitable for languages that use single-byte characters (i.e. English)


        return $posterr;
    }
    
################
#post user function by chatGPT 20260310 gikoneko
################

########## 1 メイン処理

function handleUser(&$message)
{
    $user = $message['USER'] ?? '';

    if ($user === '') {
        $message['USER'] = $this->c['ANONY_NAME'];
        return;
    }

    [$name, $trip, $copy] = $this->parseUser($user);

    $admin = $this->checkAdmin($name, $trip, $copy, $message);
    if ($admin === 3) {
        return 3;
    }
    if ($admin === true) {
        return;
    }

    $name = $this->checkAdminFraud($name);
    $name = $this->protectAdminName($name,$trip,$copy);
    $name = $this->convertHandle($name);

    $message['USER'] = $this->buildName($name, $trip, $copy);
}

###########  2 ユーザー解析

function parseUser($user)
{
    $trip = '';
    $copy = '';

    # ◆コピー分解
    if (strpos($user, '◆') !== false) {

        $parts = explode('◆', $user);

        $name = $parts[0];
        $copy = implode('◆', array_slice($parts,1));

        return [$name, '', $copy];
    }

    # 通常トリップ
    if (($pos = strpos($user,'#')) !== false) {

        $name = substr($user,0,$pos);
        $tripkey = substr($user,$pos);

        $trip =
            substr(
                preg_replace("/\W/",'',crypt($tripkey,'00')),
                -7
            ) .
            $this->tripuse($tripkey);

        return [$name,$trip,''];
    }

    return [$user,'',''];
}

############ 3 admin判定

function checkAdmin($name,$trip,$copy,&$message)
{
    $adminPost = $this->c['ADMINPOST'] ?? '';

    if (!$adminPost) {
        return false;
    }

    # ◆コピーがある場合は管理不可
    if ($copy !== '') {
        return false;
    }

    if (crypt($name,$adminPost) === $adminPost) {

        $adminName = $this->c['ADMINNAME'];

        $message['USER'] =
            "<span class=\"muh\">{$adminName}</span>";

        if ($trip !== '') {
            $message['USER'] .=
                ' <span class="mut">◆'.$trip.'</span>';
        }

        $message['MAIL'] = $this->c['ADMINMAIL'];

        if (!empty($this->c['ADMINKEY']) &&
            trim($message['MSG']) === $this->c['ADMINKEY']) {
            return 3;
        }

        return true;
    }

    return false;
}

########## 4 admin騙り検出

function checkAdminFraud($name)
{
    $adminName = $this->c['ADMINNAME'] ?? '';

    if ($adminName && strpos($name,$adminName) !== false) {

        return $adminName .
            '<span class="muh">' .
            T('FRAUDSTER_TAG') .
            '</span>';
    }

    return $name;
}

########### 5 固定ハンドル処理

function convertHandle($name)
{
    $handles = $this->c['HANDLENAMES'] ?? [];

    if (!$handles) {
        return $name;
    }

    if (isset($handles[$name])) {

        return $name .
            '<span class="muh">' .
            T('FRAUDSTER_TAG') .
            '</span>';
    }

    $search = array_search(trim($name),$handles,true);

    if ($search !== false) {
        return "<span class=\"muh\">{$search}</span>";
    }

    return $name;
}

######### 6 表示組み立て

function buildName($name,$trip,$copy)
{
    $adminPost = $this->c['ADMINPOST'] ?? '';
    $adminName = $this->c['ADMINNAME'] ?? '';

    # 管理パス + ◆コピー → 管理人騙り
    if ($copy !== '' && $adminPost && crypt($name,$adminPost) === $adminPost) {

        $copy = str_replace('◆','◇',$copy);

        return "<span class=\"muh\">{$adminName}</span>"
            .' <span class="mut">◇'.$copy.'</span>'
            .T('FRAUDSTER_TAG');
    }

    if ($copy !== '') {

        $copy = str_replace('◆','◇',$copy);

        return $name
            .' <span class="mut">◇'.$copy.'</span>'
            .T('FRAUDSTER_TAG');
    }

    if ($trip !== '') {

        return $name
            .' <span class="mut">◆'.$trip.'</span>';
    }

    return $name;
}

###### 7 protectAdminName

function protectAdminName($name,$trip,$copy)
{
    $adminName = $this->c['ADMINNAME'] ?? '';

    if (!$adminName) {
        return $name;
    }

    if (strpos($name,$adminName) !== false) {

        return $adminName
            .'<span class="muh">'
            .T('FRAUDSTER_TAG')
            .'</span>';
    }

    return $name;
}

    /**
     * Get message from form input
     *
     * @access  public
     * @return  Array  Message array
     */
    function getformmessage() {

        $message = array();
        $message['PCODE'] = $this->f['pc'];
        $message['USER'] = $this->f['u'];
        $message['MAIL'] = $this->f['i'];
        $message['TITLE'] = $this->f['t'];
        $message['MSG'] = $this->f['v'];
        $message['URL'] = $this->f['l'];
        $message['PHOST'] = $this->s['HOST'];
        $message['AGENT'] = $this->s['AGENT'];
        # Reference ID
        if ($this->f['f']) {
            $message['REFID'] = $this->f['f'];
        }
        else {
            $message['REFID'] = '';
        }
        # Protect code
        $message['PCODE'] = substr($message['PCODE'], 8, 4);
        # Title
        if (!$message['TITLE']) {
            $message['TITLE'] = ' ';
        }
        # User

####################
#Execute Post User
####################
$result = $this->handleUser($message);
if ($result === 3) {
	return 3;
}
####################

        $message['MSG'] = rtrim ($message['MSG']);

        # Auto-link URLs
        if ( $this->c['AUTOLINK'] ) {
            $message['MSG'] = preg_replace("/((https?|ftp|news):\/\/[-_.,!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/",
                "<a href=\"$1\" target=\"link\">$1</a>", $message['MSG']);
        }
        # URL field
        $message['URL'] = trim($message['URL']);
        if ($message['URL']) {
            $message['MSG'] .= "\r\r<a href=\"".Func::escape_url($message['URL'])."\" target=\"link\">{$message['URL']}</a>";
        }
        # Reference
        if ($message['REFID']) {
            $refdata = $this->searchmessage('POSTID', $message['REFID'], FALSE, $this->f['ff']);
            if (!$refdata) {
                $this->prterror ( T('REFERENCE_NOT_FOUND') );
            }
            $refmessage = $this->getmessage($refdata[0]);
            $refmessage['WDATE'] = Func::getdatestr($refmessage['NDATE'], $this->c['DATEFORMAT']);
            $message['MSG'] .= "\r\r<a href=\"m=f&s={$message['REFID']}&r=&\">" . T('REFERENCE_COLON') . " {$refmessage['WDATE']}</a>";
            # Simple self-reply prevention function
            if ($this->c['IPREC'] and $this->c['SHOW_SELFFOLLOW']
                and $refmessage['PHOST'] != '' and $refmessage['PHOST'] == $message['PHOST']) {
                $message['USER'] .= '<span class="muh">' . T('SELF_REPLY_TAG') . '</span>';
            }
        }
        # Check
        if (strlen ($message['MSG']) > $this->c['MAXMSGSIZE']) {
            $this->prterror ( T('POST_TOO_LARGE') );
        }
        return $message;
    }

    /**
     * Message registration process
     *
     * @access  public
     * @return  Integer  Error code
     */
    function putmessage($message) {
        if (!is_array($message)) {
            return $message;
        }
        $fh = @fopen($this->c['LOGFILENAME'], "rb+");
        if (!$fh) {
            $this->prterror ( T('FAILED_TO_READ_MESSAGE') );
        }
        flock ($fh, 2);
        fseek ($fh, 0, 0);

        $logdata = array();
        while (($logline = Func::fgetline($fh)) !== FALSE) {
                $logdata[] = $logline;
        }
        $posterr = 0;
        if ($this->f['ff']) {
            $refdata = $this->searchmessage('THREAD', $message['REFID'], FALSE, $this->f['ff']);
            if (isset($refdata[0])) {
                $refmessage = $this->getmessage($refdata[0]);
                if ($refmessage) {
                    $message['THREAD'] = $refmessage['thread'];
                }
                else {
                    $message['THREAD'] = '';
                }
            }
            else {
                $message['THREAD'] = '';
            }
        }
        else {
            for ($i = 0; $i < count($logdata); $i++) {
                $items = @explode(',', $logdata[$i]);
                if (count($items) > 8) {
                    $items[9] = rtrim($items[9]);
                    if ($i < $this->c['CHECKCOUNT'] and $message['MSG'] == $items[9]) {
                        $posterr = 1;
                        break;
                    }
                    if ($this->c['IPREC'] and CURRENT_TIME < ($items[0] + $this->c['SPTIME'])
                        and $this->s['HOST'] == $items[4]) {
                        $posterr = 2;
                        break;
                    }
                    if ($message['PCODE'] == $items[2]) {
                        $posterr = 2;
                        break;
                    }
                    if ($message['REFID'] and $items[1] == $message['REFID']) {
                        $message['THREAD'] = $items[3];
                        if (!$message['THREAD']) {
                            $message['THREAD'] = $items[1];
                        }
                    }
                }
            }
        }
        if ($posterr) {
            flock ($fh, 3);
            fclose ($fh);
            return $posterr;
        }
        else {
            $items = @explode (',', $logdata[0], 3);
            $message['POSTID'] = $items[1] + 1;
            if (!$message['REFID']) {
                $message['THREAD'] = $message['POSTID'];
            }
            $msgdata = implode (',', array(
                CURRENT_TIME,
                $message['POSTID'],
                $message['PCODE'],
                $message['THREAD'],
                $message['PHOST'],
                $message['AGENT'],
                $message['USER'],
                $message['MAIL'],
                $message['TITLE'],
                $message['MSG'],
                $message['REFID'],
            ));
            $msgdata = strtr ($msgdata, "\n", "") . "\n";
            if (count($logdata) >= $this->c['LOGSAVE']) {
                $logdata = array_slice($logdata, 0, $this->c['LOGSAVE'] - 2);
            }
            {
                $logdata = $msgdata . implode ('', $logdata);
                fseek ($fh, 0, 0);
                ftruncate ($fh, 0);
                fwrite ($fh, $logdata);
            }
            flock ($fh, 3);
            fclose ($fh);
            # Cookie registration
            if ($this->c['COOKIE']) {
                $this->setbbscookie();
                if ($this->c['ALLOW_UNDO']) {
                    $this->setundocookie($message['POSTID'], $message['PCODE']);
                }
            }

            # Message log output
            if ($this->c['OLDLOGFILEDIR']) {
                $dir = $this->c['OLDLOGFILEDIR'];

                if ($this->c['OLDLOGFMT']) {
                    $oldlogext = 'dat';
                }
                else {
                    $oldlogext = 'html';
                }
                if ($this->c['OLDLOGSAVESW']) {
                    $oldlogfilename = $dir . date("Ym", CURRENT_TIME) . ".$oldlogext";
                    $oldlogtitle = $this->c['BBSTITLE'] . date(" Y.m", CURRENT_TIME);
                }
                else {
                    $oldlogfilename = $dir . date("Ymd", CURRENT_TIME) . ".$oldlogext";
                    $oldlogtitle = $this->c['BBSTITLE'] . date(" Y.m.d", CURRENT_TIME);
                }
                if (@filesize($oldlogfilename) > $this->c['MAXOLDLOGSIZE']) {
                    $this->prterror ( T('OLDLOG_TOO_LARGE') );
                }
                $fh = @fopen($oldlogfilename, "ab");
                if (!$fh) {
                    $this->prterror( T('FAILED_TO_OUTPUT_LOG') );
                }
                flock ($fh, 2);
                $isnewdate = FALSE;
                if (!@filesize($oldlogfilename)) {
                    $isnewdate = TRUE;
                }
                if ($this->c['OLDLOGFMT']) {
                    fwrite ($fh, $msgdata);
                }
                else {
                    # HTML header for HTML output
                    if ($isnewdate) {
                        $oldloghtmlhead = $this->prthtmlhead($oldlogtitle);
                        $oldloghtmlhead .= "<span class=\"pagetitle\">$oldlogtitle</span>\n\n<hr />\n";
                        fwrite ($fh, $oldloghtmlhead);
                    }
                    $msghtml = $this->prtmessage($this->getmessage($msgdata), 3);
                    fwrite ($fh, $msghtml);
                }
                flock ($fh, 3);
                fclose ($fh);
                if (@filesize($oldlogfilename) > $this->c['MAXOLDLOGSIZE']) {
                    @chmod ($oldlogfilename, 0400);
                }
                # Delete old log files
                if (!$this->c['OLDLOGSAVESW'] and $isnewdate) {
                    $limitdate = CURRENT_TIME - $this->c['OLDLOGSAVEDAY'] * 60 * 60 * 24;
                    $limitdate = date("Ymd", $limitdate);
                    $dh = opendir($dir);
                    while ($entry = readdir($dh)) {
                        $matches = array();
                        if (is_file($dir . $entry)
                            and preg_match("/(\d+)\.$oldlogext$/", $entry, $matches)) {
                            $timestamp = $matches[1];
                            if (strlen($timestamp) == strlen($limitdate) and $timestamp < $limitdate) {
                                unlink ($dir . $entry);
                            }
                        }
                    }
                    closedir ($dh);
                }

                # Archive creation
                if ($this->c['ZIPDIR'] and @function_exists('gzcompress')) {
                    # In the case of dat, it also writes the message log in HTML format as a temporary file to be saved in the ZIP
                    if ($this->c['OLDLOGFMT']) {
                        if ($this->c['OLDLOGSAVESW']) {
                            $tmplogfilename = $this->c['ZIPDIR'] . date("Ym", CURRENT_TIME) . ".html";
                        }
                        else {
                            $tmplogfilename = $this->c['ZIPDIR'] . date("Ymd", CURRENT_TIME) . ".html";
                        }

                        $fhtmp = @fopen($tmplogfilename, "ab");
                        if (!$fhtmp) {
                            return;
                        }
                        flock ($fhtmp, 2);

                        if (!@filesize($tmplogfilename)) {
                            $oldloghtmlhead = $this->prthtmlhead($oldlogtitle);
                            $oldloghtmlhead .= "<span class=\"pagetitle\">$oldlogtitle</span>\n\n<hr />\n";
                            fwrite ($fhtmp, $oldloghtmlhead);
                        }
                        $msghtml = $this->prtmessage($this->getmessage($msgdata), 3);
                        fwrite ($fhtmp, $msghtml);
                        flock ($fhtmp, 3);
                        fclose ($fhtmp);
                    }
                    $tmpdir = $dir;
                    if ($this->c['OLDLOGFMT']) {
                        $tmpdir = $this->c['ZIPDIR'];
                    }
                    if ($this->c['OLDLOGSAVESW']) {
                        $currentfile = date("Ym", CURRENT_TIME) . ".html";
                    }
                    else {
                        $currentfile = date("Ymd", CURRENT_TIME) . ".html";
                    }

                    $files = array();
                    $dh = opendir($tmpdir);
                    if (!$dh) {
                        return;
                    }
                    while ($entry = readdir($dh)) {
                        if ($entry != $currentfile and is_file($tmpdir . $entry) and preg_match("/^\d+\.html$/", $entry)) {
                            $files[] = $entry;
                        }
                    }
                    closedir ($dh);

                    # File with the latest update time, other than the current log
                    $maxftime = 0;
                    foreach ($files as $filename) {
                        $fstat = stat ($tmpdir . $filename);
                        if ($fstat[9] > $maxftime) {
                            $maxftime = $fstat[9];
                            $checkedfile = $tmpdir . $filename;
                        }
                    }
                    if (!$checkedfile) {
                        return;
                    }
                    $zipfilename = preg_replace("/\.\w+$/", ".zip", $checkedfile);

                    # Create a ZIP file
                    require_once(LIB_PHPZIP);
                    $zip = new PHPZip();
                    $zipfiles[] = $checkedfile;
                    $zip->Zip($zipfiles, $zipfilename);

                    # Delete temporary files
                    if ($this->c['OLDLOGFMT']) {
                        unlink ($checkedfile);
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Get environment variables
     */
    function setuserenv() {

        if ($this->c['UAREC']) {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            $agent = Func::html_escape($agent);
            $this->s['AGENT'] = $agent;
        }
        if (!$this->c['IPREC']) {
            return;
        }
        list ($addr, $host, $proxyflg, $realaddr, $realhost) = Func::getuserenv();

        $this->s['ADDR'] = $addr;
        $this->s['HOST'] = $host;
        $this->s['PROXYFLG'] = $proxyflg;
        $this->s['REALADDR'] = $realaddr;
        $this->s['REALHOST'] = $realhost;
    }

    /**
     * Bulletin board cookie registration
     */
    function setbbscookie() {
        $cookiestr = "u=" . urlencode($this->f['u']);
        $cookiestr .= "&i=" . urlencode($this->f['i']);
        $cookiestr .= "&c=" . $this->f['c'];
        setcookie('c', $cookiestr, CURRENT_TIME + 7776000); // expires in 90 days
    }

    /**
     * Register cookie for post UNDO
     */
    function setundocookie($undoid, $pcode) {
        $undokey = substr (preg_replace("/\W/", "", crypt($pcode, $this->c['ADMINPOST'])), -8);
        $cookiestr = "p=$undoid&k=$undokey";
        $this->s['UNDO_P'] = $undoid;
        $this->s['UNDO_K'] = $undokey;
        setcookie('undo', $cookiestr, CURRENT_TIME + 86400); // expires in 24 hours
    }

    /**
     * Bulletproof counter process
     *
     * @access  public
     * @param   Integer Bulletproof level
     * @return  String  Counter value
     */
    function counter($countlevel = 0) {
        if (!$countlevel) {
            if (isset($this->c['COUNTLEVEL'])) {
                $countlevel = $this->c['COUNTLEVEL'];
            }
            if ($countlevel < 1) {
                $countlevel = 1;
            }
        }
        $count = array();
        for ($i = 0; $i < $countlevel; $i++) {
            $filename = "{$this->c['COUNTFILE']}{$i}.dat";
            if (is_writable ($filename) and $fh = @fopen ($filename, "r")) {
                $count[$i] = fgets ($fh, 10);
                fclose ($fh);
            }
            else {
                $count[$i] = 0;
            }
            $filenumber[$count[$i]] = $i;
        }
        sort ($count, SORT_NUMERIC);
        $mincount = $count[0];
        $maxcount = $count[$countlevel-1] + 1;
        if ($fh = @fopen("{$this->c['COUNTFILE']}{$filenumber[$mincount]}.dat", "w")) {
            fputs ($fh, $maxcount);
            fclose ($fh);
            return $maxcount;
        } else {
            return T('COUNTER_ERROR');
        }
    }

    /**
     * Participant count (currently viewing)
     *
     * @access  public
     * @param   $cntfilename  Record file name
     * @return  String  Number of participants
     */
    function mbrcount($cntfilename = "") {
        if (!$cntfilename) {
            $cntfilename = $this->c['CNTFILENAME'];
        }
        if ($cntfilename) {
            $mbrcount = 0;
            $remoteaddr = '0.0.0.0';
            if ($_SERVER['REMOTE_ADDR']) {
                $remoteaddr = $_SERVER['REMOTE_ADDR'];
            }
            $ukey = hexdec(substr(md5($remoteaddr), 0, 8));
            $newcntdata = array();
            if (is_writable ($cntfilename)) {
                $cntdata = file ($cntfilename);
                $cadd = 0;
                foreach ($cntdata as $cntvalue) {
                    if (strrpos($cntvalue, ',') !== FALSE) {
                        list ($cuser, $ctime,) = @explode (',', trim ($cntvalue));
                        if ($cuser == $ukey) {
                            $newcntdata[] = "$ukey,".CURRENT_TIME."\n";
                            $cadd = 1;
                            $mbrcount++;
                        }
                        elseif (($ctime + $this->c['CNTLIMIT']) >= CURRENT_TIME) {
                            $newcntdata[] = "$cuser,$ctime\n";
                            $mbrcount++;
                        }
                    }
                }
                if (!$cadd) {
                    $newcntdata[] = "$ukey,".CURRENT_TIME."\n";
                    $mbrcount++;
                }
            }
            else {
                $newcntdata[] = "$ukey,".CURRENT_TIME."\n";
                $mbrcount++;
            }
            if ($fh = @fopen ($cntfilename, "w")) {
                $cntdatastr = implode('', $newcntdata);
                flock ($fh, 2);
                fwrite ($fh, $cntdatastr);
                flock ($fh, 3);
                fclose ($fh);
            }
            else {
                return T('PARTICIPANT_FILE_ERROR');
            }
            return $mbrcount;
        }
        else {
            return;
        }
    }
}
/* end of class Bbs */

/**
 * Shared function class
 *
 * A class that stores general-purpose functions that do not depend on configuration information.
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Func {

    /**
     * Constructor
     *
     */
    public function __construct() {
    }


    public static function getuserenv() {

        $addr = $_SERVER['REMOTE_ADDR'];
        $host = $_SERVER['REMOTE_HOST'];
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if ($addr == $host or !$host) {
            $host = gethostbyaddr ($addr);
        }

        $proxyflg = 0;

        if ($_SERVER['HTTP_CACHE_CONTROL']) { $proxyflg = 1; }
        if ($_SERVER['HTTP_CACHE_INFO']) { $proxyflg += 2; }
        if ($_SERVER['HTTP_CLIENT_IP']) { $proxyflg += 4; }
        if ($_SERVER['HTTP_FORWARDED']) { $proxyflg += 8; }
        if ($_SERVER['HTTP_FROM']) { $proxyflg += 16; }
        if ($_SERVER['HTTP_PROXY_AUTHORIZATION']) { $proxyflg += 32; }
        if ($_SERVER['HTTP_PROXY_CONNECTION']) { $proxyflg += 64; }
        if ($_SERVER['HTTP_SP_HOST']) { $proxyflg += 128; }
        if ($_SERVER['HTTP_VIA']) { $proxyflg += 256; }
        if ($_SERVER['HTTP_X_FORWARDED_FOR']) { $proxyflg += 512; }
        if ($_SERVER['HTTP_X_LOCKING']) { $proxyflg += 1024; }
        if (preg_match ("/cache|delegate|gateway|httpd|proxy|squid|www|via/i", $agent)) {
            $proxyflg += 2048;
        }
        if (preg_match ("/cache|^dns|dummy|^ns|firewall|gate|keep|mail|^news|pop|proxy|smtp|w3|^web|www/i", $host)) {
            $proxyflg += 4096;
        }
        if ($host == $addr) {
            $proxyflg += 8192;
        }
        $realaddr = '';
        $realhost = '';
        if ( $proxyflg > 0 ) {
            $matches = array();
            if (preg_match ("/^(\d+)\.(\d+)\.(\d+)\.(\d+)/", $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", $_SERVER['HTTP_FORWARDED'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", $_SERVER['HTTP_VIA'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", $_SERVER['HTTP_CLIENT_IP'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/(\d+)\.(\d+)\.(\d+)\.(\d+)/", $_SERVER['HTTP_SP_HOST'], $matches)) {
                $realaddr = "{$matches[1]}.{$matches[2]}.{$matches[3]}.{$matches[4]}";
            }
            elseif (preg_match ("/.*\sfor\s(.+)/", $_SERVER['HTTP_FORWARDED'], $matches)) {
                $realhost = $matches[1];
            }
            elseif (preg_match ("/\-\@(.+)/", $_SERVER['HTTP_FROM'], $matches)) {
                $realhost = $matches[1];
            }
            if (!$realaddr and $realhost) {
                $realaddr = gethostbyname ($realhost);
            }
        }
        return array($addr, $host, $proxyflg, $realaddr, $realhost);
    }

    /**
     * Protect code generation
     *
     * @access  public
     * @param   Integer $timestamp  Timestamp
     * @param   Boolean $limithost  Whether or not to check for same host
     * @return  String  Protect code (12 alphanumeric characters)
     */
    public static function pcode($timestamp = 0, $limithost = TRUE) {
        if (!$timestamp) {
            $timestamp = CURRENT_TIME;
        }
        $ukey = 0;
        if ($limithost) {
            $remoteaddr = '0.0.0.0';
            if ($_SERVER['REMOTE_ADDR']) {
                $remoteaddr = $_SERVER['REMOTE_ADDR'];
            }
            $ukey = hexdec(substr(md5($remoteaddr), 0, 8));
        }

        $basecode =  dechex ($timestamp + $ukey);
        $cryptcode = crypt ($basecode . substr($GLOBALS['CONF']['ADMINPOST'], -4), substr($GLOBALS['CONF']['ADMINPOST'], -4) . $basecode);
        $cryptcode = substr (preg_replace ("/\W/", "", $cryptcode), -4);
        $pcode = dechex ($timestamp) . $cryptcode;
        return $pcode;
    }

    /**
     * Protect code verification
     *
     * @access  public
     * @param   String  $pcode  Protect code (12 alphanumeric characters)
     * @param   Boolean $limithost  Whether or not to check for same host
     * @return  Integer Timestamp
     */
    public static function pcode_verify($pcode, $limithost = TRUE) {

        if (strlen($pcode) != 12) {
            return;
        }
        $timestamphex = substr($pcode, 0, 8);
        $cryptcode = substr($pcode, 8, 4);

        $ukey = 0;
        if ($limithost) {
            $remoteaddr = '0.0.0.0';
            if ($_SERVER['REMOTE_ADDR']) {
                $remoteaddr = $_SERVER['REMOTE_ADDR'];
            }
            $ukey = hexdec(substr(md5($remoteaddr), 0, 8));
        }

        $timestamp = hexdec ($timestamphex);
        $basecode = dechex ($timestamp + $ukey);
        $verifycode = crypt ($basecode . substr($GLOBALS['CONF']['ADMINPOST'], -4), substr($GLOBALS['CONF']['ADMINPOST'], -4) . $basecode);
        $verifycode = substr (preg_replace ("/\W/", "", $verifycode), -4);
        if ($cryptcode != $verifycode) {
            return;
        }
        return $timestamp;
    }

    /**
     * Checkbox flag output process
     *
     * @access  public
     * @param   Integer $flag  Checkbox flag
     * @return  String  String for checkbox
     */
    public static function chkval($flag = 0, $attrvalue = FALSE) {
        if ($flag) {
            if ($attrvalue) {
                return 'checked';
            }
            else {
                return ' checked="checked"';
            }
        }
    }

    /**
     * Escaping for HTML display
     *
     * @access  public
     * @param   String  $value  Original string
     * @return  String  String after escaping process
     */
    public static function html_escape($value) {
        if ($value == '') {
            return $value;
        }
        if (!preg_match("/^\w+$/", $value)) {
            $value = htmlspecialchars($value, ENT_QUOTES);
        }
        $value = str_replace("\015\012", "\015", $value);
        $value = str_replace("\012", "\015", $value);
        $value = str_replace("\015$", "", $value);
        $value = str_replace(",", "&#44;", $value);

        return $value;
    }

    /**
     * Unescaping for HTML display
     *
     * @access  public
     * @param   String  $value  Original string
     * @return  String  String after unescaping process
     */
    public static function html_decode($value) {
        if ($value == '') {
            return $value;
        }

        if (!preg_match("/^\w+$/", $value)) {
            $value = strtr($value, array_flip(get_html_translation_table(HTML_ENTITIES)));
            $value = preg_replace("/&#([0-9]+);/me", "chr('\\1')", $value);
        }
        return $value;
    }

    /**
     * Time format conversion
     *
     * @access  public
     * @param   Integer $timestamp  Timestamp
     * @return  String  Date string
     */
    public static function getdatestr($timestamp, $format = "") {
        if (!$format) {
            $format = "Y/m/d(-) H:i:s";
        }
        $datestr = date($format, $timestamp);
        if (strrpos($format, '-') !== FALSE) {
            if (!isset($wdays)) {
                $wdays = [ T('SUNDAY'), T('MONDAY'), T('TUESDAY'), T('WEDNESDAY'), T('THURSDAY'), T('FRIDAY'), T('SATURDAY') ];
            }
            $datestr = str_replace('-', $wdays[date("w", $timestamp)], $datestr);
        }
        return $datestr;
    }

    /**
     * Numeric character formatting
     *
     * @access  public
     * @param   Integer $numberstr  Original string
     * @return  String  Character string after formatting
     */
    public static function fixnumberstr($numberstr) {
        $numberstr = trim($numberstr);
        $twobytenumstr = array ('０', '１', '２', '３', '４', '５', '６', '７', '８', '９', );
        for ($i = 0; $i < count($twobytenumstr); $i++) {
            $numberstr = str_replace($twobytenumstr[$i], "$i", $numberstr);
        }
        if (is_numeric ($numberstr)) {
            return $numberstr;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Escape link strings
     *
     * This process is to deal with XSS vunerabilities
     *
     * @access  public
     * @param   Integer $numberstr  Original string
     * @return  String  Character string after escaping
     */
    public static function escape_url($src_url) {
        $src_url = preg_replace("/script:/i", "script", $src_url);
        $src_url = urlencode($src_url);
        $src_url = str_replace ("%2F", "/", $src_url);
        $src_url = str_replace ("%3A", ":", $src_url);
        $src_url = str_replace ("%3D", "=", $src_url);
        $src_url = str_replace ("%23", "#", $src_url);
        $src_url = str_replace ("%26", "&", $src_url);
        $src_url = str_replace ("%3B", ";", $src_url);
        $src_url = str_replace ("%3F", "?", $src_url);
        $src_url = str_replace ("%25", "%", $src_url);

        return $src_url;
    }

    /**
     * Convert image tags to links
     *
     * @access  public
     * @param   String  $value  Original string
     * @return  String  String after tag conversion
     */
    public static function conv_imgtag ($value) {
        if ($value == '') {
            return $value;
        }
        while (preg_match("/(<a href=[^>]+>)<img ([^>]+)>(<\/a>)/i", $value, $matches)) {
            if (preg_match("/alt=\"([^\"]+)\"/", $matches[2], $submatches)) {
                $altvalue = $submatches[1];
            }
            elseif (preg_match("/src=\"([^\"]+)\"/", $matches[2], $submatches)) {
                $altvalue = substr($submatches[1], strrpos($submatches[1], '/'));
            }
            $value = str_replace($matches[0], " [{$matches[1]}{$altvalue}{$matches[3]}] ", $value);
        }
        return $value;
    }

    /**
     * Encoding 6-character hexidecimal strings into base64
     *
     * @access  public
     * @param   String  $inputhex  6-character hexidecimal string
     * @return  String  4-character base64 string
     */
    public static function threebytehex_base64($inputhex) {
        $inputdec = hexdec($inputhex);

        $a = floor($inputdec / 262144);
        $tmp_a = $inputdec - 262144 * $a;
        $b = floor($tmp_a / 4096);
        $tmp_b = $tmp_a - 4096 * $b;
        $c = floor($tmp_b / 64);
        $d = $tmp_b - 64 * $c;

        $basestr = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $base64val = $basestr[$a] . $basestr[$b] . $basestr[$c] . $basestr[$d];
        return $base64val;
    }

    /**
     * Decoding base64 strings into 6-character hexidecimal
     *
     * @access  public
     * @param   String  $str  4-character base64 string
     * @return  String  6-character hexidecimal string
     */
    public static function base64_threebytehex($str) {
        if (strlen($str) != 4) {
            return '';
        }
        $basestr = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $decval =
            262144 * @strrpos($basestr, substr($str, 0, 1))
            + 4096 * @strrpos($basestr, substr($str, 1, 1))
            + 64 * @strrpos($basestr, substr($str, 2, 1))
            + @strrpos($basestr, substr($str, 3, 1));
        $hexval = str_pad(@dechex($decval), 6, "0", STR_PAD_LEFT);
        return $hexval;
    }

    /**
     * Measure the time difference between microtime() strings
     *
     * @access  public
     * @param   String  $a  Measurement start time microtime() string
     * @param   String  $b  Measurement end time microtime() string
     * @return  String  Time difference string
     */
    public static function microtime_diff($a, $b) {
        list($a_dec, $a_sec) = explode(" ", $a);
        list($b_dec, $b_sec) = explode(" ", $b);
        return $b_sec - $a_sec + $b_dec - $a_dec;
    }

    /**
     * Get lines from file
     *
     * @access  public
     * @param   Integer $fh             File pointer
     * @param   Integer $maxbuffersize  Read buffer size
     * @return  String  Line string
     */
    public static function fgetline(&$fh, $maxbuffersize = 16000) {
        $line = '';
        do {
            $line .= fgets($fh, $maxbuffersize);
        } while (strrpos($line, "\n") === FALSE and !feof($fh));
        return strlen ($line) == 0 ? FALSE : $line;
    }


    /**
     * Check if there's an IP address in the specified IP address band
     * @param   String  $cidraddr   IP address bandwidth in CIDR format (e.g. 210.153.84.0/24)
     * @param   String  $checkaddr  IP address to check (e.g. 210.153.84.7)
     * @return  Boolean Result
     */
    public static function checkiprange($cidraddr, $checkaddr) {
        list($netaddr, $cidrmask) = explode("/", $cidraddr);
        $netaddr_long = ip2long($netaddr);
        $cidrmask = pow(2, 32 - $cidrmask) - 1;
        $bits1 = str_pad(decbin($netaddr_long), 32, "0", "STR_PAD_LEFT");
        $bits2 = str_pad(decbin($cidrmask), 32, "0", "STR_PAD_LEFT");
        $final = '';
        for ($i = 0; $i < 32; $i++) {
            if ($bits1[$i] == $bits2[$i]) {
                $final .= $bits1[$i];
            }
            if ($bits1[$i] == 1 and $bits2[$i] == 0) {
                $final .= $bits1[$i];
            }
            if ($bits1[$i] == 0 and $bits2[$i] == 1) {
                $final .= $bits2[$i];
            }
        }
        $final_long = ip2long(long2ip(bindec($final)));
        $checkaddr_long = ip2long($checkaddr);
        if ($checkaddr_long >= $netaddr_long and $checkaddr_long <= $final_long) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Host name pattern list matching
     *
     * @access  public
     * @param   Array   $hostlist Host name pattern list
     * @return  Boolean Match or not
     */
    public static function hostname_match($hostlist,$hostagent) {
        if (!$hostlist or !is_array($hostlist)) {
            return;
        }
        $hit = FALSE;
        list ($addr, $host, $proxyflg, $realaddr, $realhost) = Func::getuserenv();
        $agent = $_SERVER['HTTP_USER_AGENT'];
        foreach ($hostlist as $hostpattern) {
            foreach ($hostagent as $hostagentpattern) {
                if ((preg_match("/$hostpattern/", $host) or preg_match("/$hostpattern/", $realhost)) or preg_match("/$hostagentpattern/", $agent)) {
                    $hit = TRUE;
                    break;
                }
            }
        }
        return $hit;
    }

    /**
     * For debugging
     *
     */
    public static function debugwrite($debugstr, $printdate = TRUE, $debugfile = "debug.txt") {
        $fhdebug = @fopen($debugfile, "ab");
        if (!$fhdebug) {
            return;
        }
        flock ($fhdebug, 2);
        if ($printdate) {
            fwrite ($fhdebug, date("Y/m/d H:i:s\t (T)", CURRENT_TIME));
        }
        fwrite ($fhdebug, "$debugstr\n");
        flock ($fhdebug, 3);
        fclose ($fhdebug);
    }
}
/* end of class Func */
