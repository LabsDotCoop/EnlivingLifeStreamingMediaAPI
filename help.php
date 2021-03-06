<?php
/**
 * Chronolabs Life Streaming Media REST API File
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Chronolabs Cooperative http://labs.coop
 * @license         General Public License version 3 (http://labs.coop/briefs/legal/general-public-licence/13,3.html)
 * @package         lookups
 * @since           1.1.2
 * @author          Simon Roberts <wishcraft@users.sourceforge.net>
 * @version         $Id: index.php 1000 2013-06-07 01:20:22Z mynamesnot $
 * @subpackage		api
 * @description		Internet Protocol Address Information API Service REST
 */

	$genre = getRandomGenre();
	$stations = getRandomGenre();
	$station = lifeRadio::getStationsFromAPI('random', '');
	$pu = parse_url($_SERVER['REQUEST_URI']);
	$source = (isset($_SERVER['HTTPS'])?'https://':'http://').strtolower($_SERVER['HTTP_HOST']) . '/';
	$outputs = array('serial.api' => 'Serialisation', 'json.api' => 'JSON', 'xml.api'=>'XML');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php 	$servicename = "Enliving Streaming Media"; 
		$servicecode = "ESM"; ?>
	<meta property="og:url" content="<?php echo (isset($_SERVER["HTTPS"])?"https://":"http://").$_SERVER["HTTP_HOST"]; ?>" />
	<meta property="og:site_name" content="<?php echo $servicename; ?> Open Services API's (With Source-code)"/>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="rating" content="general" />
	<meta http-equiv="author" content="wishcraft@users.sourceforge.net" />
	<meta http-equiv="copyright" content="Chronolabs Cooperative &copy; <?php echo date("Y")-1; ?>-<?php echo date("Y")+1; ?>" />
	<meta http-equiv="generator" content="wishcraft@users.sourceforge.net" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="//labs.partnerconsole.net/execute2/external/reseller-logo">
	<link rel="icon" href="//labs.partnerconsole.net/execute2/external/reseller-logo">
	<link rel="apple-touch-icon" href="//labs.partnerconsole.net/execute2/external/reseller-logo">
	<meta property="og:image" content="//labs.partnerconsole.net/execute2/external/reseller-logo"/>
	<link rel="stylesheet" href="/style.css" type="text/css" />
	<link rel="stylesheet" href="//css.ringwould.com.au/3/gradientee/stylesheet.css" type="text/css" />
	<link rel="stylesheet" href="//css.ringwould.com.au/3/shadowing/styleheet.css" type="text/css" />
	<title><?php echo $servicename; ?> (<?php echo $servicecode; ?>) Open API || Chronolabs Cooperative (Sydney, Australia)</title>
	<meta property="og:title" content="<?php echo $servicecode; ?> API"/>
	<meta property="og:type" content="<?php echo strtolower($servicecode); ?>-api"/>
	<!-- AddThis Smart Layers BEGIN -->
	<!-- Go to http://www.addthis.com/get/smart-layers to customize -->
	<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-50f9a1c208996c1d"></script>
	<script type="text/javascript">
	  addthis.layers({
		'theme' : 'transparent',
		'share' : {
		  'position' : 'right',
		  'numPreferredServices' : 6
		}, 
		'follow' : {
		  'services' : [
			{'service': 'twitter', 'id': 'ChronolabsCoop'},
			{'service': 'twitter', 'id': 'Cipherhouse'},
			{'service': 'twitter', 'id': 'OpenRend'},
			{'service': 'facebook', 'id': 'Chronolabs'},
			{'service': 'linkedin', 'id': 'founderandprinciple'},
			{'service': 'google_follow', 'id': '105256588269767640343'},
			{'service': 'google_follow', 'id': '116789643858806436996'}
		  ]
		},  
		'whatsnext' : {},  
		'recommended' : {
		  'title': 'Recommended for you:'
		} 
	  });
	</script>
	<!-- AddThis Smart Layers END -->
</head>

