<?php

/*

KuzuhaScriptPHP ver0.0.7alpha (13:04 2003/02/18)
BBS with image upload function module

* Todo

* Memo


*/

if(!defined("INCLUDED_FROM_BBS")) {
    header ("Location: ../../bbs.php");
    exit();
}


/*
 * Module-specific settings
 *
 * They will be added to/overwritten by $CONF.
 */
$GLOBALS['CONF_IMAGEBBS'] = array(

    # Image upload directory (please set it to be writable)
    'UPLOADDIR' => './upload/',

    # File containing latest image upload file number (please set it to be writable)
    'UPLOADIDFILE' => './upload/id.txt',

    # If this string is present in the post content, the uploaded image will be inserted into that position
    'IMAGETEXT' => '%image',

    # Total space dedicated to uploaded images (KB)
    'MAX_UPLOADSPACE' => 10000,

    # Maximum width for uploaded images
    'MAX_IMAGEWIDTH' => 1280,

    # Maximum height for uploaded images
    'MAX_IMAGEHEIGHT' => 1600,

    # Maximum file size for uploaded images (KB)
    'MAX_IMAGESIZE' => 200,

    # Image scale factor when displayed on the bulletin board (％)
    'IMAGE_PREVIEW_RESIZE' => 100,

);




// Include file path


/* Launch */
{
    if (!ini_get('file_uploads')) {
        print 'Error: The file upload feature is not allowed.';
        exit();
    }
    if (!function_exists('GetImageSize')) {
        print 'Error: The image processing feature is not supported.';
        exit();
    }
}




/**
 * BBS with image upload function module
 *
 *
 *
 * @package strangeworld.cnscript
 * @access  public
 */
class Imagebbs extends Bbs {

    /**
     * Constructor
     *
     */
    function __construct() {
        $GLOBALS['CONF'] = array_merge ($GLOBALS['CONF'], $GLOBALS['CONF_IMAGEBBS']);
        parent::__construct();
    }





    /**
     * Reflect personal settings
     */
    function refcustom() {
        $this->c['SHOWIMG'] = 1;

        parent::refcustom();
    }





    /**
     * Display form section
     *
     * @access  public
     * @param   String  $dtitle     Initial value of the title form
     * @param   String  $dmsg       Initial value of the contents form
     * @param   String  $dlink      Initial value of the link form
     * @return  String  Form HTML data
     */
    function setform($dtitle, $dmsg, $dlink, $mode = '') {
        if ($this->c['SHOWIMG']) $this->t->addVar('sicheck', 'CHK_SI', ' checked="checked"');
        $this->t->addVar('postform', 'MAX_FILE_SIZE', $this->c['MAX_IMAGESIZE'] * 1024);
        $this->t->addVar('postform', 'mode', 'image');
        $this->t->setAttribute('sicheck', 'visibility', 'visible');
        return parent::setform($dtitle, $dmsg, $dlink, $mode);
    }





    /**
     * Get message from form input
     *
     * @access  public
     * @return  Array  Message array
     */
    function getformmessage() {

        $message = parent::getformmessage();

        if (!is_array($message)) {
            return $message;
        }

        # Confirm file upload
        if ($_FILES['file']['name']) {

            if ($_FILES['file']['error'] == 2
            or (file_exists($_FILES['file']['tmp_name'])
            and filesize($_FILES['file']['tmp_name']) > ($this->c['MAX_IMAGESIZE'] * 1024))) {
                $this->prterror( 'The file size is over ' .$this->c['MAX_IMAGESIZE'] .'KB.');
            }

            if ($_FILES['file']['error'] > 0
            or !is_uploaded_file($_FILES['file']['tmp_name'])) {
                $this->prterror( 'File upload process failed. Code: ' . $_FILES['file']['error']);
            }

            # Locking the image upload process
            #20260717 Gikoneko: auto-create the upload-id file on first run
            if (!file_exists($this->c['UPLOADIDFILE']) and $this->ensurefile($this->c['UPLOADIDFILE'], '0')) {
                $this->prtfilecreated(array("{$this->c['UPLOADIDFILE']} was not found, so it has been newly created."));
            }
            $fh = @fopen($this->c['UPLOADIDFILE'], "rb+");
            if (!$fh) {
                $this->prterror ( 'Failed to load the uploaded image file.' );
            }
            flock ($fh, 2);

            # Obtain file ID
            $fileid = trim(fgets ($fh, 10));
            if (!$fileid) {
                $fileid = 0;
            }

            # File type check
            $imageinfo = GetImageSize($_FILES['file']['tmp_name']);
            if ($imageinfo[0] > $this->c['MAX_IMAGEWIDTH'] or $imageinfo[1] > $this->c['MAX_IMAGEHEIGHT']) {
                unlink ($_FILES['file']['tmp_name']);
                $this->prterror ( 'The width of the image exceeds the limit.' );
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
                $this->prterror ('The file format is incorrect.');
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

            # Embedding tags in messages.
            if (strpos($message['MSG'], $this->c['IMAGETEXT']) !== FALSE) {
                $message['MSG'] = preg_replace("/\Q{$this->c['IMAGETEXT']}\E/", $message['FILETAG'], $message['MSG'], 1);
                $message['MSG'] = preg_replace("/\Q{$this->c['IMAGETEXT']}\E/", '', $message['MSG']);
            }
            else {
                if (preg_match("/\r\r<a href=[^<]+>Reference: [^<]+<\/a>$/", $message['MSG'])) {
                    $message['MSG'] = preg_replace("/(\r\r<a href=[^<]+>Reference: [^<]+<\/a>)$/", "\r\r{$message['FILETAG']}$1", $message['MSG'], 1);
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
     * Message registration process
     *
     * @access  public
     * @return  Integer  Error code
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

            # Delete old images
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
