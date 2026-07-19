<?php

// このスクリプトの名前
$mycginame = 'gikonekoadd.php';

// スクリプト自身の設置場所（conf.php・language/はここ基準）
$script_root = __DIR__;

// conf.phpを読み込む（本スクリプトは単体で呼び出されるためbbs.php本体は
// requireせず、conf.phpのみ読み込んで軽量にロードする）。
require_once( $script_root . '/conf.php' );

// データファイル名（conf.phpのGIKONEKO_KOTOBA_FILEを使用。未設定の場合のみ
// 環境変数／デフォルトの旧来の決め方にフォールバックする）。
$data = $CONF['GIKONEKO_KOTOBA_FILE'] ?? ( ( getenv( 'GIKO_DATA_DIR' ) ?: $script_root . '/data' ) . '/gikoneko_kotoba.dat' );
$giko_dir = dirname( $data );

// 通常はbbs.php側のMigration Engineがdata/を作成済みだが、本スクリプトが
// bbs.phpより先に単体で呼ばれた場合に備え、無ければここでも作成する。
if ( ! is_dir( $giko_dir ) ) {
	@mkdir( $giko_dir, 0755, true );
}

// ことばのmax値
$maxword = 128; # 128なら全角で64文字

// UI文言（$MSG）の読み込み。bbs.phpと同じlanguage/{LANGUAGE_FILE}.txtを参照する。
$language_file_name = $CONF['LANGUAGE_FILE'] ?? 'english';
$langfile = $script_root . '/language/' . $language_file_name . '.txt';
$MSG = array();
if ( file_exists( $langfile ) ) {
	$lines = @file( $langfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	if ( $lines !== false ) {
		foreach ( $lines as $line ) {
			$trimmed = ltrim( $line );
			if ( $trimmed === '' || $trimmed[0] === '#' || $trimmed[0] === ';' ) {
				continue;
			}
			$pos = strpos( $line, '=' );
			if ( $pos === false ) {
				continue;
			}
			$MSG[ trim( substr( $line, 0, $pos ) ) ] = substr( $line, $pos + 1 );
		}
	}
}
function T( string $key ): string {
	return $GLOBALS['MSG'][ $key ] ?? $key;
}

$title = T( 'GIKO_ADD_TITLE' );

function giko_prterror( string $msg, string $title ): never {
	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<body bgcolor=\"004040\" text=\"ffffff\">\n";
	echo "<h3>{$msg}</h3>\n";
	echo "</body></html>\n";
	exit;
}

header( 'Content-type: text/html; charset=UTF-8' );

$mode = trim( (string) ( $_POST['mode'] ?? $_GET['mode'] ?? '' ) );
$text = trim( (string) ( $_POST['text'] ?? $_GET['text'] ?? '' ) );
$text = str_replace( array( "\n", "\r" ), '', $text );
$text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );

if ( $mode === 'add' && $text !== '' ) {

	if ( strlen( $text ) > $maxword * 3 ) {
		giko_prterror( T( 'GIKO_ERR_TOO_LONG' ), $title );
	}

	$fortunedata = file_exists( $data ) ? file( $data, FILE_IGNORE_NEW_LINES ) : array();

	if ( $fortunedata !== false && in_array( $text, $fortunedata, true ) ) {
		giko_prterror( T( 'GIKO_ERR_DUPLICATE' ), $title );
	}

	// データファイルが改行で終わっていない場合に備える。
	$prefix = '';
	if ( file_exists( $data ) && filesize( $data ) > 0 ) {
		$fp = @fopen( $data, 'rb' );
		if ( $fp !== false ) {
			fseek( $fp, -1, SEEK_END );
			if ( fread( $fp, 1 ) !== "\n" ) {
				$prefix = "\n";
			}
			fclose( $fp );
		}
	}

	$result = @file_put_contents( $data, $prefix . $text . "\n", FILE_APPEND | LOCK_EX );

	if ( $result === false ) {
		giko_prterror( sprintf( T( 'GIKO_ERR_WRITE_FAILED' ), $data ), $title );
	}

	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<BODY bgcolor=\"#004040\" text=\"#ffffff\" link=\"#eeffee\" vlink=\"#dddddd\" alink=\"#ff0000\">\n";
	echo "<h1><a href=\"{$mycginame}\">" . T( 'GIKO_POST_COMPLETE' ) . "</a><p></h1><a href=\"./bbs.php\">" . T( 'GIKO_BACK_TO_BBS' ) . "</a>\n";
	echo "</body></html>\n";

} else {

	echo "<html><head><title>{$title}</title><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>\n";
	echo "<BODY bgcolor=\"#004040\" text=\"#ffffff\" link=\"#eeffee\" vlink=\"#dddddd\" alink=\"#ff0000\"><center>\n";
	echo "<p><font size=\"+2\"><B>{$title}</B></font><br>\n";
	echo "<P>" . sprintf( T( 'GIKO_ADD_PROMPT' ), $maxword ) . "</p>\n";
	echo "<form method=\"post\" action=\"{$mycginame}\">\n";
	echo "<input type=\"hidden\" name=\"mode\" value=\"add\">\n";
	echo "<input type=\"text\" name=\"text\" size=\"30\" maxlength=\"{$maxword}\">\n";
	echo "<input type=\"submit\" value=\"" . T( 'GIKO_ADD_SUBMIT' ) . "\" accesskey=\"R\">\n";
	echo " <INPUT type=\"reset\" value=\"" . T( 'GIKO_ADD_RESET' ) . "\">\n";
	echo "</form><p><a href=\"./bbs.php\" >" . T( 'GIKO_BACK_TO_BBS' ) . "</a></center></body></html>\n";

}
