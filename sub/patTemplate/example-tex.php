<?PHP
	include( "include/patTemplate.php" );

	$tmpl	=	new	patTemplate( "tex" );

	//	basedir of templates
	$tmpl->setBasedir( "templates" );

	$tmpl->readTemplatesFromFile( "latex.tmpl" );


	$sections = array( 0 => array( "title" => "A Title", "label" => "sec-title" , "body" => "Well a section need som tet data" ),
		1 => array( "title" => "An important Title", "label" => "sec-important" , "body" => "Well a section need som tet data"),
		2 => array( "title" => "Yet another title", "label" => "sec-another" , "body" => "Well a section need som tet data"),
		3 => array( "title" => "One More Title", "label" => "sec-more" , "body" => "Well a section need som tet data"),
		4 => array( "title" => "Even More Titles", "label" => "sec-even" , "body" => "Well a section need som tet data") );
	$tmpl->addRows( "SECTION", $sections, "SECTION_" );

	echo "<pre>\n";
	$tmpl->displayParsedTemplate( );
	echo "</pre>\n";

	//	Debug information
	echo	"<br><br>----------------------------------------------&lt;DUMP INFOS&gt;----------------------------------------------------<br><br>";
	
	$tmpl->dump();
?>
