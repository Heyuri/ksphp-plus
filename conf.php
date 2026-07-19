<?php

  # "*"がついている項目は変更・確認が必要です。
  # Items marked "*" need to be changed or confirmed

/* 共通設定 */
/* Common settings */
$CONF = array(

  #-------------------------- URLなど ---------------------------
  #------------------------- URLs, etc. -------------------------

  // * 掲示板スクリプトのURL（相対パス可）
  // * URL for the bulletin board script (Relative paths are acceptable)
  'CGIURL' => './bbs.php',
  
  // 掲示板スクリプトのURL（Refererチェック用、フルURLを記述。空にするとチェックしません）
  // URL for the bulletin board script (Describes the full URL for referer checking. If empty, it will not be checked)
  'REFCHECKURL' => '',
  
  // スクリプトを設置するホストアドレス（呼び出し元チェック用。空にするとチェックしません）
  // Host address where the script will be installed (For caller checking. If empty, it will not be checked)
  'BBSHOST' => '',

  #-------------------------- ファイルとディレクトリ --------------------------
  #------------------------- Files and directories -------------------------

  // ログファイル名
  // Log file name
  'LOGFILENAME' => './logs/bbs.log',
  
  //過去ログ保存用ディレクトリの名前（最後に/を入れてください。空の場合は過去ログを保存しません）
  // Name of the directory for storing logs (Please put a / at the end. If empty, logs will not be saved)
  'OLDLOGFILEDIR' => './logs/log/',

  // 過去ログファイルのZIPアーカイブディレクトリ（最後に/を入れてください。空の場合かgzcompress関数が使用不可の場合はZIPアーカイブを作成しません）
  // ZIP archive directory for past log files (Please put a / at the end. If empty, or if gzcompress is unavailable, ZIP archives will not be created)
  'ZIPDIR' => './archive/',

  // 擬古猫のことば（占い）データファイル（sub/ではなくgikoneko.php/gikonekoadd.phpで使用）
  // Gikoneko fortune-message data file (used by gikoneko.php / gikonekoadd.php)
  'GIKONEKO_KOTOBA_FILE' => './data/gikoneko_kotoba.dat',

  #------------------------------ 掲示板名称など ---------------------------------
  #------------------------- Bulletin board name, etc. -------------------------

  // * 掲示板の名前
  // * Bulletin board name
  ## TL note: Ayashii World titles usually take the form of "AyashiiWorld@[web host name]
  ## TL note: StrangeWorld@ is the common English way to start it
  'BBSTITLE' => 'あやしいわーるど＠',

  // * 広報室のURL
  // * URL for the Public Relations Office (home/information page)
  'INFOPAGE' => './',

  #------------------------------- 管理設定 ----------------------------------
  #------------------------- Administrator settings -------------------------

  // * 管理人の名前
  // * Administrator name
  'ADMINNAME' => '管理人',

  // * 管理人のメールアドレス
  // * Administrator email address
  'ADMINMAIL' => 'admin@example.com',
  
  // * 管理用パスワード（暗号化パスワード。最初は空にしておいてください）
  // * Administor password (Encrypted password. Please leave this empty at first)
  'ADMINPOST' => '',

  // * 管理モード移行用キーワード（半角英数字、空の場合管理モードを使用できません）
  // * The keyword for entering administrator mode (regular alphanumeric characters are recommended. If empty, administrator mode will be unavailable)
  'ADMINKEY' => '',

  ## TL note: To enter admin mode after the bulletin board is up and running, put the adminkey (and only the adminkey) into the name and contents fields of the post form, then hit send.
  ## TL note: Putting the adminkey (and only the adminkey) into the name field alone will display the admin capcode on your post.

  #--------------------------- 検索エンジン --------------------------
  #------------------------- Search engine -------------------------

  // 検索エンジンに掲示板の概要を教えます。短い文章にするといいでしょう
  // Gives search engines an overview of the bulletin board. Keep it short and sweet
  'META_DESCRIPTION' => 'あやしいわーるど＠',

  // 掲示板に関連した単語をカンマ区切りで入力します。あまり多すぎるとペナルティを食らう場合もあるようです
  // Enter some words related to the bulletin board, seperated by commas. If there's too many, you may be penalized by search engines
  'META_KEYWORDS' => 'Ayashii,ᵃʸᵃˢʰⁱⁱ,strange,bulletin,board,BBS',

  // コンテンツの言語を指定してください。通常は日本語(ja)
  // Please specify the language of the content. Typically you'd use Japanese (ja)
  ## TL note: Ayashii World-style AA (ASCII art) may not render correctly using English (en) without some additional CSS to change the font to MS Gothic or another compatible font
  // 日本語：ja
  // English : en
  'META_LANGUAGE' => 'ja',

  // 掲示板ソフト全体の表示言語（UIやテンプレートの言語）を指定します。
  // Specifies the software’s display language (the language used for the UI and templates).
  // 日本語：japanese
  // English : english
  'LANGUAGE_FILE' => 'japanese',

  #------------------------------ 動作設定 -------------------------------
  #------------------------- Operation settings -------------------------

  // 掲示板投稿機能停止
  // ログは読めますが、投稿が一切できなくなります。）
  // Halt the posting functionality on the bulletin board
  // (You will be able to read the logs, but be unable to post anything)
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'RUNMODE' => 0,

  // 画像のアップロード機能
  // Image upload function.
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'BBSMODE_IMAGE' => 1,

  // 管理者専用投稿モード（日記用）
  #   0 : 無効
  #   1 : 管理者による新規投稿のみ許可し、一切のフォロー投稿を許可しない
  #   2 : 新規投稿は管理者のみ許可し、フォロー投稿は誰でも許可する
  // Administrator-only post mode (for diary purposes)
  #   0 : Disabled
  #   1 : Only the administrator is allowed to make new posts, follow-up posts are not allowed
  #   2 : Only the administrator is allowed to make new posts, follow-up posts are allowed
  'BBSMODE_ADMINONLY' => 0,

  // UNDO機能（自分が直前に投稿した記事のみ消去できる機能）の使用
  // UNDO function usage (a function that allows you to delete your most recent post)
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'ALLOW_UNDO' => 1,

  // 「0件」ボタンと「未読」ボタンの表示
  // （投稿数の少ない掲示板であれば必要ないでしょう）
  // Display the [0] and [Unread] buttons
  // (If the bulletin board doesn't receive much post activity, this won't be necessary)
  #   0 : 表示しない (Do not display)
  #   1 : 表示する (Display)
  'SHOW_READNEWBTN' => 1,

  // 未読が0件のときに「擬古猫といっしょ」を表示するかどうか
  // Whether to show "Gikoneko-to-issho" when there are no unread posts
  #   0 : 表示しない (Disabled)
  #   1 : 表示する (Enabled)
  'GIKONEKO_TOISSHO' => 1,

  // gzip圧縮の初期値
  // （表示が高速化されます）
  // Default value for gzip compression
  // (Speeds up page rendering)
  #   0 : 圧縮しない (No compression)
  #   1 : 圧縮する (Compression)
  'GZIPU' => 1,

  // メッセージの保存数
  // Number of messages stored
  'LOGSAVE' => 99999,

  // １画面に表示するメッセージの表示数
  // Number of messages displayed on a single page
  'MSGDISP' => 40,

  // 二重書き込みチェック件数
  // Number of posts to check for duplicate entries
  # 'CHECKCOUNT' => 20,
  'CHECKCOUNT' => 0,

  // １メッセージの最大桁数
  // Maximum number of characters in a single message
  'MAXMSGCOL' => 1000,

  // １メッセージの最大行数
  // Maximum number of lines in a single message
  'MAXMSGLINE' => 1000,

  // 投稿者の最大文字数
  // Maximum number of characters in the name field
  'MAXNAMELENGTH' => 128,

  // メールの最大文字数
  // Maximum number of characters in the email field
  'MAXMAILLENGTH' => 256,

  // 題名の最大文字数
  // Maximum number of characters in the title field
  'MAXTITLELENGTH' => 128,

  // １メッセージの最大サイズ(byte)
  // Maximum size of a single message (bytes)
  'MAXMSGSIZE' => 9000,

  // 最短投稿間隔（秒数）
  // Minimum posting interval (seconds)
  # 'MINPOSTSEC' => 2,
  'MINPOSTSEC' => 0,

  // 最長投稿間隔（秒数）
  // Maximum posting interval (seconds)
  # 'MAXPOSTSEC' => 1,
  'MAXPOSTSEC' => 0,

  // 自動リンク機能の初期値
  // Default value for the automatic link function
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'AUTOLINK' => 1,

  // フォロー投稿画面表示
  #   0 : 新規ウィンドウをオープンして表示(Rebirth系の仕様)
  #   1 : 同一画面に表示(本店系の仕様)
  // Follow-up post screen display
  #   0 : Open and display a new window (Rebirth-type setting)
  #   1 : Display on the same screen (Honten-type setting)
  'FOLLOWWIN' => 0,

  // 投稿者IPアドレスの記録
  // Record user IP addresses
  #   0 : 記録しない (Do not record)
  #   1 : 匿名プロクシのみ記録 (Only record anonymous proxies)
  #   2 : 全て記録 (Record all)
  'IPREC' => 2,
  
  // User Agent(ブラウザ名)の記録
  // Record User Agent (browser name)
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'UAREC' => 1,

  // 投稿者IPアドレスの表示(非推奨)
  // （投稿者IPアドレスの記録が有効になっている必要があります）
  // Display user IP addresses (not recommended)
  // (User IP address recording must be enabled)
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'IPPRINT' => 0,

  // User Agent(ブラウザ名)の表示
  // （User Agentの記録が有効になっている必要があります）
  // Display User Agent (browser name)
  // (User Agent recording must be enabled)
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'UAPRINT' => 0,

  // 同一IPアドレスからの投稿を拒否する時間 (秒)
  // （投稿者IPアドレスの記録が有効になっている必要があります
  #   0に設定するとminpostsecの設定により制限を行います）
  // Time in which posts from the same IP address will be rejected (seconds)
  // (User IP address recording must be enabled
  #   If set to 0, users will be limited by the minpostsec setting)
  'SPTIME' => 0,

  // Cookieによる投稿者／メールアドレス記憶機能の使用
  // Use cookies to remember name/email address
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'COOKIE' => 1,

  // 簡易自作自演防止機能の使用
  // （返信元と返信先のIPアドレスが同一の場合に名前欄に(自己レス)と表示する機能
  //   投稿者IPアドレスの記録が有効になっている必要があります）
  // Use a simple self-replying prevention function
  // (If the IP addresses of the replier and reply recipient are the same, the reply will display (self-reply) in the name field
  //   User IP address recording must be enabled)
  #   0 : 無効 (Disabled)
  #   1 : 有効 (Enabled)
  'SHOW_SELFFOLLOW' => 1,

  #-------------------------- カウンターなど --------------------------
  #------------------------- Counters, etc. -------------------------

  // * カウンターのスタート日付
  // * Counter start date
  'COUNTDATE' => '2026/07/20',

  // カウンターのファイル名の先頭部分
  // First part of the counter's file name
  'COUNTFILE' => './data/count/count',

  // カウンターの壊れにくさレベル
  #  (推奨値3～5 値が大きいほどエラーが発生しにくくなりますがサーバー負荷が大きくなります)
  // Counter breakage resistance level
  # (Values between 3-5 are recommended. The larger the value, the less likely the counter will be erroneous, but the greater the server load)
  'COUNTLEVEL' => 3,

  // リアルタイム参加者カウント用ファイル名
  //  (リアルタイム参加者カウント機能を使用しない場合は空のままにしておきます)
  // File name for real-time participant counting
  //  (Leave it empty if you don't want to use the real-time participant counting function)
  'CNTFILENAME' => './data/bbs.cnt',

  // リアルタイム参加者カウント間隔 (秒)
  // （最終ページビューからこの時間を超えた参加者は集計から除外されます）
  // Real-time participant count interval (seconds)
  // (Participants who have exceeded this length of time since their last page view will be excluded from the tally)
  'CNTLIMIT' => 300,

  #------------------------- 時間 --------------------------
  #------------------------- Time -------------------------

  // サーバー設置場所と日本との時差
  #   日本             : 0
  #   グリニッジ標準時 : -9
  #   アメリカ         : -14 (ワシントン)
  #                    : -20 (ミッドウェー諸島)
  #   ニュージーランド : 3
  // Time difference between the location of the host server and Japan (or your own country)
  #   Japan               : 0
  #   Greenwich Mean Time : -9
  #   America             : -14 (Washington)
  #                       : -20 (Midway Islands)
  #   New Zealand         : 3
  'DIFFTIME' => 0,

  // 秒数時差（微調整用、マイナス値可）
  // Time difference in seconds (For fine adjustment. Negative values allowed)
  'DIFFSEC' => 0,

  #---------------------------- 色の設定(16進で指定)など ---------------------------
  #------------------------- Color settings (hex), etc. -------------------------

  // 背景色
  # クラシック：007f7f (Teal)
  # Rebirth系：004040 (黒板)
  # 本店系：303c6d (蔵藍)
  // Background color
  # Classic：007f7f (teal)
  # REBIRTH-type：004040 (blackboard)
  # Honten-type：303c6d (indigo)
  'C_BACKGROUND' => '004040',

  // テキスト色
  // Text color
  'C_TEXT' => 'efefef',

  // リンク色
  // Link color
  'C_A_COLOR' => 'cfe',    # 通常 (Normal)
  'C_A_VISITED' => 'ddd',  # 訪問済み (Visited)
  'C_A_ACTIVE' => 'f00',   # アクティブ (Active)
  'C_A_HOVER' => '1ee',    # ホバー(マウスオーバー) Hover (mouseover)

  'C_SUBJ' => 'fffffe',   # 題名の色 (Title color)
  // 引用メッセージの色（色を変えない場合は空にしてください）
  // Quote message color (Leave empty if you don't want the color to change)
  'C_QMSG' => 'ccc',
  'C_ERROR' => 'f00',  # エラーメッセージの色 (Error message color)


  // フォロー投稿画面ボタンに表示する文字
  // Text to be displayed on the follow-up post screen button
  'TXTFOLLOW' => '■',
  
  // 投稿者検索ボタンに表示する文字
  // Text to be displayed on the user search button
  'TXTAUTHOR' => '★',

  // スレッド表示ボタンに表示する文字
  // Text to be displayed on the thread view button
  'TXTTHREAD' => '◆',

  // ツリー表示ボタンに表示する文字
  // Text to be displayed on the tree view button
  'TXTTREE' => '木',

  // UNDO(自分が直前に投稿した記事のみ消去)ボタンに表示する文字
  // Text to be displayed on the UNDO (Delete only the last post you posted) button
  ## TL Note: You can set to 'Undo' for English instances
  'TXTUNDO' => '×',

  // フォロー投稿時に相手の投稿者名に付加する文字（一般の掲示板では「さん」などを付けると良いでしょう）
  // Text to be added to the other user's name when making a follow-up post (on a typical bulletin board it's best to use "-san" or something similar)
  'FSUBJ' => '',

  // 匿名の投稿者名（一般の掲示板では「匿名」、「名無し」などを付けると良いでしょう）
  //Anonymous username (on a typical bulletin board it's best to use "Anonymous", "Nameless", etc.)
  'ANONY_NAME' => '　',

  #---------------------------- 過去ログ ---------------------------
  #------------------------- Message logs -------------------------

  // 過去ログの保存形式
  // (HTML形式にすると過去ログ検索が利用できなくなります)
  // Save format for message logs
  // (Past log search will not be available if you use HTML format)
  #   0 : HTML形式 (HTML format)
  #   1 : バイナリ形式 (Binary format)
  'OLDLOGFMT' => 1,

  // 過去ログからのフォロー投稿・投稿者検索
  // (過去ログがバイナリ形式の場合のみ有効)
  // Search for follow-up posts and usernames in the message logs
  // (Only enabled if message logs are in binary format)
  #   0 : 不可 (Not allowed)
  #   1 : 可 (Allowed)
  'OLDLOGBTN' => 1,

  // 過去ログの保存方法
  // How to save message logs
  #   0 : 日毎 (Daily)
  #   1 : 月毎 (Monthly)
  'OLDLOGSAVESW' => 1,

  // 過去ログの保存日数
  //  (過去ログの保存方法が日毎の場合にのみ有効)
  // Number of days to keep message logs
  //  (Only available if message logs are saved daily)
  'OLDLOGSAVEDAY' => 12,

  // 過去ログの最大ファイルサイズ
  // Maximum file size for message logs
  'MAXOLDLOGSIZE' => 4 * 1024 * 1024,

  #---------------------------- 表示テンプレートなど ----------------------------
  #------------------------- Display templates, etc. -------------------------

  // * リンク行
  // * Links line
  ## TL Note: Commented-out-in-HTML line below is an example.
  'BBSLINK' => '
<!-- 例:  |  <a href="http://strange.egoism.jp/script/" target="_blank">くずはすくりぷとPHP</a> -->
|| <a href="https://example.com/" target="_blank">example.com</a> | <a href="https://example.net/" target="_blank">example.net</a>
',

  // メッセージテンプレート
  // Message template
#  'TMPL_MSG' => '
#<div class="m" id="m{val postid}">
#  {val postid}<span class="nw"><span class="ms">{val title}</span>&nbsp;&nbsp;<span class="mu">User: <span class="mun">{val user}</span></span>&nbsp;
#  &nbsp;<span class="md">Post date: {val wdate}<a id="a{val postid}">&nbsp;</a>
#  {val btn}</span></span>
#  <blockquote>
#    <pre>{val msg}</pre>
#  </blockquote>
#{val envlist}</div>
#
#<hr /><!--  -->
#',

  // 環境変数表示テンプレート
  // Preferences display template
#  'TMPL_ENVLIST' => "<div class=\"env\">{val envaddr}{val envbr}{val envua}</div>\n",

  #------------------------------ アクセス制限など --------------------------------
  #------------------------- Access restrictions, etc. -------------------------

  ## TL Note: Commented out Lines starting with 例 are examples. 例 means example.
  
  // 投稿禁止ホスト名パターンリスト(Perl5互換正規表現)
  // List of hostname patterns prohibited from posting (Perl5 compatible regular expression)
  'HOSTNAME_POSTDENIED' => array(
    #例: 'npa\.go\.jp$',
      '.example.com',
      '.example.net'
  ),

  // アクセス禁止ホスト名パターンリスト(Perl5互換正規表現)
  // List of hostname patterns prohibited from access (Perl5 compatible regular expression)
  'HOSTNAME_BANNED' => array(
    #例: '\.npa\.go\.jp$',
  ),

  // アクセス禁止エージェント名パターンリスト(Perl5互換正規表現) 20230818 猫・新規追加
  // List of user agents prohibited from access (Perl5 compatible regular expression)
  'HOSTAGENT_BANNED' => array(
    #例# 'iPad$',
  'dummy',
  ),

  // 投稿禁止ワード
  // Prohibited words
  'NGWORD' => array(
  #例: 'Viagra','スーパーコピー'
    'viagra',
    'Viagra',
    'スーパーコピー'
  ),

  // 携帯モジュールからの投稿を携帯端末のIPで制限するか否か
  // 携帯版の投稿機能はプロテクトコードの同一IPアドレスチェックをしていないため、
  // 携帯端末のIPアドレスによる制限を推奨します。
  // (iモードなどではアクセスの度にIPアドレスが変わるため)
  // Whether or not to restrict posting from the mobile module by the IP of the mobile device
  // Since the posting function of the mobile version does not check the same IP address for the protect code,
  // it's recommended to restrict by the IP address of the mobile device.
  // (Because the IP address changes every time it accesses the site in i-mode, etc.)
  'RESTRICT_MOBILEIP' => 0,

  #---------------------------- 固定ハンドル名 ----------------------------
  #------------------------- Fixed handle names -------------------------

  // 'ハンドル名' => 'パスワード',
  // の形式で羅列してください。パスワードはそのまま記述してください。
  // 投稿者名欄にパスワードを記述して投稿すると、ハンドル名に変換されます。
  // 投稿者名欄にハンドル名をそのまま記述して投稿すると、「（騙り）」が付加されます。
  // Please list the desired handle names and passwords in the order of 'handle name' => 'password'.
  // Please enter the password exactly how it is.
  // If you then put this password into the username field and post, it will get converted to your handle name.
  // If you attempt to write the handle name into the username field, "(fraudster)" will be added to the post.
  'HANDLENAMES' => array(
    'しぱ' => 'しば',
    'Fraudster' => 'Administrator',
    '騙り' => '管理人',
    '驕り' => '管理入',
    'しば' => 'shiba',
    'テスト' => 'test'
  ),

  #------------------------------------- 画像アップロード（sub/bbsimage.php） -------------------------------------
  #------------------------- Image upload (sub/bbsimage.php) -------------------------

  // 画像アップロード先ディレクトリ（書き込み可能にしてください）
  // Image upload directory (please set it to be writable)
  'UPLOADDIR' => './upload/',

  // 最新アップロード画像番号を記録するファイル（書き込み可能にしてください）
  // File containing latest image upload file number (please set it to be writable)
  'UPLOADIDFILE' => './upload/id.txt',

  // 投稿内容にこの文字列があれば、その位置にアップロード画像を挿入する
  // If this string is present in the post content, the uploaded image will be inserted into that position
  'IMAGETEXT' => '%image',

  // アップロード画像用の総容量（KB）
  // Total space dedicated to uploaded images (KB)
  'MAX_UPLOADSPACE' => 10000,

  // アップロード画像の最大幅
  // Maximum width for uploaded images
  'MAX_IMAGEWIDTH' => 1280,

  // アップロード画像の最大高さ
  // Maximum height for uploaded images
  'MAX_IMAGEHEIGHT' => 1600,

  // アップロード画像の最大ファイルサイズ（KB）
  // Maximum file size for uploaded images (KB)
  'MAX_IMAGESIZE' => 200,

  // 掲示板表示時の画像縮小率（％）
  // Image scale factor when displayed on the bulletin board (%)
  'IMAGE_PREVIEW_RESIZE' => 100,

  #------------------------------------- ツリー表示（sub/bbstree.php） -------------------------------------
  #------------------------- Tree view (sub/bbstree.php) -------------------------

  // 枝の色
  // Branch color
  'C_BRANCH' => '5ff',

  // 更新時刻表示の色
  // Update time display color
  'C_UPDATE' => 'ccc',

  // 新着メッセージの色
  // New message color
  'C_NEWMSG' => 'fca',

  // ツリー表示件数
  // Number of trees displayed
  'TREEDISP' => 32,

  #------------------------------------- ログ検索（sub/bbslog.php） -------------------------------------
  #------------------------- Log search (sub/bbslog.php) -------------------------

  // 複数の過去ログを横断検索できるようにするか
  // Whether or not multiple logs can be searched
  'MULTIPLESEARCH' => 1,

  // 検索キーワードのハイライト色
  // Search term highlight color
  'C_QUERY' => 'FF8000',

  // 検索できるキーワードの最大数
  // Maximum number of keywords that can be searched
  'MAXKEYWORDS' => 10,

  #------------------------------------- 詳細設定（通常は変更不要です） -------------------------------------
  #------------------------- Advanced settings (usually don't require changing) -------------------------

  // カウンターの表示有無
  // Determines whether the counter is displayed or not
  'SHOW_COUNTER' => 1,

  // 時刻表示フォーマット
  // Time display format
  'DATEFORMAT' => '',

  #-------------------------- デバッグ --------------------------
  #------------------------- Debugging -------------------------

  // 実行時間の表示
  // Show processing time
  'SHOW_PRCTIME' => 1,
);
?>