<body>
	<div class="main">
	    <h1><?php echo $servicename; ?> (<?php echo $servicecode; ?>) Open API || Chronolabs Cooperative (Sydney, Australia)</h1>
	    <p>This is an API Service for conducting search or retriving URL/URI's for Streaming service around the internet, you can use it to find one of our life media sources anytime, and remember to use it wisely.</p>
	    <h2>Code API Documentation</h2>
	    <p>You can find the phpDocumentor code API documentation at the following path :: <a target="_blank" href="<?php echo $source; ?>docs/" target="_blank"><?php echo $source; ?>docs/</a>. These should outline the source code core functions and classes for the API to function!</p>
	    <h2>Life Media Sources Available</h2>
	    <p>This is the media sources currently available:~</p>
	    <blockquote>
	    	<ol>
	    		<ul><strong>radio</strong> ~ Radio media streams</ul>
	    	</ol>
	    </blockquote>
	    <?php 
	   	foreach($outputs as $output => $title) { 
	?>
	    <h2><?php echo $title; ?> Document Output</h2>
	    <p>This is done with the <em><?php echo $output; ?></em> extension at the end of the url, you replace the address with either a place, an country either with no spaces in words or country ISO2 or ISO3 code and a name to search for the place on the api</p>
	    <blockquote>
		<font color="#009900">This is for a primary genre categories of the source which is currently <em>radio</em></font><br/>
		<em><strong><a href="<?php echo $source; ?>v2/radio/genres/primary/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/genres/primary/<?php echo $output; ?></a></strong></em><br /><br />
		<font color="#009900">This is for a all genre's information of the source which is currently <em>radio</em></font><br/>
		<em><strong><a href="<?php echo $source; ?>v2/radio/genres/all/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/genres/all/<?php echo $output; ?></a></strong></em><br /><br />
		<font color="#009900">This is for a genre's <strong><?php echo $genre; ?></strong> sub-categories of the source which is currently <em>radio</em></font><br/>
		<em><strong><a href="<?php echo $source; ?>v2/radio/genres/<?php echo $genre; ?>/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/genres/<?php echo $genre; ?>/<?php echo $output; ?></a></strong></em><br /><br />
		<font color="#009900">This is for a all genre's categorgies of the source which is currently <em>radio</em></font><br/>
		<em><strong><a href="<?php echo $source; ?>v2/radio/search/Sydney%20Dance/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/search/Sydney%20Dance/<?php echo $output; ?></a></strong></em><br /><br />
		<font color="#009900">This is for retrieving a stations listening for the genre <strong><?php echo $stations; ?></strong> on the <em>radio</em></font><br/>
	 		<em><strong><a href="<?php echo $source; ?>v2/radio/genre/<?php echo $stations; ?>/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/genre/<?php echo $stations; ?>/<?php echo $output; ?></a></strong></em><br /><br />
	 		<font color="#009900">This is for retrieving a Top Listening List of 500 station's and streams listings of the source which is currently <em>radio</em></font><br/>
		<em><strong><a href="<?php echo $source; ?>v2/radio/top500/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/top500/<?php echo $output; ?></a></strong></em><br /><br />
	 		<font color="#009900">This is for retrieving a station listening at random and streams listings of the source which is currently <em>radio</em></font><br/>
		<em><strong><a href="<?php echo $source; ?>v2/radio/random/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/random/<?php echo $output; ?></a></strong></em><br /><br />
		<font color="#009900">This is for retrieving a station's stream(s) URI/URL's with an identity key listings of the source which is currently <em>radio</em></font><br/>
		<em><strong><a href="<?php echo $source; ?>v2/radio/station/<?php echo $station['key']; ?>/<?php echo $output; ?>" target="_blank"><?php echo $source; ?>v2/radio/station/<?php echo $station['key']; ?>/<?php echo $output; ?></a></strong></em><br /><br />
		</blockquote>
	    
	<?php 
		} if (file_exists($fionf = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'apis-labs.coop.html')) {
	    	readfile($fionf);
	    }?>		
	    <h2>The Author</h2>
	    <p>This was developed by Simon Roberts in 2014 and is part of the Chronolabs System and Xortify. if you need to contact simon you can do so at the following address <a href="mailto:wishcraft@users.sourceforge.net">wishcraft@users.sourceforge.net</a></p></body>
	</div>
</html>
<?php 
