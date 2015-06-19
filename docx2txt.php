<?php
	
	if( $args[1] )
	{
		$file = $args[1];
	}
	else if( ( $stdin = fopen( 'php://stdin','rb' ) ) && stream_set_blocking( $stdin, 0 ) && fread( $stdin, 1) )
	{
		//we read one byte, we must read that byte again
		rewind( $stdin );
		
		if( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' )
		{
			$file = tempnam( '%temp%', 'x2T' );
		}
		else
		{
			$file = tempnam( '/tmp', 'x2T' );
		}
		
		//keeping compatibility with older versions
		register_shutdown_function( create_function( '$file', 'unlink( $file );' ), $file );
		
		//without b, windows will interpret it as text
		$tmp_file = fopen( $file, 'w+b' );
		
		while( !feof( $stdin ) )
		{
			//usually attribution units are 4kb long, or 1024*4 bytes
			fwrite( $tmp_file, fread( $stdin, 1024*4 ) );
		}
		
		fclose( $stdin );
		fclose( $tmp_file );
		unlink( $tmp_file );
	}
	else
	{
		trigger_error( 'Error: a file on STDIN or a filename as the first argument', E_USER_ERROR );
	}
	
	//docx files are just a zipped file
	$zip = new ZipArchive();
	
	if ( $zip->open( $file ) === true )
	{
		//this is where all the content is present
		if( $xml = $zip->getFromName( 'word/document.xml' ) )
		{
			$dom = new DOMDocument( '1.0', 'utf-8' );
			$dom->loadXML( $xml );

			//the namespace is actually w, the tag is t. text is in <w:t> elements
			for($i = 0, $list = $dom->getElementsByTagNameNS( '*', 't' ); $i < $list->length; $i++ )
			{
				echo $list[$i]->nodeValue, PHP_EOL;
			}
			
			$zip->close();
		}
		else
		{
			trigger_error( 'Invalid structure for the file "' . $args[1] . '"' );
		}
	}
	else
	{
		trigger_error( 'Failed to open the file "' . $args[1] . '"', E_USER_ERROR );
	}
