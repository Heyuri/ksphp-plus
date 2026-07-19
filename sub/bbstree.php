<?php

/*

KuzuhaScriptPHP ver0.0.7alpha (13:04 2003/02/18)
Tree view module

* Todo

* Memo

http://www.hlla.is.tsukuba.ac.jp/~yas/gen/it-2002-10-28/


*/

if(!defined("INCLUDED_FROM_BBS")) {
    header ("Location: ../bbs.php?m=tree");
    exit();
}


/*
 * Module-specific settings
 *
 * They will be added to/overwritten by $CONF.
 */
$GLOBALS['CONF_TREEVIEW'] = array(

    # Branch color
    'C_BRANCH' => '5ff',

    # Update time display color
    'C_UPDATE' => 'ccc',

    # New message color
    'C_NEWMSG' => 'fca',

    # Number of trees displayed
    'TREEDISP' => 32,

);





/**
 * Tree view module
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Treeview extends Bbs {

    /**
     * Constructor
     *
     */
    function __construct() {
        $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF_TREEVIEW'], $GLOBALS['CONF']);
        parent::__construct();
        $this->t->readTemplatesFromFile($this->c['TEMPLATE_TREEVIEW']);
    }


    /**
     * Main processing
     */
    function main() {

        # Start measuring execution time
        $this->setstarttime();

        # Form acquisiation preprocessing
        $this->procForm();

        # Reflect personal settings
        if (@$this->f['treem'] == 'p') {
            @$this->f['m'] = 'p';
        }
        $this->refcustom();
        $this->setusersession();

        # gzip compressed transfer
        if ($this->c['GZIPU']) {
            ob_start("ob_gzhandler");
        }

        # Post operation
        if (@$this->f['treem'] == 'p' and trim(@$this->f['v'] ?? '')) {

            # Get environment variables
            $this->setuserenv();

            # Parameter check
            $posterr = $this->chkmessage();

            # Post operation
            if (!$posterr) {
                $posterr = $this->putmessage($this->getformmessage());
            }

            # Double post error, etc
            if ($posterr == 1) {
                $this->prttreeview();
            }
            # Protect code redisplaying due to time lapse
            else if ($posterr == 2) {
                if (@$this->f['f']) {
                    $this->prtfollow(TRUE);
                }
                else {
                    $this->prttreeview(TRUE);
                }
            }
            # Admin mode transition
            else if ($posterr == 3) {
                define('BBS_ACTIVATED', TRUE);
                require_once(PHP_BBSADMIN);
                $bbsadmin = new Bbsadmin($this);
                $bbsadmin->main();
            }
            # Post completion page
            else if (@$this->f['f']) {
                $this->prtputcomplete();
            }
            else {
                $this->prttreeview();
            }
        }
        # User settings page display
        else if (@$this->f['setup']) {
            $this->prtcustom('tree');
        }
        # Tree view of threads
        else if (@$this->f['s']) {
            $this->prtthreadtree();
        }
        # Tree view main page
        else {
            $this->prttreeview();
        }

        if ($this->c['GZIPU']) {
            ob_end_flush();
        }
    }





    /**
     * Displaying tree view
     *
     * @todo  Measures for when some logs are deleted/removed
     */
    function prttreeview($retry = FALSE) {

        # Get display message
        list ($logdata, $bindex, $eindex, $lastindex) = $this->getdispmessage();

        $isreadnew = FALSE;
#20200210 Gikoneko: unread pointer fix
#        if ((@$this->f['readnew'] or ($this->s['MSGDISP'] == '0' and $bindex == 1)) and @$this->f['p'] > 0) {
        if ((@$this->f['readnew'] or ($this->s['MSGDISP'] == '0' )) and @$this->f['p'] > 0) {
            $isreadnew = TRUE;
        }

        $customstyle = $this->t->getParsedTemplate('tree_customstyle');

        # HTML header partial output
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('TITLE_TREEVIEW_MAIN'), '', $customstyle);

        # Form section
        $dtitle = "";
        $dmsg = "";
        $dlink = "";
        if ($retry) {
            $dtitle = @$this->f['t'];
            $dmsg = @$this->f['v'];
            $dlink = @$this->f['l'];
        }
        $forminput = '<input type="hidden" name="m" value="tree" /><input type="hidden" name="treem" value="p" />';
        $this->setform ($dtitle, $dmsg, $dlink, $forminput);

        # Upper main section
        $this->t->displayParsedTemplate('treeview_upper');

        $threadindex = 0;

        #20260716 Replaced re-scanning/re-parsing the entire log per thread
        #with single-pass indexing.
        list($order, $buckets, $newest) = $this->indexthreads($logdata);

        # Process in order of threads with the latest post time
        foreach ($order as $tid) {

            # Unread reload
            #20260716 Since threads are processed in order of most recently
            #updated first, once a thread with no unread messages is reached,
            #all remaining threads are also fully read -> break. $newest[$tid]
            #is that thread's highest POSTID, so this performs the same check
            #as the old per-message loop, without any parsing.
            if ($isreadnew) {
                if (!($newest[$tid] > @$this->f['p'])) {
                    break;
                }
            }
            else if ($this->s['MSGDISP'] < 0) {
                break;
            }
            # Beginning index
            else if ($threadindex < $bindex - 1) {
                $threadindex++;
                continue;
            }

            # Fully parse only the threads that will actually be displayed
            $thread = array();
            foreach ($buckets[$tid] as $logline) {
                $thread[] = $this->getmessage($logline);
            }
            if (!$thread[0]['THREAD']) {
                $thread[0]['THREAD'] = $thread[0]['POSTID'];
            }
            $msgcurrent = $thread[0];

            # Extract reference IDs from "reference"
            #20260716 Gikoneko: foreach was by-value, so the recovered REFID never
            #propagated back into $thread, causing legacy posts without a stored
            #REFID field to be dropped from the tree view. Changed to by-reference.
            foreach ($thread as &$message) {
                if (!@$message['REFID']) {
                    if (preg_match("/<a href=\"m=f&s=(\d+)[^>]+>([^<]+)<\/a>$/i", $message['MSG'], $matches)) {
                        $message['REFID'] = $matches[1];
                    }
                    else if (preg_match("/<a href=\"mode=follow&search=(\d+)[^>]+>([^<]+)<\/a>$/i", $message['MSG'], $matches)) {
                        $message['REFID'] = $matches[1];
                    }
                }
            }
            unset($message);

            # Output $thread text tree
            $this->prttexttree($msgcurrent, $thread);

            $threadindex++;

            if ($threadindex > $eindex - 1) {
                break;
            }
        }

        $eindex = $threadindex;

        # Message information
        if ($this->s['MSGDISP'] < 0) {
            $msgmore = '';
        }
        else if ($eindex > 0) {
            $msgmore = str_replace(['{BINDEX}','{EINDEX}'], [$bindex,$eindex], T('TREE_RANGE_TEXT'));
        }
        else {
            $msgmore = T('NO_UNREAD_MESSAGES');
        }
        #20260716 The old code's count($logdata) == 0 check relied on the loop
        #destructively consuming the log array. Changed to compare thread
        #progress against the total number of threads.
        if ($threadindex >= count($order)) {
            $msgmore .= T('TREE_NO_THREADS_BELOW');
        }
        $this->t->addVar('treeview_lower', 'MSGMORE', $msgmore);


        # Navigation button
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

        # Administrator post
        if ($this->c['BBSMODE_ADMINONLY'] == 0) {
            $this->t->setAttribute("adminlogin", "visibility", "hidden");
        }

        # Lower main section
        $this->t->displayParsedTemplate('treeview_lower');

        print $this->prthtmlfoot ();
    }





    /**
     * Index the entire log into per-thread buckets in a single pass.
     *
     * Only the first 4 CSV fields (NDATE,POSTID,PROTECT,THREAD) are read
     * here. These are always numeric, and Func::html_escape() already
     * converts every comma inside the post body to "&#44;" at write time,
     * so a plain explode() is never affected by the body content.
     * getmessage() is only ever called on the threads that will actually
     * be displayed.
     *
     * A reply always carries its parent's THREAD field (see putmessage()).
     * If the parent is a legacy root with no THREAD field, it carries the
     * parent's POSTID instead. So "THREAD, or POSTID if empty" identifies
     * the thread of every row -- this is exactly the information the old
     * scan used to re-derive on every pass.
     *
     * @param   Array   $logdata  Log line array (newest first)
     * @return  Array   $order    Thread IDs (most recently updated first)
     * @return  Array   $buckets  Thread ID => log lines (newest first)
     * @return  Array   $newest   Thread ID => that thread's highest POSTID
     */
    function indexthreads(&$logdata) {

        $order   = array();
        $buckets = array();
        $newest  = array();

        foreach ($logdata as $logline) {

            # Same check as getmessage(): a well-formed line has at least 10 fields.
            if (substr_count($logline, ',') < 9) {
                continue;
            }

            $items  = explode(',', $logline, 5);
            $postid = $items[1];
            $tid    = $items[3];
            if (!$tid) {
                $tid = $postid;
            }

            if (!isset($buckets[$tid])) {
                $buckets[$tid] = array();
                $order[]       = $tid;
                # The log is strictly newest-first, so the first line seen
                # for a given thread carries that thread's highest POSTID.
                $newest[$tid]  = $postid;
            }
            $buckets[$tid][] = $logline;
        }

        return array($order, $buckets, $newest);
    }



    /**
     * Text tree output
     *
     * @param   Array   &$msgcurrent  Parent message
     * @param   Array   &$thread      Array of messages containing parents and children
     */
    function prttexttree(&$msgcurrent, &$thread) {

        print "<pre class=\"msgtree\"><a href=\"{$this->s['DEFURL']}&amp;m=t&amp;s={$msgcurrent['THREAD']}\" target=\"link\">{$this->c['TXTTHREAD']}</a>";
        $msgcurrent['WDATE'] = Func::getdatestr($msgcurrent['NDATE']);
        print "<span class=\"update\"> " . sprintf(T('TREE_DATE_UPDATED'), $msgcurrent['WDATE']) . "</span>\r";
        #20260717 Gikoneko: avoid "Only variables should be passed by reference" notice
        $reversedthread = array_reverse($thread);
        $tree =& $this->gentree($reversedthread, $msgcurrent['THREAD']);
        $tree = str_replace("</span><span class=\"bc\">", "", $tree);
        $tree = str_replace("</span>　<span class=\"bc\">", "　", $tree);
        $tree = '　' . str_replace("\r", "\r　", $tree);

        #20181110 Gikoneko: Escape special characters
        $tree = str_replace("{","&#123;", $tree);
        $tree = str_replace("}","&#125;", $tree);

    #20200207 Gikoneko: span style=tag enabled
#    $tree = preg_replace("/&lt;span style=&quot;(.+?)&quot;&gt;(.+?)&lt;\/span&gt;/","<span style=\"$1\">$2</span>", $tree);

    #20200207 Gikoneko: font color="tag enabled
#    $tree = preg_replace("/&lt;font color=&quot;([a-zA-Z#0-9]+)&quot;&gt;(.+?)&lt;\/font&gt;/","<font color=\"$1\">$2</font>", $tree);

    #20200201 Gikoneko: font color=tag enabled
#    $tree = preg_replace("/&lt;font color=([a-zA-Z#0-9]+)&gt;(.+?)&lt;\/font&gt;/","<font color=$1>$2</font>", $tree);

        #20181110 Gikoneko: Unicode conversion
        #$tree  = preg_replace("/&amp;#(\d+);/","&#$1;", $tree );

        #20181115 Gikoneko: Personal word filter
        #$tree  = preg_replace("/(.+)/","<span class= \"ngline\">$1</span>", $tree );

        print $tree . "</pre>\n\n<hr>\n\n";

    }




    /**
     * Recursive function for text tree generation
     *
     * @param   Array   &$treemsgs  Array of messages containing parents and children
     * @param   Integer $parentid   Parent ID
     * @return  String  &$treeprint Parent-child tree string
     */
    function &gentree(&$treemsgs, $parentid) {

        # Tree string
        $treeprint = '';

        # Outputting parent message
        reset($treemsgs);
        foreach ($treemsgs as $pos => $treemsg) {
            if ($treemsg['POSTID'] == $parentid) {

                # Delete reference
                $treemsg['MSG'] = preg_replace("/<a href=[^>]+>Reference: [^<]+<\/a>/i", "", $treemsg['MSG'], 1);

                # Delete quotes
                $treemsg['MSG'] = preg_replace("/(^|\r)&gt;[^\r]*/", "", $treemsg['MSG']);
                $treemsg['MSG'] = preg_replace("/^\r+/", "", $treemsg['MSG']);
                $treemsg['MSG'] = rtrim($treemsg['MSG']);

                #20181117 Gikoneko: Personal word filter
                $treemsg['MSG']  = preg_replace("/(.+)/","<span class= \"ngline\">$1</span>\r", $treemsg['MSG']);

                # Link to the follow-up post page
                $treeprint .= "<a href=\"{$this->s['DEFURL']}&amp;m=f&amp;s={$parentid}\" target=\"link\">{$this->c['TXTFOLLOW']}</a>";

                # Username
                if ($treemsg['USER'] and $treemsg['USER'] != $this->c['ANONY_NAME']) {
                    $treeprint .= T('TREE_USER_LABEL').preg_replace("/<[^>]*>/", '', $treemsg['USER'])."\r";
                }

                # Display new arrivals
                if (@$this->f['p'] > 0 and $treemsg['POSTID'] > @$this->f['p']) {
                    $treemsg['MSG'] = '<span class="newmsg">' . $treemsg['MSG'] . '</span>';
                }

                # Hide images on the imageBBS
                $treemsg['MSG'] = Func::conv_imgtag($treemsg['MSG']);

                $treeprint .= $treemsg['MSG'];

                # Delete from array
                array_splice($treemsgs, $pos, 1);
                break;
            }
        }

        # Enumerate child IDs
        $childids = array();
        reset($treemsgs);
        foreach ($treemsgs as $treemsg) {
            if ($treemsg['REFID'] == $parentid) {
                $childids[] = $treemsg['POSTID'];
            }
        }

        # If there's children, extend the "│" branch
        if ($childids) {
            $treeprint = str_replace("\r", "\r".'<span class="bc">│</span>', $treeprint);
        }
        # If not, make the start of the line blank
        else {
            $treeprint = str_replace("\r", "\r".'　', $treeprint);
        }

        # Get the tree strings of children and join them together
        $childidcount = count($childids) - 1;
        foreach ($childids as $idx => $childid) {
            $childtree =& $this->gentree($treemsgs, $childid);

            # If there's another child, extend from "├" branch with a "│"
            if ($idx < $childidcount) {
                $childtree = '<span class="bc">├</span>' . str_replace("\r", "\r".'<span class="bc">│</span>', $childtree);
            }
            # If it's the last child, make the start of the line blank and use "└" branch
            else {
                $childtree = '<span class="bc">└</span>' . str_replace("\r", "\r".'　', $childtree);
            }

            # Join child string to its parent
            $treeprint .= "\r" . $childtree;
        }

        return $treeprint;
    }





    /**
     * Get display range messages and parameters
     *
     * @access  public
     * @return  Array   $logdatadisp  Log line array
     * @return  Integer $bindex       Beginning index
     * @return  Integer $eindex       Ending index
     * @return  Integer $lastindex    Last index for all logs
     * @return  Integer $msgdisp      Display results
     */
    function getdispmessage() {

        $logdata = $this->loadmessage();

        # Unread pointer (latest POSTID)
        $items = @explode (',', $logdata[0], 3);
        $toppostid = @$items[1];

        # Display results
        $msgdisp = Func::fixnumberstr(@$this->f['d']);
        if ($msgdisp === FALSE) {
            $msgdisp = $this->c['TREEDISP'];
        }
        else if ($msgdisp < 0) {
            $msgdisp = -1;
        }
        else if ($msgdisp > $this->c['LOGSAVE']) {
            $msgdisp = $this->c['LOGSAVE'];
        }
        if (@$this->f['readzero']) {
            $msgdisp = 0;
        }

        # Beginning index
        $bindex = @$this->f['b'];
        if (!$bindex) {
            $bindex = 0;
        }

        # Ending index
        $eindex = $bindex + $msgdisp;

        # Unread reload
#20200210 Gikoneko: unread pointer fix
#        if ((@$this->f['readnew'] or ($msgdisp == '0' and $bindex == 0)) and @$this->f['p'] > 0) {
        if ((@$this->f['readnew'] or ($msgdisp == '0' )) and @$this->f['p'] > 0) {
            $bindex = 0;
#            $eindex = 0;
      $eindex = $toppostid - @$this->f['p'];
        }

        # For the last page, truncate
        $lastindex = count($logdata);
        if ($eindex > $lastindex) {
            $eindex = $lastindex;
        }

        # Display -1 item
        if ($msgdisp < 0) {
            $bindex = 0;
            $eindex = 0;
        }

        $this->s['TOPPOSTID'] = $toppostid;
        $this->s['MSGDISP'] = $msgdisp;

#20200210 Gikoneko: unread pointer fix
    $this->t->addGlobalVars(array(
      'TOPPOSTID' => $this->s['TOPPOSTID'],
      'MSGDISP' => $this->s['MSGDISP']
    ));
        return array($logdata, $bindex + 1, $eindex, $lastindex);
    }





    /**
     * Tree view of individual threads
     *
     */
    function prtthreadtree() {

        if (!@$this->f['s']) {
            $this->prterror ( T('NO_PARAMETERS') );
        }

        $customstyle = <<<__XHTML__
    .bc { color:#{$this->c['C_BRANCH']}; }
    .update { color:#{$this->c['C_UPDATE']}; }
    .newmsg { color:#{$this->c['C_NEWMSG']}; }

__XHTML__;

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ' . T('TITLE_TREE_VIEW'), '', $customstyle);
        print "<hr>\n";

        $result = $this->msgsearchlist('t');
        if (@$this->f['ff']) {
            $msgcurrent = $result[count($result) - 1];
        }
        else {
            $msgcurrent = $result[0];
        }
        $this->prttexttree($msgcurrent, $result);

        print <<<__XHTML__
<span class="bbsmsg"><a href="{$this->s['DEFURL']}">Return</a></span>
__XHTML__;

        print $this->prthtmlfoot ();

    }





}


?>
