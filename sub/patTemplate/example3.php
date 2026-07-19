<?PHP
	include( "include/patTemplate.php" );

	$tmpl	=	new	patTemplate();

	//	In diesem Verzeichnis liegen die Templates
	$tmpl->setBasedir( "templates" );

	$tmpl->readTemplatesFromFile( "example3.tmpl.html" );

	$tmpl->addVar( "suchergebnis", "ERGEBNISSE", 5 );

	//	Alle Templates ausgeben
	$tmpl->displayParsedTemplate( );

	//	Debug Infos ausgeben
	echo	"<br><br>----------------------------------------------&lt;DUMP INFOS&gt;----------------------------------------------------<br><br>";
	
	$tmpl->dump();
?>