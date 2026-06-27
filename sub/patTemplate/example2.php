<?PHP
	include( "include/patTemplate.php" );

	$tmpl	=	new	patTemplate();

	//	In diesem Verzeichnis liegen die Templates
	$tmpl->setBasedir( "templates" );

	$tmpl->readTemplatesFromFile( "example2.tmpl.html" );

	$tmpl->addVars( "listeneintrag", array( "CUSTOMER_NAME" => array( "Stephan Schmidt", "Sebastian Mordziol", "Georg Rothweiler" ),
									"CUSTOMER_EMAIL" => array( "stephan@metrix.de", "sebastian@metrix.de", "georg@metrix.de" ) ) );

	//	Alle Templates ausgeben
	$tmpl->displayParsedTemplate( );

	//	Debug Infos ausgeben
	echo	"<br><br>----------------------------------------------&lt;DUMP INFOS&gt;----------------------------------------------------<br><br>";
	
	$tmpl->dump();
?>