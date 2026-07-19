<?php
ini_set('display_errors', "On");

// 全てのエラーを表示
error_reporting(E_ALL);

// 擬古猫といっしょ

###############################################################################
#  メッセージ処理（点取り占いのぱくり）
###############################################################################

function giko_fortune(): string {

	global $CONF;
	$giko_file = ( isset( $CONF['GIKONEKO_KOTOBA_FILE'] ) && $CONF['GIKONEKO_KOTOBA_FILE'] !== '' )
		? $CONF['GIKONEKO_KOTOBA_FILE']
		: ( ( getenv( 'GIKO_DATA_DIR' ) ?: __DIR__ . '/data' ) . '/gikoneko_kotoba.dat' );

	// データファイルが存在しない場合は空ファイルを自動生成する。
	// file_exists()での事前チェックにより、file()の生のPHP警告が
	// ページ出力に混入するのを防ぐ（ここで処理を止まらせない）。
	if ( ! file_exists( $giko_file ) ) {
		@file_put_contents( $giko_file, '', LOCK_EX );
	}

	$fortunedata = @file( $giko_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

	if ( $fortunedata === false || count( $fortunedata ) === 0 ) {
		return '';
	}

	return $fortunedata[ random_int( 0, count( $fortunedata ) - 1 ) ];
}

###############################################################################
#  運勢データ（$GIKO_FORTUNES）
#
#  各要素は 'label'（language/*.txtのTキー）・'weight'（出現しやすさ、
#  省略時1）・'aa'（AAテンプレート）を持つ。{label}はTキーの訳語に、
#  {kotoba}はgiko_fortune()の結果に置換される（{kotoba}を複数書けば、
#  轟吉のようにそれぞれ別のことばが入る）。新しいAAを追加する場合は、
#  末尾に同じ形の要素を1つ足すだけでよい。
###############################################################################

function ksphp_giko_fortunes(): array {
	return array(
	array(
		'label' => 'GIKO_FORTUNE_SHOKICHI',
		'aa' => "
【{label}】
　　　 ∧ ∧
～′￣(´Д`)＜{kotoba}
  UU￣ U  U
",
	),
	array(
		'label' => 'GIKO_FORTUNE_CHUKICHI',
		'aa' => "
【{label}】
　　　 ∧ ∧
～′￣(`Д´)＜ﾊｯﾊｰﾝ!  {kotoba}
  UU￣ U  U
",
	),
	array(
		'label' => 'GIKO_FORTUNE_KYOU',
		'aa' => "
【{label}】
　　　 ∧ ∧
～′￣(;´Д`)＜{kotoba}
  UU￣ U  U
",
	),
	array(
		'label' => 'GIKO_FORTUNE_DAIKICHI',
		'aa' => "
【{label}】
          ヽ(`ー´)ノ＜{kotoba}
       ∧ ∧｜_ ｜
      (`ー´)  < ) ～
        U  U ￣￣UU 
",
	),
	array(
		'label' => 'GIKO_FORTUNE_YOUKICHI',
		'aa' => "
【{label}】
   ∧  ∧    
   ﾉ  ﾊ  ＼  
  ﾉ ∂.∂)＜{kotoba}
    (∩∩
γ～/___|
     U U
",
	),
	array(
		'label' => 'GIKO_FORTUNE_GOUKICHI',
		'aa' => "
【{label}】
                          *  . .  * (ﾟДﾟ)＜{kotoba}
＼猫ビィィィィム！／    *  .     .    *  (ﾟДﾟ)＜{kotoba}
       ∧ ∧       ＿＿＿＿＿※   .              *  
～′￣(     )￣￣￣        .     .  *
  UU￣ U  U                  . .  *              (ﾟДﾟ)＜{kotoba}
",
	),
	array(
		'label' => 'GIKO_FORTUNE_NEKOKICHI',
		'aa' => "
【{label}】
       ∧ ∧
       ■●■
      (´ー`)＜{kotoba}
      (｜ o｜)
      U｜ o｜U
      Ｕ  Ｕ
",
	),
	array(
		'label' => 'GIKO_FORTUNE_DOKICHI',
		'aa' => "
【{label}】
  |
  |
  |    ∧ ∧
  ′￣(`Д´)＜{kotoba}
  |  ＿＿  |
  |||    |||
  UU     U U
",
	),
	array(
		'label' => 'GIKO_FORTUNE_AIKICHI',
		'aa' => "
【{label}】
       ＿∧ ∧
     ／（´ー`)＜{kotoba}
   ／  ／U  U∧ ∧
ノ’（  ￣￣(´ー`)＜{kotoba}
  UU  UU￣￣ U  U
",
	),
	array(
		'label' => 'GIKO_FORTUNE_INKICHI',
		'aa' => "
【{label}】
       ∧ ∧
    ／(´ー`)＜{kotoba}
乙／  ) ⊃ ⊃
  ＼⊃＼⊃  ））））））））
",
	),
	array(
		'label' => 'GIKO_FORTUNE_KICHI',
		'aa' => "
【{label}】
   ∧ ∧
／(´ー`)＜{kotoba}
￣￣￣￣￣|
",
	),
	array(
		'label' => 'GIKO_FORTUNE_RAKUKICHI',
		'aa' => "
【{label}】
       ∧ ∧
    ヽ(´ー`)ノ＜{kotoba}
      ｜   ｜
      ﾉ  _ ﾉ
ε≡Ξ∪ ∪
",
	),
	array(
		'label' => 'GIKO_FORTUNE_KYOU',
		'aa' => "
【{label}】
　 ∧∧
　/⌒ヽ)＜{kotoba}
～(_＿)
",
	),
	array(
		'label' => 'GIKO_FORTUNE_KICHI',
		'aa' => "
【{label}】
({kotoba})
　　 。
　　。
 ∧ ∧⌒ヽ
(´ー`)(　)～
￣￣￣￣￣￣|
",
	),
	array(
		'label' => 'GIKO_FORTUNE_KICHI',
		'weight' => 11,
		'aa' => "
【{label}】
　　　 ∧ ∧
～′￣(´ー`)＜{kotoba}
  UU￣ U  U
",
	),
	);
}

###############################################################################
#  メイン処理
###############################################################################

function giko_display(): void {
	$GIKO_FORTUNES = ksphp_giko_fortunes();

	echo "<BLOCKQUOTE><PRE>

" . T('GIKO_TOGETHER') . "　[<A HREF=\"./gikonekoadd.php\" target=\"link\">" . T('GIKO_TEACH_LINK_TEXT') . "</A>]

";

	$total_weight = 0;
	foreach ( $GIKO_FORTUNES as $entry ) {
		$total_weight += $entry['weight'] ?? 1;
	}

	$pick = random_int( 0, $total_weight - 1 );
	$chosen = $GIKO_FORTUNES[ count( $GIKO_FORTUNES ) - 1 ]; // フォールバック（理論上到達しない）
	foreach ( $GIKO_FORTUNES as $entry ) {
		$w = $entry['weight'] ?? 1;
		if ( $pick < $w ) {
			$chosen = $entry;
			break;
		}
		$pick -= $w;
	}

	$out = str_replace( '{label}', T( $chosen['label'] ), $chosen['aa'] );
	while ( ( $pos = strpos( $out, '{kotoba}' ) ) !== false ) {
		$out = substr_replace( $out, giko_fortune(), $pos, strlen( '{kotoba}' ) );
	}
	echo $out;

	echo "</PRE></BLOCKQUOTE>\n";
}
