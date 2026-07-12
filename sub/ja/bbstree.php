<?php

/*

くずはすくりぷとPHP ver0.0.7alpha (13:04 2003/02/18)
ツリービューモジュール

* Todo

* Memo

http://www.hlla.is.tsukuba.ac.jp/~yas/gen/it-2002-10-28/


*/

if(!defined("INCLUDED_FROM_BBS")) {
    header ("Location: ../../bbs.php?m=tree");
    exit();
}


/*
 * モジュール固有設定
 *
 * $CONFに追加・上書きされます。
 */
$GLOBALS['CONF_TREEVIEW'] = array(

    # 枝の色
    'C_BRANCH' => '5ff',

    # 更新時間表示の色
    'C_UPDATE' => 'ccc',

    # 更新時間表示の色
    'C_NEWMSG' => 'fca',

    # 表示ツリー数
    'TREEDISP' => 32,

);





/**
 * ツリービューモジュール
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Treeview extends Bbs {

    /**
     * コンストラクタ
     *
     */
    function __construct() {
        $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_TREEVIEW']);
        parent::__construct();
        $this->t->readTemplatesFromFile($this->c['TEMPLATE_TREEVIEW']);
    }


    /**
     * メイン処理
     */
    function main() {

        # 実行時間測定開始
        $this->setstarttime();

        # フォーム取得前処理
        $this->procForm();

        # 個人用設定反映
        if (@$this->f['treem'] == 'p') {
            $this->f['m'] = 'p';
        }
        $this->refcustom();
        $this->setusersession();

        # gzip圧縮転送
        if ($this->c['GZIPU']) {
            ob_start("ob_gzhandler");
        }

        # 書き込み処理
        if (@$this->f['treem'] == 'p' and trim(@$this->f['v'])) {

            # 環境変数取得
            $this->setuserenv();

            # パラメータチェック
            $posterr = $this->chkmessage();

            # 書き込み処理
            if (!$posterr) {
                $posterr = $this->putmessage($this->getformmessage());
            }

            # ２重書き込みエラーなど
            if ($posterr == 1) {
                $this->prttreeview();
            }
            # プロテクトコード時間経過のため再表示
            else if ($posterr == 2) {
                if (@$this->f['f']) {
                    $this->prtfollow(TRUE);
                }
                else {
                    $this->prttreeview(TRUE);
                }
            }
            # 管理モード移行
            else if ($posterr == 3) {
                define('BBS_ACTIVATED', TRUE);
                require_once(PHP_BBSADMIN);
                $bbsadmin = new Bbsadmin($this);
                $bbsadmin->main();
            }
            # 書き込み完了画面
            else if (@$this->f['f']) {
                $this->prtputcomplete();
            }
            else {
                $this->prttreeview();
            }
        }
        # 環境設定画面表示
        else if (@$this->f['setup']) {
            $this->prtcustom('tree');
        }
        # スレッドのツリー表示
        else if (@$this->f['s']) {
            $this->prtthreadtree();
        }
        # ツリービューメイン画面
        else {
            $this->prttreeview();
        }

        if ($this->c['GZIPU']) {
            ob_end_flush();
        }
    }





    /**
     * ツリービューを表示
     *
     * @todo  一部のログが削除・流れている場合の対策
     */
    function prttreeview($retry = FALSE) {

        # 表示メッセージ取得
        list ($logdata, $bindex, $eindex, $lastindex) = $this->getdispmessage();

        $isreadnew = FALSE;
#20200210 擬古猫・未読ポインタfix
#        if ((@$this->f['readnew'] or ($this->s['MSGDISP'] == '0' and $bindex == 1)) and @$this->f['p'] > 0) {
        if ((@$this->f['readnew'] or ($this->s['MSGDISP'] == '0' )) and @$this->f['p'] > 0) {
            $isreadnew = TRUE;
        }

        $customstyle = $this->t->getParsedTemplate('tree_customstyle');

        # HTMLヘッダ部分出力
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ツリービュー', '', $customstyle);

        # フォーム部分
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

        # メイン上部
        $this->t->displayParsedTemplate('treeview_upper');

        $threadindex = 0;

        # 最終書き込み時刻が最新のスレッド順に処理
        while (count($logdata) > 0) {

            $msgcurrent = $this->getmessage(array_shift($logdata));
            if (!$msgcurrent['THREAD']) {
                $msgcurrent['THREAD'] = $msgcurrent['POSTID'];
            }

            # スレッドを$logdataから抽出し、スレッドのメッセージ配列 $thread を作成
            $thread = array($msgcurrent);
            $i = 0;
            while ($i < count($logdata)) {
                $message = $this->getmessage($logdata[$i]);
                if ($message['THREAD'] == $msgcurrent['THREAD']
                    or $message['POSTID'] == $msgcurrent['THREAD']) {
                    array_splice($logdata, $i, 1);
                    $thread[] = $message;
                    # 根の発見
                    if ($message['POSTID'] == $message['THREAD'] or !$message['THREAD']) {
                        break;
                    }
                }
                else {
                    $i++;
                }
            }

            # 未読リロード
            if ($isreadnew) {
                $hit = FALSE;
                for ($i = 0; $i < count($thread); $i++) {
                    if ($thread[$i]['POSTID'] > $this->f['p']) {
                        $hit = TRUE;
                        break;
                    }
                }
                if (!$hit) {
                    continue;
                }
            }
            else if ($this->s['MSGDISP'] < 0) {
                break;
            }
            # 開始index
            else if ($threadindex < $bindex - 1) {
                $threadindex++;
                continue;
            }

            #「参考」からの参照ID抽出
            foreach ($thread as $message) {
                if (!@$message['REFID']) {
                    if (preg_match("/<a href=\"m=f&s=(\d+)[^>]+>([^<]+)<\/a>$/i", $message['MSG'], $matches)) {
                        $message['REFID'] = $matches[1];
                    }
                    else if (preg_match("/<a href=\"mode=follow&search=(\d+)[^>]+>([^<]+)<\/a>$/i", $message['MSG'], $matches)) {
                        $message['REFID'] = $matches[1];
                    }
                }
            }

            # $thread のテキストツリーを出力
            $this->prttexttree($msgcurrent, $thread);

            $threadindex++;

            if ($threadindex > $eindex - 1) {
                break;
            }
        }

        $eindex = $threadindex;

        # メッセージ情報
        if ($this->s['MSGDISP'] < 0) {
            $msgmore = '';
        }
        else if ($eindex > 0) {
            $msgmore = "以上は、現在登録されている最終更新順{$bindex}番目から{$eindex}番目までのスレッドです。";
        }
        else {
            $msgmore = '未読メッセージはありません。';
        }
        if (count($logdata) == 0) {
            $msgmore .= 'これ以下のスレッドはありません。';
        }
        $this->t->addVar('treeview_lower', 'MSGMORE', $msgmore);


        # ナビゲートボタン
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

        # 管理者投稿
        if ($this->c['BBSMODE_ADMINONLY'] == 0) {
            $this->t->setAttribute("adminlogin", "visibility", "hidden");
        }

        # メイン下部
        $this->t->displayParsedTemplate('treeview_lower');

        print $this->prthtmlfoot ();
    }





    /**
     * テキストツリー出力
     *
     * @param   Array   &$msgcurrent  親メッセージ
     * @param   Array   &$thread      親子を含むメッセージの配列
     */
    function prttexttree(&$msgcurrent, &$thread) {

        print "<pre class=\"msgtree\"><a href=\"{$this->s['DEFURL']}&amp;m=t&amp;s={$msgcurrent['THREAD']}\" target=\"link\">{$this->c['TXTTHREAD']}</a>";
        $msgcurrent['WDATE'] = Func::getdatestr($msgcurrent['NDATE']);
        print "<span class=\"update\"> [更新日：{$msgcurrent['WDATE']}]</span>\r";
        $tree =& $this->gentree(array_reverse($thread), $msgcurrent['THREAD']);
        $tree = str_replace("</span><span class=\"bc\">", "", $tree);
        $tree = str_replace("</span>　<span class=\"bc\">", "　", $tree);
        $tree = '　' . str_replace("\r", "\r　", $tree);

        #20181110 擬古猫 特殊文字をエスケープする
        $tree = str_replace("{","&#123;", $tree);
        $tree = str_replace("}","&#125;", $tree);

    #20200207 擬古猫 span style=タグ有効
#    $tree = preg_replace("/&lt;span style=&quot;(.+?)&quot;&gt;(.+?)&lt;\/span&gt;/","<span style=\"$1\">$2</span>", $tree);

    #20200207 擬古猫 font color="タグ有効
#    $tree = preg_replace("/&lt;font color=&quot;([a-zA-Z#0-9]+)&quot;&gt;(.+?)&lt;\/font&gt;/","<font color=\"$1\">$2</font>", $tree);

    #20200201 擬古猫 font color=タグ有効
#    $tree = preg_replace("/&lt;font color=([a-zA-Z#0-9]+)&gt;(.+?)&lt;\/font&gt;/","<font color=$1>$2</font>", $tree);

        #20181110 擬古猫 Unicode変換用
        #$tree  = preg_replace("/&amp;#(\d+);/","&#$1;", $tree );

        #20181115 擬古猫 個別NG
        #$tree  = preg_replace("/(.+)/","<span class= \"ngline\">$1</span>", $tree );

        print $tree . "</pre>\n\n<hr>\n\n";

    }




    /**
     * テキストツリー生成の再帰処理関数
     *
     * @param   Array   &$treemsgs  親子を含むメッセージの配列
     * @param   Integer $parentid   親ID
     * @return  String  &$treeprint 親子のツリー文字列
     */
    function &gentree(&$treemsgs, $parentid) {

        # ツリー文字列
        $treeprint = '';

        # 親メッセージの出力
        reset($treemsgs);
        foreach ($treemsgs as $pos => $treemsg) {
            if ($treemsg['POSTID'] == $parentid) {

                # 参考の消去
                $treemsg['MSG'] = preg_replace("/<a href=[^>]+>参考：[^<]+<\/a>/i", "", $treemsg['MSG'], 1);

                # 引用の消去
                $treemsg['MSG'] = preg_replace("/(^|\r)&gt;[^\r]*/", "", $treemsg['MSG']);
                $treemsg['MSG'] = preg_replace("/^\r+/", "", $treemsg['MSG']);
                $treemsg['MSG'] = rtrim($treemsg['MSG']);

                #20181117 擬古猫 個別NG
                $treemsg['MSG']  = preg_replace("/(.+)/","<div class= \"ngline\">$1</div>\r", $treemsg['MSG']);

                # フォロー画面へのリンク
                $treeprint .= "<a href=\"{$this->s['DEFURL']}&amp;m=f&amp;s={$parentid}\" target=\"link\">{$this->c['TXTFOLLOW']}</a>";

                # 投稿者名
                if ($treemsg['USER'] and $treemsg['USER'] != $this->c['ANONY_NAME']) {
                    $treeprint .= "投稿者：".preg_replace("/<[^>]*>/", '', $treemsg['USER'])."\r";
                }

                # 新着表示
                if (@$this->f['p'] > 0 and $treemsg['POSTID'] > $this->f['p']) {
                    $treemsg['MSG'] = '<span class="newmsg">' . $treemsg['MSG'] . '</span>';
                }

                # 画像BBSの画像を非表示
                $treemsg['MSG'] = Func::conv_imgtag($treemsg['MSG']);

                $treeprint .= $treemsg['MSG'];

                # 配列から消去
                array_splice($treemsgs, $pos, 1);
                break;
            }
        }

        # 子のIDを列挙
        $childids = array();
        reset($treemsgs);
        foreach ($treemsgs as $treemsg) {
            if ($treemsg['REFID'] == $parentid) {
                $childids[] = $treemsg['POSTID'];
            }
        }

        # もし子があるなら、枝「│」をのばす
        if ($childids) {
            $treeprint = str_replace("\r", "\r".'<span class="bc">│</span>', $treeprint);
        }
        # なければ行頭空白
        else {
            $treeprint = str_replace("\r", "\r".'　', $treeprint);
        }

        # 子のツリー文字列を取得し、結合
        $childidcount = count($childids) - 1;
        foreach ($childids as $idx => $childid) {
            $childtree =& $this->gentree($treemsgs, $childid);

            # もし次の子があるなら、枝「├」から「│」をのばす
            if ($idx < $childidcount) {
                $childtree = '<span class="bc">├</span>' . str_replace("\r", "\r".'<span class="bc">│</span>', $childtree);
            }
            # 最後の子なら枝「└」から行頭空白
            else {
                $childtree = '<span class="bc">└</span>' . str_replace("\r", "\r".'　', $childtree);
            }

            # 子のツリー文字列を親に結合
            $treeprint .= "\r" . $childtree;
        }

        return $treeprint;
    }





    /**
     * 表示範囲のメッセージとパラメータの取得
     *
     * @access  public
     * @return  Array   $logdatadisp  ログ行配列
     * @return  Integer $bindex       開始index
     * @return  Integer $eindex       終端index
     * @return  Integer $lastindex    全ログの終端index
     * @return  Integer $msgdisp      表示件数
     */
    function getdispmessage() {

        $logdata = $this->loadmessage();

        # 未読ポインタ（最新POSTID）
        $items = @explode (',', $logdata[0], 3);
        $toppostid = @$items[1];

        # 表示件数
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

        # 開始index
        $bindex = @$this->f['b'];
        if (!$bindex) {
            $bindex = 0;
        }

        # 終端index
        $eindex = $bindex + $msgdisp;

        # 未読リロード
#20200210 擬古猫・未読ポインタfix
#        if ((@$this->f['readnew'] or ($msgdisp == '0' and $bindex == 0)) and @$this->f['p'] > 0) {
        if ((@$this->f['readnew'] or ($msgdisp == '0' )) and @$this->f['p'] > 0) {
            $bindex = 0;
#            $eindex = 0;
      $eindex = $toppostid - $this->f['p'];
        }

        # 最後のページの場合、切り詰め
        $lastindex = count($logdata);
        if ($eindex > $lastindex) {
            $eindex = $lastindex;
        }

        # -1件表示
        if ($msgdisp < 0) {
            $bindex = 0;
            $eindex = 0;
        }

        $this->s['TOPPOSTID'] = $toppostid;
        $this->s['MSGDISP'] = $msgdisp;

#20200210 擬古猫・未読ポインタfix
    $this->t->addGlobalVars(array(
      'TOPPOSTID' => $this->s['TOPPOSTID'],
      'MSGDISP' => $this->s['MSGDISP']
    ));
        return array($logdata, $bindex + 1, $eindex, $lastindex);
    }





    /**
     * 個別スレッドのツリー表示
     *
     */
    function prtthreadtree() {

        if (!@$this->f['s']) {
            $this->prterror ( 'パラメータがありません。' );
        }

        $customstyle = <<<__XHTML__
    .bc { color:#{$this->c['C_BRANCH']}; }
    .update { color:#{$this->c['C_UPDATE']}; }
    .newmsg { color:#{$this->c['C_NEWMSG']}; }

__XHTML__;

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' ツリー表示', '', $customstyle);
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
<span class="bbsmsg"><a href="{$this->s['DEFURL']}">戻る</a></span>
__XHTML__;

        print $this->prthtmlfoot ();

    }





}


?>
