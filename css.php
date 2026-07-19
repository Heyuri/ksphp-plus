<?php
/**
 * css.php
 *
 * template.html の<style>内にあった、掲示板全体の共通CSSを外部化した
 * ファイル。<link rel="stylesheet" href="css.php?c={C}"> の形で読み込む。
 *
 * 文字色・背景色・リンク色等、個人用環境設定画面で変更できる配色
 * （8項目）は、リクエストごとに変わりうるため、静的な.cssファイルには
 * 出来ない。bbs.phpのWebapp::refcustom()と同じ「?c=」パラメータの
 * デコード方式をここでも使い、動的にCSSへ差し込む。
 *
 * ※{CUSTOMSTYLE}（ページ固有の追加スタイル。bbslog.php・bbstree.php等が
 * 個別に注入する）は対象外。従来通りsub/template.html内の小さな
 * <style>{CUSTOMSTYLE}</style>ブロックとして残る。
 *
 * This file externalizes the shared CSS that used to live inside
 * template.html's <style> block, loaded via
 * <link rel="stylesheet" href="css.php?c={C}">.
 *
 * The 8 user-customizable colors (text, background, link colors, etc.,
 * changeable from the personal settings page) can differ per request, so
 * they can't live in a static .css file. This file re-implements the same
 * "?c=" parameter decoding used by Webapp::refcustom() in bbs.php, and
 * injects the resulting values into the CSS dynamically.
 *
 * Note: {CUSTOMSTYLE} (page-specific extra styles injected individually
 * by bbslog.php, bbstree.php, etc.) is out of scope here and remains a
 * small inline <style>{CUSTOMSTYLE}</style> block in sub/template.html,
 * same as before.
 *
 * @package strangeworld.cnscript
 */

require_once("./conf.php");

/**
 * Decoding base64 strings into 6-character hexadecimal.
 * Kept in sync with Func::base64_threebytehex() in bbs.php -- if that
 * encoding ever changes, update both.
 *
 * @param   String  $str  4-character base64 string
 * @return  String  6-character hexadecimal string
 */
