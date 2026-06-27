<?php

/*

くずはすくりぷとPHP ver0.0.7alpha (13:04 2003/02/18)
画像アップロード機能つきBBSモジュール

* Todo

* Memo


*/

if(!defined("INCLUDED_FROM_BBS")) {
    header ("Location: ../../bbs.php");
    exit();
}


/*
 * モジュール固有設定
 *
 * $CONFに追加・上書きされます。
 */
$GLOBALS['CONF_IMAGEBBS'] = array(

    # 画像アップロードディレクトリ（書換可に設定してください）
    'UPLOADDIR' => './upload/',

    # 画像アップロード用最新ファイル番号記録ファイル（書換可に設定してください）
    'UPLOADIDFILE' => './upload/id.txt',

    # 投稿内容にこの文字列があるとその位置にアップロード画像が挿入されます
    'IMAGETEXT' => '%image',

    # 保存する画像の総容量(KB)
    'MAX_UPLOADSPACE' => 10000,

    # アップロードする画像の横幅最大値
    'MAX_IMAGEWIDTH' => 1280,

    # アップロードする画像の縦幅最大値
    'MAX_IMAGEHEIGHT' => 1600,

    # アップロードする画像サイズの最大値(KB)
    'MAX_IMAGESIZE' => 200,

    # 掲示板に表示する際の画像縮尺率(％)
    'IMAGE_PREVIEW_RESIZE' => 100,

);




// インクルードファイルパス


/* 起動 */
{
    if (!ini_get('file_uploads')) {
        print 'エラー：ファイルアップロード機能が許可されていません。';
        exit();
    }
    if (!function_exists('GetImageSize')) {
        print 'エラー：画像処理機能がサポートされていません。';
        exit();
    }
}




