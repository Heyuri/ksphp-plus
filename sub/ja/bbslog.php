<?php

/*

くずはすくりぷとPHP ver0.0.7alpha (13:04 2003/02/18)
過去ログ閲覧モジュール

* Todo

*/

if(!defined("INCLUDED_FROM_BBS")) {
    header ("Location: ../../bbs.php?m=g");
    exit();
}


/*
 * モジュール固有設定
 *
 * $CONFに追加・上書きされます。
 */
$GLOBALS['CONF_GETLOG'] = array(

    # 複数ログの検索可能有無
    'MULTIPLESEARCH' => 1,

    # 検索語ハイライト色
    'C_QUERY' => 'FF8000',

    # 検索キーワード数の最大値
    'MAXKEYWORDS' => 10,

);


/**
 * 過去ログ閲覧モジュール
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Getlog extends Webapp {


    /**
     * コンストラクタ
     *
     */
    function __construct() {
        $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_GETLOG']);
        parent::__construct();
        $this->t->readTemplatesFromFile($this->c['TEMPLATE_LOG']);
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
        $this->refcustom();
        $this->setusersession();

        # gzip圧縮転送
        if ($this->c['GZIPU']) {
            ob_start("ob_gzhandler");
        }

        # 検索処理
        if (@$this->f['f']) {
            $this->prtsearchresult();
        }
        # ダウンロード
        else if (@$this->f['dl']) {
            $result = $this->prthtmldownload(@$this->f['dl']);
            if ($result) {
                $this->prtloglist();
            }
        }
        # トピック一覧
        else if (@$this->f['l']) {
            $result = $this->prttopiclist(@$this->f['l']);
            if ($result) {
                $this->prtloglist();
            }
        }
        # Zipアーカイブ
        else if (@$this->f['gm'] == 'z' and @$this->c['ZIPDIR']) {
            $this->prtarchivelist();
        }
        # 検索画面
        else {
            $this->prtloglist();
        }

        if ($this->c['GZIPU']) {
            ob_end_flush();
        }
    }





    /**
     * 検索画面表示
     *
     */
    function prtloglist() {

        $dir = $this->c['OLDLOGFILEDIR'];

        if ($this->c['OLDLOGFMT']) {
            $oldlogext = 'dat';
        }
        else {
            $oldlogext = 'html';
        }

        $files = array();

        $dh = opendir($dir);
        if (!$dh) {
            $this->prterror ('ディレクトリが開けませんでした');
        }
        while ($entry = readdir($dh)) {
            if (is_file($dir . $entry) and preg_match("/^\d+\.$oldlogext$/", $entry)) {
                $files[] = $entry;
            }
        }
        closedir ($dh);

        # ファイル名の自然順でソート
        natsort($files);

        # 更新時間が最新のファイルを標準でチェック
        $maxftime = 0;
        #20260717 Gikoneko: defensive init so PHPStan/PHP8 don't warn if $files is empty
        $checkedfile = '';
        foreach ($files as $filename) {
            $fstat = stat ($dir . $filename);
            if ($fstat[9] > $maxftime) {
                $maxftime = $fstat[9];
                $checkedfile = $filename;
            }
        }

        if ($this->c['ZIPDIR'] and function_exists("gzcompress")) {
            $this->t->setAttribute("ziplink", "visibility", "visible");
        }

        if (!$this->c['OLDLOGFMT']) {
            $this->t->setAttribute("topiclink", "visibility", "hidden");
        }
        if (!$this->dlchk()) {
            $this->t->setAttribute("dllink", "visibility", "hidden");
        }

        foreach ($files as $filename) {
            $fstat = stat ($dir . $filename);
            $fsize = $fstat[7];
            $ftime = date("Y/m/d H:i:s", $fstat[9]);
            $ftitle = '';
            $matches = array();
            if (preg_match("/^(\d\d\d\d)(\d\d)(\d\d)\.$oldlogext/", $filename, $matches)) {
                $ftitle = "{$matches[1]}/{$matches[2]}/{$matches[3]}";
            }
            else if (preg_match("/^(\d\d\d\d)(\d\d)\.$oldlogext/", $filename, $matches)) {
                $ftitle = "{$matches[1]}/{$matches[2]}";
            }
            else {
                $ftitle = $filename;
            }

            $checked = '';
            if ($filename == $checkedfile) {
                $checked = ' checked="checked"';
            }
            $checkbox = '';
            if (@$this->c['MULTIPLESEARCH']) {
                $checkbox = "<input type=\"checkbox\" name=\"f[]\" value=\"$filename\"$checked />";
            }
            else {
                $checkbox = "<input type=\"radio\" name=\"f\" value=\"$filename\"$checked />";
            }

            $this->t->clearTemplate('topiclink');
            $this->t->clearTemplate('dllink');
            $this->t->addVar('topiclink', 'FILENAME', $filename);
            $this->t->addVar('dllink', 'FILENAME', $filename);
            $this->t->addVars('filelist', array(
                'FCHECK' => $checkbox,
                'FILENAME' => $filename,
                'FTITLE' => $ftitle,
                'FTIME' => $ftime,
                'FSIZE' => $fsize,
            ));
            $this->t->parseTemplate('filelist', 'a');
        }

        $this->t->addVar('dateform', 'OLDLOGSAVESW', $this->c['OLDLOGSAVESW']);
        if ($this->c['BBSMODE_IMAGE'] == 1) {
            if ($this->c['SHOWIMG']) $this->t->addVar('sicheck', 'CHK_SI', ' checked="checked"');
            $this->t->setAttribute('sicheck', 'visibility', 'visible');
        }
        if (!$this->c['OLDLOGFMT'] or !$this->c['OLDLOGBTN']) {
            $this->t->setAttribute("check_bt", "visibility", "hidden");
        }
        if ($this->c['GZIPU']) $this->t->addVar('loglist', 'CHK_G', ' checked="checked"');

        # 出力
        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' 過去ログ検索');
        $this->t->displayParsedTemplate('loglist');
        print $this->prthtmlfoot ();

    }







    /**
     * 検索条件の取得
     */
    function getconditions($filename) {
        $conditions = array();

        $conditions['showall'] = TRUE;
        if (@$this->f['q']) {
            $conditions['showall'] = FALSE;
        }

        foreach (array ('q', 't', 'b', 'ci',) as $formvalue) {
            $conditions[$formvalue] = @$this->f[$formvalue];
        }
        foreach (array ('sd', 'sh', 'si', 'ed', 'eh', 'ei',) as $formvalue) {
            if ($conditions['showall'] and @$this->f[$formvalue]) {
                $conditions['showall'] = FALSE;
            }
            $conditions[$formvalue] = str_pad(@$this->f[$formvalue], 2, "0", STR_PAD_LEFT);
        }

        if ($conditions['q']) {
            $conditions['q'] = trim($conditions['q']);
            $conditions['keywords'] = preg_split("/\s+/", $conditions['q']);
            if (count($conditions['keywords']) > $this->c['MAXKEYWORDS']) {
                $this->prterror ('検索キーワードが多すぎます。');
            }
        }

        $conditions['savesw'] = $this->c['OLDLOGSAVESW'];

        return $conditions;
    }







    /**
     * 過去ログ検索結果表示
     *
     */
    function prtsearchresult() {

        $formf = array();
        if (is_array(@$this->f['f'])) {
            $formf = @$this->f['f'];
        }
        else {
            $formf[] = @$this->f['f'];
        }
        if (!@$this->c['MULTIPLESEARCH'] and count($formf) > 1) {
            array_splice($formf, 1);
        }
        $files = array();
        foreach ($formf as $filename) {
            if (preg_match("/^\d+\./", $filename) and is_file($this->c['OLDLOGFILEDIR'] . $filename)) {
                $files[] = $filename;
            }
        }

        $this->sethttpheader();
        $customstyle= "  .sq { color: #{$this->c['C_QUERY']}; }\n";
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' 過去ログ検索結果', '', $customstyle);
        $this->t->displayParsedTemplate('searchresult');

        foreach ($files as $filename) {
            $conditions = $this->getconditions($filename);
            $resultcode = $this->prtoldlog($filename, $conditions, FALSE);
        }

        print $this->prthtmlfoot ();

    }







    /**
     * 過去ログHTMLファイルダウンロード
     *
     */
    function prthtmldownload($filename) {

        if ($this->c['OLDLOGFMT']) {
            $oldlogext = 'dat';
        }
        else {
            $oldlogext = 'html';
        }

        # 不正なファイル名
        if (!preg_match("/^\d+\.$oldlogext$/", $filename)) {
            return 1;
        }
        else if (!is_file($this->c['OLDLOGFILEDIR'] . $filename)) {
            return 1;
        }

        $dlfilename = str_replace (".dat", ".html", $filename);

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=".$dlfilename);

        if ($this->c['OLDLOGFMT']) {
            $this->sethttpheader();
            print $this->prthtmlhead ($this->c['BBSTITLE'] . ' 過去ログ');
            $this->t->displayParsedTemplate('htmldownload');
        }

        $conditions = $this->getconditions($filename);
        $resultcode = $this->prtoldlog($filename, $conditions, TRUE);

        if ($this->c['OLDLOGFMT']) {
            print $this->prthtmlfoot ();
        }

    }







    /**
     * 各ファイル検索
     *
     */
    function prtoldlog($filename, $conditions = "", $isdownload = FALSE) {

        $dir = $this->c['OLDLOGFILEDIR'];

        if ($this->c['OLDLOGFMT']) {
            $oldlogext = 'dat';
        }
        else {
            $oldlogext = 'html';
        }

        # 不正なファイル名
        if (!preg_match("/^\d+\.$oldlogext$/", $filename)) {
            return 1;
        }
        else if (!is_file($dir . $filename)) {
            return 1;
        }

        $this->t->clearTemplate('oldlog_upper');
        $this->t->clearTemplate('oldlog_lower');
        $this->t->addVar('oldlog_upper', 'FILENAME', $filename);

        $fh = @fopen($dir . $filename, "rb");
        if (!$fh) {
            $this->t->addVar('oldlog_upper', 'success', 'false');
            $this->t->displayParsedTemplate('oldlog_upper');
            return 2;
        }
        flock ($fh, 1);

        $timerangestr = '';
        if (!(!$this->c['OLDLOGFMT'] and !$conditions)) {
            if (!@$conditions['showall']) {
                if (@$conditions['savesw']) {
                    if ($conditions['sd'] > 1 or $conditions['sh'] > 0 or $conditions['ed'] < 31 or $conditions['eh'] < 24) {
                        $timerangestr .= "{$conditions['sd']}日{$conditions['sd']}時 ～ {$conditions['ed']}日{$conditions['ed']}時　";
                    }
                }
                else {
                    if ($conditions['sh'] > 0 or $conditions['si'] > 0 or $conditions['eh'] < 24 or $conditions['ei'] > 0) {
                        $timerangestr .= "{$conditions['sh']}時{$conditions['si']}分 ～ {$conditions['eh']}時{$conditions['ei']}分　";
                    }
                }
            }
            $this->t->addVar('oldlog_upper', 'TIMERANGE', $timerangestr);
            $this->t->displayParsedTemplate('oldlog_upper');
        }


        $msgmode = 2;
        if (@$this->f['bt']) {
            $msgmode = 1;
        }
        $resultcount = 0;

        # dat検索
        if ($this->c['OLDLOGFMT']) {
            if (!@$conditions['showall']) {
                $result = 0;
                while (($logline = Func::fgetline($fh)) !== FALSE) {
                    $message = $this->getmessage($logline);
                    $result = $this->msgsearch($message, $conditions);
                    # 検索ヒット
                    if ($result == 1) {
                        $prtmessage = $this->prtmessage($message, $msgmode, $filename);
                        # 検索語のハイライト
                        if ($conditions['q']) {
                            $needle = "\Q{$conditions['q']}\E";
                            $quoteq = preg_quote($conditions['q'], "/");
                            if ($conditions['ci']) {
                                #$prtmessage = preg_replace("/($quoteq)/i", "<span class=\"sq\">$1</span>", $prtmessage);
                                #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", $prtmessage)) {
                                #  $prtmessage = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", "$1", $prtmessage, 1);
                                #}
                                $prtmessage = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/i", "$1<span class=\"sq\"><mark>$2</mark></span>", $prtmessage);
                            }
                            else {
                                #$prtmessage = str_replace($conditions['q'], "<span class=\"sq\">{$conditions['q']}</span>", $prtmessage);
                                #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", $prtmessage)) {
                                #  $prtmessage = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", "$1", $prtmessage, 1);
                                #}
                                $prtmessage = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/", "$1<span class=\"sq\"><mark>$2</mark></span>", $prtmessage);
                            }
                        }
                        print $prtmessage;
                        $resultcount++;
                    }
                    # 検索の終了
                    else if ($result == 2) {
                        break;
                    }
                }
            }
            # 全件表示
            else {
                while (($logline = Func::fgetline($fh)) !== FALSE) {
                    $messagestr = $this->prtmessage($this->getmessage($logline), $msgmode, $filename);
                    print $messagestr;
                }
            }
        }
        # HTML検索
        else {
            if (!$conditions['showall']) {
                # メッセージごとにファイル読み込みのバッファリングを行う
                $buffer = "";
                $flgbuffer = FALSE;
                $result = 0;
                while (($htmlline = Func::fgetline($fh)) !== FALSE) {
                    # メッセージの開始
                    if (!$flgbuffer and preg_match("/<div [^>]*id=\"m\d+\"[^>]*>/", $htmlline)) {
                        $buffer = $htmlline;
                        $flgbuffer = TRUE;
                    }
                    # メッセージの終了
                    else if ($flgbuffer and strpos($htmlline, "<!--  -->") !== FALSE) {
                        $buffer .= $htmlline;
                        {
                            $result = $this->msgsearchhtml($buffer, $conditions);
                            if ($result == 1) {
                                # 検索語のハイライト
                                if ($conditions['q']) {
                                    $needle = "\Q{$conditions['q']}\E";
                                    $quoteq = preg_quote($conditions['q'], "/");
                                    if ($conditions['ci']) {
                                        #$buffer = preg_replace("/($quoteq)/i", "<span class=\"sq\">$1</span>", $buffer);
                                        #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", $buffer)) {
                                        #  $buffer = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/i", "$1", $buffer, 1);
                                        #}
                                        $buffer = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/i", "$1<span class=\"sq\"><mark>$2</mark></span>", $buffer);
                                    }
                                    else {
                                        #$buffer = str_replace($conditions['q'], "<span class=\"sq\">{$conditions['q']}</span>", $buffer);
                                        #while (preg_match("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", $buffer)) {
                                        #  $buffer = preg_replace("/(<[^<>]*)<span class=\"sq\">$quoteq<\/span>/", "$1", $buffer, 1);
                                        #}
                                        $buffer = preg_replace("/((?:\G|>)[^<]*?)($quoteq)/", "$1<span class=\"sq\"><mark>$2</mark></span>", $buffer);
                                    }
                                }
                                print $buffer;
                                $resultcount++;
                            }
                            else if ($result == 2) {
                                break;
                            }
                        }
                        $buffer = "";
                        $flgbuffer = FALSE;
                    }
                    # メッセージ中
                    else if ($flgbuffer) {
                        $buffer .= $htmlline;
                    }
                    # メッセージ以外
                    else {
                    }
                }
            }
            else {
                while (($htmlline = Func::fgetline($fh)) !== FALSE) {
                    print $htmlline;
                }
            }
        }
        flock ($fh, 3);
        fclose ($fh);

        if (!(!$this->c['OLDLOGFMT'] and !$conditions)) {
            $resultmsg = '';
            if (!$conditions['showall']) {
                #$resultmsg = "{$filename}：&nbsp;{$timerangestr}&nbsp;";
                if (@$conditions['q'] != '') {
                    $value = $conditions['q'];
                    #$value_euc = JcodeConvert($value, 2, 1);
                    #$value_euc = htmlentities($value_euc, ENT_QUOTES, 'EUC-JP');
                    #$value = JcodeConvert($value_euc, 1, 2);
                    $value = htmlentities($value, ENT_QUOTES);
                    $resultmsg .= '「' . $value . '」は';
                }
                if ($resultcount > 0) {
                    $resultmsg .= $resultcount . '件見つかりました。';
                }
                else {
                    $resultmsg .= '見つかりませんでした。';
                }
                #print $resultmsg;
                $this->t->addVar('oldlog_lower', 'RESULTMSG', $resultmsg);
                $this->t->displayParsedTemplate('oldlog_lower');
            }
        }

    }












    /**
     * １件メッセージ検索（HTML形式）
     */
    function msgsearchhtml ($buffer, $conditions) {
        $message = array();

        $message['USER'] = '';
        $message['TITLE'] = '';
        $message['MSG'] = '';
        $message['NDATESTR'] = '';

        if (preg_match("/<span class=\"mun\">([^<]+)<\/span>/", $buffer, $matches)) {
            $message['USER'] = $matches[1];
        }
        if (preg_match("/<span class=\"ms\">([^<]+)<\/span>/", $buffer, $matches)) {
            $message['TITLE'] = $matches[1];
        }
        if (preg_match("/<blockquote>[\r\n\s]*<pre>(.+?)<\/pre>/ms", $buffer, $matches)) {
            $message['MSG'] = $matches[1];
        }
        if (preg_match("/<span class=\"md\">[^<]*投稿日：(\d+)\/(\d+)\/(\d+)[^\d]+(\d+)時(\d+)分(\d+)秒/", $buffer, $matches)) {
            if (@$conditions['savesw']) {
                $message['NDATESTR'] = $matches[3] . $matches[4];
            }
            else {
                $message['NDATESTR'] = $matches[4] . $matches[5];
            }
        }

        return $this->msgsearch ($message, $conditions);
    }



    /**
     * １件メッセージ検索（dat形式）
     * 返り値 0:ヒットなし 1:ヒット 2:検索終了信号
     */
    function msgsearch ($message, $conditions) {

        if (!$message) {
            return 0;
        }

        # 月毎
        if (@$conditions['savesw']) {
            $starttime = $conditions['sd'].$conditions['sh'];
            $endtime = $conditions['ed'].$conditions['eh'];
            if (!@$message['NDATESTR']) {
                $message['NDATESTR'] = date("dH", $message['NDATE']);
            }
        }
        # 日毎
        else {
            $starttime = $conditions['sh'].$conditions['si'];
            $endtime = $conditions['eh'].$conditions['ei'];
            if (!@$message['NDATESTR']) {
                $message['NDATESTR'] = date("Hi", $message['NDATE']);
            }
        }
        if ($message['NDATESTR'] < $starttime or $message['NDATESTR'] > $endtime) {
            return 2;
        }

        $hit = FALSE;

        # キーワード検索
        if (@$conditions['keywords']) {

            $haystack = '';
            if ($conditions['t'] == 'u') {
                $haystack = $message['USER'];
            }
            else if ($conditions['t'] == 't') {
                $haystack = $message['TITLE'];
            }
            else {
                $haystack = "{$message['USER']}<>{$message['TITLE']}<>{$message['MSG']}";
            }

            # OR検索
            if ($conditions['b'] == 'o') {
                $hit = FALSE;
                foreach ($conditions['keywords'] as $needle) {
                    if ($conditions['ci']) {
                        $result = stristr ($haystack, $needle);
                    }
                    else {
                        $result = strpos ($haystack, $needle);
                    }
                    if ($result !== FALSE) {
                        $hit = TRUE;
                        break;
                    }
                }
            }
            # AND検索
            else {
                $hit = TRUE;
                foreach ($conditions['keywords'] as $needle) {
                    if ($conditions['ci']) {
                        $result = stristr ($haystack, $needle);
                    }
                    else {
                        $result = strpos ($haystack, $needle);
                    }
                    if ($result === FALSE) {
                        $hit = FALSE;
                        break;
                    }
                }
            }
        }
        else {
            $hit = TRUE;
        }

        if ($hit) {
            return 1;
        }
        else {
            return 0;
        }

    }




    /**
     * トピック一覧の表示
     */
    function prttopiclist($filename) {

        # 不正なファイル名
        if (!preg_match("/^\d+\.dat$/", $filename)) {
            return 1;
        }
        else if (!is_file($this->c['OLDLOGFILEDIR'] . $filename)) {
            return 1;
        }

        $fh = @fopen($this->c['OLDLOGFILEDIR'] . $filename, "rb");
        if (!$fh) {
            $this->prterror($filename . ' を開けませんでした。');
        }
        flock ($fh, 1);

        $tid = array();
        $tcount = array();
        $ttitle = array();
        $ttime = array();
        $tindex = 0;
        while (($logline = Func::fgetline($fh)) !== FALSE) {
            $message = $this->getmessage($logline);
            if (!$message['THREAD'] or $message['THREAD'] == $message['POSTID'] or !@$ttitle[$message['THREAD']]) {
                $tid[$tindex] = $message['POSTID'];
                $tcount[$message['POSTID']] = 0;

                $msg = ltrim($message['MSG']);
                $msg = preg_replace("/<a href=[^>]+>参考：[^<]+<\/a>/i", "", $msg, 1);
                $msg = preg_replace("/<[^>]+>/", "", $msg);
                $msgsplit = explode("\r", $msg);
                $msgdigest = $msgsplit[0];
                $index = 1;
                while ($index < count($msgsplit) - 1 and strlen($msgdigest . $msgsplit[$index]) < 50) {
                    $msgdigest .= $msgsplit[$index];
                    $index++;
                }
                $ttitle[$message['POSTID']] = $msgdigest;

                if (strpos($ttitle[$message['POSTID']], "\r") !== FALSE) {
                    $ttitle[$message['POSTID']] = substr($ttitle[$message['POSTID']],
                    0, strpos($ttitle[$message['POSTID']], "\r"));
                }

                $ttime[$message['POSTID']] = $message['NDATE'];
                $tindex++;
            }
            else {
                $tcount[$message['THREAD']]++;
                $ttime[$message['THREAD']] = $message['NDATE'];
            }
        }
        flock ($fh, 3);
        fclose ($fh);

        $this->t->addVar('topiclist', 'FILENAME', $filename);

        $tidcount = count($tid);
        $i = 0;
        while ($i < $tidcount) {
            if ($tid[$i]) {
                $tc = sprintf ("%02d", $tcount[$tid[$i]]);
                $tt = date ("m/d H:i:s", $ttime[$tid[$i]]);
                $this->t->addVars('topic', array(
                    'TID' => $tid[$i],
                    'TC' => $tc,
                    'TT' => $tt,
                    'TTITLE' => $ttitle[$tid[$i]],
                    'FILENAME' => $filename,
                ));
                $this->t->parseTemplate('topic', 'a');
            }
            $i++;
        }

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' トピック一覧 ' . $filename);
        $this->t->displayParsedTemplate('topiclist');
        print $this->prthtmlfoot ();

    }





    /**
     * Zipアーカイブ一覧画面表示
     *
     */
    function prtarchivelist() {

        $dir = $this->c['ZIPDIR'];

        $dh = opendir($dir);
        if (!$dh) {
            $this->prterror ('ディレクトリが開けませんでした');
        }
        $files = array();
        while ($entry = readdir($dh)) {
            if (is_file($dir . $entry) and preg_match("/\.(zip|lzh|rar|gz|tar\.gz)$/i", $entry)) {
                $files[] = $entry;
            }
        }
        closedir ($dh);

        # ファイル名の自然順でソート
        natsort($files);

        foreach ($files as $filename) {
            $fstat = stat ($dir . $filename);
            $fsize = $fstat[7];
            $ftime = date("Y/m/d H:i:s", $fstat[9]);

            $this->t->setAttribute('archive', 'visibility', 'visible');
            $this->t->addVars('archive', array(
                'DIR' => $dir,
                'FILENAME' => $filename,
                'FTIME' => $ftime,
                'FSIZE' => $fsize,
            ));
            $this->t->parseTemplate('archive', 'a');
        }

        $this->sethttpheader();
        print $this->prthtmlhead ($this->c['BBSTITLE'] . ' 過去ログアーカイブ');
        $this->t->displayParsedTemplate('archivelist');
        print $this->prthtmlfoot ();

    }




    /**
     * ダウンロード機能使用可否チェック
     */
    function dlchk() {

        if (!@$_SERVER['HTTP_USER_AGENT']) {
            return TRUE;
        }
        if (preg_match ("/^Mozilla\/(\S+)\s(.+)/", @$_SERVER['HTTP_USER_AGENT'], $matches)) {
            $ver = $matches[1];
            $uos = $matches[2];
            $isie = 0;
            if (preg_match ("/MSIE (\S)/", $uos, $matches)) {
                $isie = 1;
                $iever = $matches[1];
            }
            $ismac = 0;
            if (preg_match ("/Mac/", $uos, $matches)) {
                $ismac = 1;
            }
            if ((@$ver >= 4 and !@$isie) or (@$ver >= 4 and @$isie and @$iever >= 5 and !@$ismac)) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
        return TRUE;
    }










}


?>
