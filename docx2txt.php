<?php

	/*
	 * Simple docx parser into text
	 * Options:
	 * -f --file		file to be used
	 * -b --br			use <br/> instead of a newline
	 * -t --tmp_path	path to where the temporary file will be written
	 *    --base64		the input comes in base64
	 * 
	 */
	
	if( version_compare( PHP_VERSION, '5.3.0', '<' ) )
	{
		trigger_error( 'PHP5.3 and above is required. You currently have the version ' . PHP_VERSION, E_USER_ERROR );
	}
	if( !class_exists( '\ZipArchive' ) )
	{
		trigger_error( 'The class ZipArchive is required. Check if PECL 1.0 is installed properly', E_USER_ERROR );
	}
	if( !class_exists( '\DOMDocument' ) )
	{
		trigger_error( 'The class DOMDocument is required. Please, check if xml is enabled, or add the parameter --enable-libxml. If not, check if you have libxml installed', E_USER_ERROR );
	}
	
	$args = getopt('f::bt::',array('file::','br','tmp_path','base64'));
	
	if( isset( $args['f'] ) || isset( $args['file'] ) )
	{
		$file = isset( $args['f'] ) ? $args['f'] : $args['file'];
	}
	else if( ( $stdin = fopen( 'php://stdin','rb' ) ) && stream_set_blocking( $stdin, 0 ) && fread( $stdin, 1) )
	{
		//we read one byte, we must read that byte again
		rewind( $stdin );
		
		if( isset( $args['t'] ) || isset( $args['tmp_path'] ) )
		{
			$file = tempnam( isset( $args['t'] ) ? $args['t'] : $args['tmp_path'], 'x2T' );
		}
		else if( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' )
		{
			$file = tempnam( '%temp%', 'x2T' );
		}
		else
		{
			$file = tempnam( '/tmp', 'x2T' );
		}
		
		//avoids syntax errors with old versions, to allow to warn that php 5.3 is required
		register_shutdown_function( create_function( '$file', 'unlink( $file );' ), $file );
		
		//without b, windows will interpret it as text
		$tmp_file = fopen( $file, 'w+b' );
		
		if( isset( $args['base64'] ) )
		{
			$base64 = '';
			while( !feof( $stdin ) )
			{
				//32kb at a time doesn't seem a bad compromise
				$base64 .= fread( $stdin, 1024*32 );
			}
			fwrite( $tmp_file, base64_decode( $base64 ) );
			unset( $base64 );
		}
		else
		{
			while( !feof( $stdin ) )
			{
				//usually attribution units are 4kb long, or 1024*4 bytes
				//also, this is the usual ssd page size
				fwrite( $tmp_file, fread( $stdin, 1024*4 ) );
			}
		}
		
		fclose( $stdin );
		fclose( $tmp_file );
	}
	else
	{
		trigger_error( 'Error: a file on STDIN or a filename (arguments -f and --file) is missing', E_USER_ERROR );
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
				echo $list[$i]->nodeValue;
				if( isset( $args['b'] ) || isset( $args['br'] ) )
				{
					echo '<br/>';
				}
				else
				{
					echo PHP_EOL;
				}
			}
			
			$zip->close();
		}
		else
		{
			trigger_error( 'Invalid structure for the file "' . $file . '"', E_USER_ERROR );
		}
	}
	else
	{
		trigger_error( 'Failed to open the file "' . $file . '"', E_USER_ERROR );
	}