/**
 * 画像アップロード機能つきBBSモジュール
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Imagebbs extends Bbs {

    /**
     * コンストラクタ
     *
     */
    function __construct() {
        $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_IMAGEBBS']);
        parent::__construct();
    }





    /**
     * 個人用設定反映
     */
    function refcustom() {
        $this->c['SHOWIMG'] = 1;

        parent::refcustom();
    }





    /**
     * フォーム部分表示
     *
     * @access  public
     * @param   String  $dtitle     題名のフォーム初期値
     * @param   String  $dmsg       内容のフォーム初期値
     * @param   String  $dlink      リンクのフォーム初期値
     * @return  String  フォームのHTMLデータ
     */
    function setform($dtitle, $dmsg, $dlink, $mode = '') {
        if ($this->c['SHOWIMG']) $this->t->addVar('sicheck', 'CHK_SI', ' checked="checked"');
        $this->t->addVar('postform', 'MAX_FILE_SIZE', $this->c['MAX_IMAGESIZE'] * 1024);
        $this->t->addVar('postform', 'mode', 'image');
        $this->t->setAttribute('sicheck', 'visibility', 'visible');
        return parent::setform($dtitle, $dmsg, $dlink, $mode);
    }





    /**
     * フォーム入力からのメッセージ取得
     *
     * @access  public
     * @return  Array  メッセージ配列
     */
    function getformmessage() {

        $message = parent::getformmessage();

        if (!is_array($message)) {
            return $message;
        }

        # アップロードファイルの確認
        if ($_FILES['file']['name']) {

            if ($_FILES['file']['error'] == 2
            or (file_exists($_FILES['file']['tmp_name'])
            and filesize($_FILES['file']['tmp_name']) > ($this->c['MAX_IMAGESIZE'] * 1024))) {
                $this->prterror( 'ファイルサイズが' .$this->c['MAX_IMAGESIZE'] .'KBを超えています。');
            }

            if ($_FILES['file']['error'] > 0
            or !is_uploaded_file($_FILES['file']['tmp_name'])) {
                $this->prterror( 'ファイルアップロードの処理に失敗しました。コード:' . $_FILES['file']['error']);
            }

            # 画像アップロードプロセスのロック
            $fh = @fopen($this->c['UPLOADIDFILE'], "rb+");
            if (!$fh) {
                $this->prterror ( 'アップロード記録ファイルの読み込みに失敗しました' );
            }
            flock ($fh, 2);

            # ファイルIDの獲得
            $fileid = trim(fgets ($fh, 10));
            if (!$fileid) {
                $fileid = 0;
            }

            # ファイルの種類チェック
            $imageinfo = GetImageSize($_FILES['file']['tmp_name']);
            if ($imageinfo[0] > $this->c['MAX_IMAGEWIDTH'] or $imageinfo[1] > $this->c['MAX_IMAGEHEIGHT']) {
                unlink ($_FILES['file']['tmp_name']);
                $this->prterror ( '画像の幅が許可量を超えています。' );
            }

            # GIF
            if ($imageinfo[2] == 1) {
                $filetype = 'GIF';
                $fileext = '.gif';
            }
            # JPG
            else if ($imageinfo[2] == 2) {
                $filetype = 'JPG';
                $fileext = '.jpg';
            }
            # PNG
            else if ($imageinfo[2] == 3) {
                $filetype = 'PNG';
                $fileext = '.png';
            }
            else {
                unlink ($_FILES['file']['tmp_name']);
                $this->prterror ('ファイルの形式が正しくありません。');
            }

            $fileid++;
            $filename = $this->c['UPLOADDIR'] . str_pad($fileid, 5, "0", STR_PAD_LEFT) . '_' . date("YmdHis", CURRENT_TIME) . $fileext;

            copy ($_FILES['file']['tmp_name'], $filename);
            unlink ($_FILES['file']['tmp_name']);

            $message['FILEID'] = $fileid;
            $message['FILENAME'] = $filename;
            $message['FILEMSG'] = '画像'.str_pad($fileid, 5, "0", STR_PAD_LEFT)." $filetype {$imageinfo[0]}*{$imageinfo[1]} ".floor(filesize($filename)/1024)."KB";
            $message['FILETAG'] = "<a href=\"{$filename}\" target=\"link\">"
            . "<img src=\"{$filename}\" width=\"{$imageinfo[0]}\" height=\"{$imageinfo[1]}\" border=\"0\" alt=\"{$message['FILEMSG']}\" /></a>";

            # メッセージへのタグ埋め込み
            if (strpos($message['MSG'], $this->c['IMAGETEXT']) !== FALSE) {
                $message['MSG'] = preg_replace("/\Q{$this->c['IMAGETEXT']}\E/", $message['FILETAG'], $message['MSG'], 1);
                $message['MSG'] = preg_replace("/\Q{$this->c['IMAGETEXT']}\E/", '', $message['MSG']);
            }
            else {
                if (preg_match("/\r\r<a href=[^<]+>参考：[^<]+<\/a>$/", $message['MSG'])) {
                    $message['MSG'] = preg_replace("/(\r\r<a href=[^<]+>参考：[^<]+<\/a>)$/", "\r\r{$message['FILETAG']}$1", $message['MSG'], 1);
                }
                else {
                    $message['MSG'] .= "\r\r" . $message['FILETAG'];
                }
            }

            fseek ($fh, 0, 0);
            ftruncate ($fh, 0);
            fwrite ($fh, $fileid);
            flock ($fh, 3);
            fclose ($fh);
        }

        return $message;

    }





    /**
     * メッセージ登録処理
     *
     * @access  public
     * @return  Integer  エラーコード
     */
    function putmessage($message) {

        $posterr = parent::putmessage($message);

        if ($posterr) {
            return $posterr;
        }
        else {

            $dirspace = 0;
            $maxspace = $this->c['MAX_UPLOADSPACE'] * 1024;

            $files = array();
            $dh = opendir($this->c['UPLOADDIR']);
            if (!$dh) {
                return;
            }
            while ($entry = readdir($dh)) {
                if (is_file($this->c['UPLOADDIR'] . $entry) and preg_match("/\.(gif|jpg|png)$/i", $entry)) {
                    $files[] = $this->c['UPLOADDIR'] . $entry;
                    $dirspace += filesize($this->c['UPLOADDIR'] . $entry);
                }
            }
            closedir ($dh);

            # 古い画像の削除
            if ($dirspace > $maxspace) {
                sort($files);
                foreach ($files as $filepath) {
                    $dirspace -= filesize($filepath);
                    unlink ($filepath);
                    if ($dirspace <= $maxspace) {
                        break;
                    }
                }
            }
        }

    }

}

?>