function css_base64_threebytehex($str) {
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

// User-customizable colors (order must match the settings string format
// produced by the personal settings page -- same order as Webapp::refcustom()
// in bbs.php's $colors array).
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

$formc = isset($_GET['c']) ? $_GET['c'] : '';
if ($formc && strlen($formc) > 5) {
    $formclen = strlen($formc);
    $currentpos = 2; // first 2 characters are flag bits, not colors
    foreach ($colors as $confname) {
        $colorval = css_base64_threebytehex(substr($formc, $currentpos, 4));
        if (strlen($colorval) == 6) {
            $CONF[$confname] = $colorval;
        }
        $currentpos += 4;
        if ($currentpos > $formclen) {
            break;
        }
    }
}

header('Content-Type: text/css; charset=UTF-8');
// 色設定はURLの?cパラメータ（=個人設定）に依存するため、URLごとに
// キャッシュされる形で問題ない。
// Since the colors depend on the ?c parameter (personal settings), it's
// fine for caching to be keyed per distinct URL.
header('Cache-Control: public, max-age=3600');

$css = <<<'CSS'
    html { scroll-behavior: smooth; }
    body { max-height: 100%; -webkit-text-size-adjust: 100%; background: #{C_BACKGROUND}; color: #{C_TEXT};font-family: "BIZ UDゴシック", "Noto Sans Mono CJK JP", "Noto Sans Mono", "IPAゴシック", "HGゴシックM", "ＭＳ ゴシック", "MS Gothic", monospace; font-size: 14px; }
    
    .postlists {
        max-width: 100%;
        margin: 0 auto;
        border-spacing: 1px;
    }

    .postlists th {
        background-color: #007f7f;
        color: #ffffff;
    }

    .postlists th a {
        color: #ffffff;
    }

    .postlists tbody tr:nth-child(odd) {
        background-color: #003434;
    }

    .postlists tbody tr:nth-child(even) {
        background-color: #004848;
    }

    .postlists th,
    .postlists td {
        padding: 0.125em;
    }

    .deletionTableCellWrapper {
        white-space: normal;
        word-break: break-word;
    }

    .centerItem {
        text-align: center;
    }
    /* Links */
    a:link { color: #{C_A_COLOR}; transition:0.2s; }
    a:visited { color: #{C_A_VISITED}; }
    a:active { color: #{C_A_ACTIVE}; }
    a:hover { color: #{C_A_HOVER}; }
    a.help { text-decoration-line: underline; }
    .deadLink, .deadLink:link, .deadLink:visited, .deadLink:hover, .deadLink:active { opacity: 0.6; text-decoration: line-through; }
    /* Horizontal lines */
    hr { border: 0 none; height: 2px; background-color: #c0c0c0; color: #c0c0c0; }

	fieldset { border: 2px #c0c0c0 solid; border-radius: 2px;}
    /* Mobile compatibility */
    @media screen and (min-width: 0px){
        .msgnormal { font-size: 0.9rem; font-size: 14px; line-height:1; /* white-space: pre-wrap; */ word-wrap: break-word; }
        .msgtree { font-size: 0.9rem; font-size: 14px; line-height:1; white-space: pre; }
        div.contents { margin-left: 18px; }
    }
    @media screen and (min-width: 640px){
        .msgnormal { font-size: 1.0rem; font-size: 16px; }
        .msgtree { font-size: 1.0rem; font-size: 16px; }
        div.contents { margin-left: 27px; }
    }
    iframe { max-width: 100%; }
    img { max-width: 99%; height: auto; }
    td { white-space: nowrap; }
    fieldset { display: inline-block; margin: 5px 0; padding: 10px; max-width: 100%; }
    /* Bulletin board title */
    .pagetitle { font-size: large; font-weight: bold; text-align: left; }
    /* Upper link row */
    .link_upper { font-size: 14px; font-size: 0.9rem; }
    /* Lower link row */
    .link_row { font-size: 13px; font-size: 0.8rem; }
    /* Message */
    .bbsmsg { font-size: 16px; font-size: 1.0rem; }
    /* BUTTANS */
    .kaomoji {font-family: inherit;}
    /* Write/erase completion message */
    .msg-completed { font-size: xx-large; font-weight: bold; }
    /* Small */
    .small { font-size: small; }
    /* There are no posts below this point */
    .msgmore { font-size: 15px; font-size: 0.95rem; font-style: italic; }
    /* Copyright notice */
    .copyright { font-size: 13px; font-size: 0.8rem; text-align: right;}
    /* Title */
    .ms { color: #{C_SUBJ}; font-size: 17px; font-size: 1.05rem; font-weight: bold; }
    /* Post number */
    .mnum { font-size: 0.8rem; font-size: 12px; color : #{C_TEXT}; display:none; }
    /* User label */
    .mu { font-size: 14px; font-size: 0.9rem; }
    /* Username */
    .mun { font-size: 14px; font-size: 0.9rem; font-weight: normal;}
    /* Fixed handle */
    .muh { font-size: 15px; font-size: 0.95rem; font-weight: bold; font-style: italic; }
    /* Trip */
    .mut { font-size: 15px; font-size: 0.95rem; font-weight: bold; font-style: italic;}
    /* Post */
    .md { font-size: 14px; font-size: 0.9rem; }
    /* Post buttons */
    .nb,a.help { font-size: 15px; font-size: 0.95rem; white-space: normal; }
    /* Post form explanation */
    .pfhelp { font-size: 13px; font-size: 0.8rem; font-style: italic; }
    /* 2026-07-17：投稿者・メール・題名ラベルの手動スペース調整（英語の
       文字幅専用に調整されていた）を廃止し、言語に依存しない固定幅の
       インラインブロックに変更。どの言語のラベル文字列でも入力欄の
       開始位置が揃うようにする。
       2026-07-17: Replaced the old manual space-padding on the name/
       email/title labels (which was hand-tuned for English text widths
       only) with a language-agnostic fixed-width inline-block, so the
       input fields line up regardless of label text length in any
       language. */
    .postform-label { display: inline-block; min-width: 4.5em; }
    /* Quotes */
    .q { color: #{C_QMSG}; }
    /* Error */
    .error { font-size: 20px; font-size: 1.2rem; font-weight: bold; color: #{C_ERROR}; }
    /* Environment variables */
    .env { font-size: 13px; font-size: 0.8rem; font-size: 0.8rem; font-style: italic; }
    /* Buttons */
    input[type="button"],input[type="submit"],input[type="reset"] { -webkit-appearance: none; -moz-appearance: none; appearance: none; border: 2px solid #999; border-radius: 2px; background: #bbb; background-color: #d2d2d2; transition:0.2s; padding: .5px .4em; margin-right: .5em; }
    input[type="button"]:hover,input[type="submit"]:hover,input[type="reset"]:hover { background-color: #cdf; border-color: #888; }
    input[type="button"]:active,input[type="submit"]:active,input[type="reset"]:active  { background-color: #abd; border-color: #08c; }
    /* Text box */
    input[type="text"], input[type="number"], input[type="password"], textarea { max-width: 100%; -webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; background-color: #eee; border: 2px solid #999; border-radius: 2px; transition: 0.2s; }
    input[type="text"]:hover,input[type="number"]:hover, input[type="password"]:hover,textarea:hover { background-color: #cdf; border-color: #888; }
    input[type="text"]:focus,input[type="number"]:focus, input[type="password"]:focus,textarea:focus { background-color: #ccc; border-color: #08c; }
    input[type="number"] { max-width: 4em; }
    /* Plugins */
    /* Custom */
    @media screen and (min-width: 1020px) {  div.contents, .msgtree {   overflow-x: auto;  } } 

CSS;

$css = strtr($css, array(
    '{C_BACKGROUND}' => $CONF['C_BACKGROUND'],
    '{C_TEXT}'       => $CONF['C_TEXT'],
    '{C_A_COLOR}'    => $CONF['C_A_COLOR'],
    '{C_A_VISITED}'  => $CONF['C_A_VISITED'],
    '{C_SUBJ}'       => $CONF['C_SUBJ'],
    '{C_QMSG}'       => $CONF['C_QMSG'],
    '{C_A_ACTIVE}'   => $CONF['C_A_ACTIVE'],
    '{C_A_HOVER}'    => $CONF['C_A_HOVER'],
    '{C_ERROR}'      => $CONF['C_ERROR'],
));

echo $css;
