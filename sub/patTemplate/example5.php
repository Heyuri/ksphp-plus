<?PHP
	include( "include/patTemplate.php" );

	$tmpl	=	new	patTemplate();

	//	In diesem Verzeichnis liegen die Templates
	$tmpl->setBasedir( "templates" );

	$tmpl->readTemplatesFromFile( "example5.tmpl.html" );

	//	Alle Templates ausgeben
	$tmpl->displayParsedTemplate( );

	//	Debug Infos ausgeben
	echo	"<br><br>----------------------------------------------&lt;DUMP INFOS&gt;----------------------------------------------------<br><br>";
	
	$tmpl->dump();
?>