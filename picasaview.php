<?php

/*
Plugin Name: PicasaView
Plugin URI: http://www.sattes-faction.de/picasaview
Description: Allows you to show all albums or a specific one from Google's picasaWeb in your posts. 
Version: 1.1.6
License: GPL
Author: Simon Sattes
Author URI: http://www.sattes-faction.de

 Copyright since 2008 Simon Sattes (email : simon.sattes@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class picasaView {
	
	/**
	 * Holds an additional index if instantView is activated and multiple picasaView-calls are on the same
	 * site - so that the javascript-functions don't get mad
	 * 
	 * @var int
	 */
	private $foundPlaceholders = 0;

	/**
	 * Helps keeping track of the currently shown album (with multiple tag-calls in one post
	 * @var int
	 */
	private $currentPostId = -1;
	private $postIndex = 0;
	
	private $options = array(
		'server' 			=> 'http://picasaweb.google.com',
		'userid' 			=> '',
		'album'				=> false,
		'thumbnailsize' 	=> 144,
		'imagesize' 		=> 800,
		'cropthumbnails'	=> false,
		'imagesperpage'		=> 9,
		'showdebugoutput'	=> false,
		'quickpaging'		=> true,
		'version'			=> '1.1.5',
		'instantview'		=> false,
		'datetimeformat'	=> '%d.%m.%Y',
		'authkey'			=> ''
	);
	
	private $imageSizes = array(32, 48, 64, 72, 94, 104, 110, 128, 144, 150, 160, 200, 220, 288, 320, 400,
	                                512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600);
	                               
	private $croppedSizes = array(32, 48, 64, 72, 104, 144, 150, 160);
	
	private $optionsPage = '';
	
	private $startIndex = 1;
	
	public function __construct() {
		
		// register hooks
		add_action('admin_menu', array(&$this, 'adminMenu'));
		add_action('admin_print_scripts', array(&$this, 'adminHeader'));
		add_action('wp_head', array(&$this, 'frontendHeader'), 20);
		add_action('init', array(&$this, 'loadScripts'));		
		// use WordPress' shortcode functions
		add_shortcode('picasaView', array(&$this, 'parsePlaceholders'));
		add_shortcode('picasaview', array(&$this, 'parsePlaceholders'));
		
		// @todo test other languages
		load_plugin_textdomain('picasaView', false, "picasaview/languages");
		
		$this->optionsPage = get_option('siteurl') . '/wp-admin/admin.php?page=picasaview.php';
		$this->loadConfig();
	}
	
	private function loadConfig() {
		
		// load options, overriding defaults
		if (get_option('picasaView_options')) {
			$this->options = array_merge($this->options, array_change_key_case(get_option('picasaView_options')));
		} 
	}
	
	private function getTemplate($template) {
	
		// we'll take a look if customized headers are stored in the currently used theme path within the
		// subdir picasaview_templates
		// if not, the default ones are taken (which are in wp-plugins/picasaview/templates)
	
		$customTemplate = TEMPLATEPATH . '/picasaview/' . $template . '.html';
		$defaultTemplate = dirname(__FILE__) . '/templates/' . $template . '.html';
		
		if(file_exists($customTemplate) && is_readable($customTemplate)) {
			return file_get_contents($customTemplate);
		} elseif(file_exists($defaultTemplate) && is_readable($defaultTemplate)) {
			return file_get_contents($defaultTemplate);
		} else {
			return "picasaView plugin: template file '$template.html' not found or not readable.";
		}
	}	
	
	public function parsePlaceholders($attributes, $content = null) {
		
		// ensure, that simplexml_load_string is enabled. Otherwise it won't work.
		if(!function_exists('simplexml_load_string')) {
			return "picasaView plugin: oops, this plugin requires <code>simplexml_load_string()</code> coming with PHP5. I'm sorry - you won't be able to use it :-(";
		}
		
		if(get_the_ID() != $this->currentPostId) {
			$this->postIndex = 0;
			$this->currentPostId = get_the_ID();
		} else {
			$this->postIndex++;
		}
		
		$this->foundPlaceholders++;
		
		// set basic options which can be overriden by each tag on its own
		$tagOptions = shortcode_atts($this->options, $attributes);
		
		// shortcode can't automatically transform positive strings into boolean values, so we do it by hand
		// yes-values
		$yes = array('1', 'yes');
		$convert = array('cropthumbnails', 'showdebugoutput', 'quickpaging', 'instantview');
		
		foreach ($convert as $c) {
			$tagOptions[$c] = in_array($tagOptions[$c], $yes);		
		}
		
		if(isset($_GET['picasaViewAlbumId'])) { // picasaViewAlbumId
			list($album, $index) = explode(',', $_GET['picasaViewAlbumId']);
			if(intval($index) == $this->postIndex) {
				$tagOptions['album'] = $album;
				$tagOptions['instantview'] = true;
				$this->startIndex = isset($_GET['startIndex']) ? $_GET['startIndex'] : 1;					
			} else {
				$this->startIndex = 1;
			}
		}

		$html = '';

		// url param settings for picasaweb
		$settings = array(	'kind' => 'photo',
						  	'thumbsize' 	=> $tagOptions['thumbnailsize'] . ($tagOptions['cropthumbnails'] ? 'c' : 'u'),
						  	'start-index' 	=> $this->startIndex,
						  	'max-results' 	=> !$tagOptions['quickpaging'] && intval($tagOptions['imagesperpage']) > 0 ? $tagOptions['imagesperpage'] : null);
					
		if(! empty($tagOptions['authkey'])) {
			$settings['authkey'] = $tagOptions['authkey'];
		}

		// we're going to view all images directly
		if($tagOptions['instantview']) {
			// we're having one specific album
			if(isset($tagOptions['album'])) {
		
				// now we set all given parameters and are ready to call picasaweb
				$rssFeed = $tagOptions['server'] . '/data/feed/api/user/' . $tagOptions['userid'] . '/album/'.$tagOptions['album'].'?'.http_build_query($settings);
				
				// get data and parse into a SimpleXML-Object
				list($data, $error) = $this->fetchDataFromPicasaweb($rssFeed, $tagOptions);
				$html = $error ? $error : $this->dispatchAlbum($data, $tagOptions, $this->startIndex);
			} else {
				$html = "picasaView: invalid album call. the option 'instantview' must only be used in combination with 'album'.";
			}
		} else {
			// gimme all albums!
			$rssFeed = $tagOptions['server'] . '/data/feed/api/user/' . $tagOptions['userid'] . '?kind=album&access=public';

			// get data and parse into a SimpleXML-Object
			list($data, $error) = $this->fetchDataFromPicasaweb($rssFeed, $tagOptions);
			$html = $error ? $error : $this->dispatchAlbumList($data, $tagOptions);
		}
	
		// if there's still no output there was probably something wrong with the specified album name
		// (or there are simply no albums yet)
		if($html == '') {
			return 'picasaView plugin: ' . ($tagOption['album'] == ''
											? 'there are no albums available for this user in picasaweb.'
											: 'the album <strong>'.$tagOption['album'].'</strong> does not exist.');
		}

		return $html;		
	}
	
	/**
	 * show all available albums
	 * 
	 * @return 
	 * @param object $data
	 * @param object $tagOptions
	 */
	private function dispatchAlbumList($data, $tagOptions) {
		
		$template = $this->getTemplate('album');
		
		$namespaces = $data->getNamespaces(true);
		
		$html = '';
		
		foreach($data->entry as $e) {

			$gPhoto = $e->children($namespaces['gphoto']);
			
		// print all albums or only the selected one
			if($tagOptions['album'] == '' || in_array($tagOptions['album'], array($e->title, (string)$gPhoto->name))) {

				// get all necessary information for display
				foreach($e->children($namespaces['media']) as $mediaChildren) {
					$imageAttributes = $mediaChildren->thumbnail->attributes();
					$thumbnail = $imageAttributes['url'];
				}

				$numberOfPhotos = $gPhoto->numphotos;
				$albumLocation = $gPhoto->location;
				$albumComments = $gPhoto->commentCount;

				// generate Link for Album-Detail-View
				$query = parse_url(get_permalink(), PHP_URL_QUERY);
				
				// url param settings for picasaweb
				$settings = array(
							'picasaViewAlbumId'	=> (string) $gPhoto->name  . ',' . $this->postIndex
								);
								
				// generate HTML-code
				$html .= $this->replaceMultiple($template, array(
							'%ALBUMTITLE%' 				=> htmlentities($e->title, ENT_QUOTES, get_option('blog_charset')),
							'%ALBUMLINK%'				=> get_permalink() . ($query == '' ? '?' : '&') . http_build_query($settings),
							'%CREATIONDATE%'	 		=> strftime($tagOptions['datetimeformat'], strtotime($e->published)),
							'%MODIFICATIONDATE%' 		=> strftime($tagOptions['datetimeformat'], strtotime($e->updated)), 
							'%THUMBNAILPATH%'			=> $thumbnail,
							'%ALBUMSUMMARY%'			=> htmlentities($e->summary, ENT_QUOTES, get_option('blog_charset')),
							'%TOTAL_RESULTS%'			=> $numberOfPhotos,
							'%TOTAL_RESULTS_LABEL%'		=> __('Photos', 'picasaView')));


			}
		}		
		
		return $html;
		
	}
	
	/**
	 * show all images of an album
	 * 
	 * @return 
	 * @param object $data
	 * @param object $tagOptions
	 * @param int $startIndex
	 */
	private function dispatchAlbum($data, $tagOptions, $startIndex = 1) {
		
		$directView = isset($tagOptions['instantview']);
		
		// get Namespaces because SimpleXML needs the Namespace-Pathes for proper selection
		$namespaces = $data->getNamespaces(true);
		
		$template 	= $this->getTemplate('albumDetails');
	
		$albumInfo = array('header' => $this->getTemplate('albumDetailsHeader'), 'footer' => $this->getTemplate('albumDetailsFooter'));
		
		// get album details
		$openSearch = $data->children($namespaces['openSearch']);
		$totalImages = intval($openSearch->totalResults[0]);
		
		$gphoto = $data->entry->children($namespaces['gphoto']);
		
		// links to previous and next page
		$query = parse_url(get_permalink(), PHP_URL_QUERY);
		
		$previousIndex = $startIndex - $tagOptions['imagesperpage'];
		$nextIndex = $startIndex + $tagOptions['imagesperpage'];
		
		$settings = array('picasaViewAlbumId' => $tagOptions['album'] . ',' . $this->postIndex);
							
		$linkBase = get_permalink() . ($query == '' ? '?' : '&') . http_build_query($settings) . '&startIndex=';
		
	
		// quick paging always outputs the previous/next links. if it's disabled, we'll check if they should
		// be hidden
		// links might be displayed if imagesPerPage is > 0
		$showPreviousLink = $showNextLink = false;
		
		if ($tagOptions['imagesperpage'] > 0) {
			if ($tagOptions['quickpaging'] || $nextIndex < $totalImages) {
				$showNextLink = true;
			}
			if ($previousIndex > 0) {
				$showPreviousLink = true;
			}
		}
		
		// if there is no previous or no next page hide these blocks
		if (! $showPreviousLink && ! $tagOptions['quickpaging']) {
			$albumInfo = $this->replacePlaceholder($albumInfo, '%IF_PREVIOUS_PAGE%', '%ENDIF_PREVIOUS_PAGE%');
		}

		if (! $showNextLink && ! $tagOptions['quickpaging']) {
			$albumInfo = $this->replacePlaceholder($albumInfo, '%IF_NEXT_PAGE%', '%ENDIF_NEXT_PAGE%');
		}
	
		$endIndex = $nextIndex - 1;
		if($endIndex > $totalImages || $endIndex == 0) {
			$endIndex = $totalImages;
		}
	
		//if there's no location hide it
		if(trim($gphoto->location) == '') {
			$albumInfo = $this->replacePlaceholder($albumInfo, '%IF_LOCATION%', '%ENDIF_LOCATION%');
		}
		
		// if the album is shown directly, hide it
		if(!isset($_GET['picasaViewAlbumId'])) {
			$albumInfo = $this->replacePlaceholder($albumInfo, '%IF_BACKTOPOST%', '%ENDIF_BACKTOPOST%');
		}	

		// generate HTML-code (header & footer)
		$albumInfo = $this->replaceMultiple($albumInfo, array(
											'%ALBUMTITLE%' 				=> htmlentities($data->title, ENT_QUOTES, get_option('blog_charset')),
											'%ALBUMSUMMARY%' 			=> htmlentities($data->subtitle, ENT_QUOTES, get_option('blog_charset')),
											'%MODIFICATIONDATE%' 		=> $data->timestamp,
											'%LOCATION%'				=> htmlentities($gphoto->location, ENT_QUOTES, get_option('blog_charset')),
											'%LOCATION_LABEL%' 			=> __('Location', 'picasaView'),
											'%PREVIOUS_PAGE_LABEL%'		=> __('Previous', 'picasaView'),
											'%PREVIOUS_PAGE_LINK%' 		=> $showPreviousLink ? $linkBase . $previousIndex : '#',
											'%NEXT_PAGE_LABEL%'			=> __('Next', 'picasaView'),
											'%NEXT_PAGE_LINK%'			=> $showNextLink ? $linkBase . $nextIndex : '#',
											'%IF_PREVIOUS_PAGE%'		=> '',
											'%ENDIF_PREVIOUS_PAGE%'		=> '',
											'%IF_NEXT_PAGE%'			=> '',
											'%ENDIF_NEXT_PAGE%'			=> '',
											'%SHOWING_RESULTS_LABEL%'	=> __('Viewing images', 'picasaView'),
											'%SHOWING_RESULTS%'			=> $startIndex.'-'.$endIndex,
											'%TOTAL_RESULTS_LABEL%'		=> __('of', 'picasaView'),
											'%TOTAL_RESULTS%'			=> $totalImages,
											'%IF_LOCATION%'				=> '',
											'%ENDIF_LOCATION%'			=> '',
											'%IF_BACKTOPOST%'			=> '',
											'%ENDIF_BACKTOPOST%'		=> '',
											'%BACKTOPOST_LABEL%'		=> __('Back to post', 'picasaView'),
											'%BACKTOPOST_LINK%'			=> get_permalink()
										));
	
		$idx = 1;
	
		// echo	HTML-code
		$html = '<div class="picasaViewBlock-daddy" id="picasaViewBlock-daddy-'.$this->foundPlaceholders.'">';
		$html .= $albumInfo['header'] . '<div class="picasaViewBlock-son">';
		
		// list all images
		foreach($data->entry as $key => $e) {
			
			$gPhoto = $e->children($namespaces['gphoto']);
	
			// get all necessary information for display
			foreach($e->children($namespaces['media']) as $mediaChildren) {
				$imageAttributes = $mediaChildren->thumbnail->attributes();
				$thumbnail = $imageAttributes['url'];
				
				$imageAttributes = $mediaChildren->content->attributes();
				$imageUrl = $imageAttributes['url'];
			}
			
			$parts = pathinfo($imageUrl);
			$imagePath = $parts['dirname'] . '/s' . $tagOptions['imagesize'] . '/' . $parts['basename'];
	
			// only display currently shown images, hide others when the option quickPaging is enabled
			$isVisible = $idx <= $tagOptions['imagesperpage'];
			// echo HTML-code
			
			// put the echoed images into a container for paging
			if ($tagOptions['imagesperpage'] > 0 && ($idx % $tagOptions['imagesperpage'] == 1 || $tagOptions['imagesperpage'] == 1)) {
				$html .= '<div id="picasaViewBlock-'.$this->foundPlaceholders.'-'.$idx.'" '.($isVisible ? '' : 'style="display:none"').'>';
			}
			
			$html .= $this->replaceMultiple($template, array(
										'%IMAGEID%'				=> $gPhoto->id,
										'%ALBUMID%'				=> $tagOptions['album'],
										'%IMAGEDESCRIPTION%' 	=> htmlentities($e->summary, ENT_QUOTES, get_option('blog_charset')),
										'%IMAGEPATH%'			=> $imagePath,
										'%IMAGETITLE%'			=> htmlentities($e->title, ENT_QUOTES, get_option('blog_charset')),
										'%THUMBNAILPATH%'		=> $thumbnail,
										'%INDEX%'				=> $startIndex + $idx
										//'%MODIFICATIONDATE%'	=> date( 'd.m.Y', intval((string) $gPhoto->timestamp) )
									));
	
			if($tagOptions['imagesperpage'] > 0 &&
				($idx % $tagOptions['imagesperpage'] == 0 || $idx == count($data->entry))) {
					$html .= '</div>';
			}
	
			$idx++;
		}
	
		$html .= '</div>' . $albumInfo['footer'] . '</div>';	
			
		return $html;
	}
	
	private function replaceMultiple($subject, $replacements) {
		return str_replace(array_keys($replacements), array_values($replacements), $subject);
	}
	
	private function replacePlaceholder($template, $first, $last) {
	
		$returnAsArray = true;
	
		if(!is_array($template)) {
			$template = array($template);
			$returnAsArray = false;
		}
	
		foreach($template as $key => $value) {
			$start  = strpos($value, $first);
			$length = strpos($value, $last, $start) + strlen($last);
			$template[$key] = ($start === false || $length === false) ? $value : substr_replace($value, '', $start, ($length - $start));
		}
	
		return $returnAsArray ? $template : $template[0];
	
	}	
	
	private function fetchDataFromPicasaweb($uri, $tagOptions) {
	
		$error = '';
		
		$feed = wp_remote_retrieve_body(wp_remote_get($uri));
		
		if($feed == '') {
			$error = 'The connection could not be established.';
		}
		
		if($error == '' && !(@$xmlStruct = simplexml_load_string($feed))) {
			$error = 'Could not load data from picasaweb. Maybe the user or specified album does not exist?';
		}
	
		if($error != '') {
			$error .= '<br />This was the generated url which failed at picasaweb: <code>'.$uri.'</code><br />It returned the following data:<br /><code>'.$feed.'</code>';
		}
		
		if(!$tagOptions['showdebugoutput'] && $error != '') {
			$error = 'Could not load data from picasaweb.';
		}
	
		// wrong call to RSS
		if($error != '') {
			return array(false, 'picasaView plugin: ' . $error);
		}	
	
		return array($xmlStruct, false);
	}	
	
	public function adminMenu() {
		add_options_page('PicasaView Options', 'PicasaView', 8, basename(__FILE__), array(&$this, 'adminMenuOptions'));
	}
	
	public function adminMenuOptions() {
		
		// check, if fsockopen is able to connect - we will use google for that check
		$fsockopenWorks = @fsockopen('www.google.com', 80, $errnovnc, $errstrvnc, 5);		

		require dirname(__FILE__) . '/admintemplates/admin-menu-options.phtml';
	}
	
	public function adminHeader() {
		wp_enqueue_script('picasaview-admin', plugins_url('picasaview-admin.js', __FILE__), array('scriptaculous'), '1.0');
	}
	
	public function frontendHeader() {

		$customCss = TEMPLATEPATH . '/picasaview/picasaview_styles.css';
		$defaultCss = dirname(__FILE__) . '/templates/picasaview_styles.css';
	
		if(file_exists($customCss) && is_readable($customCss)) {
			$cssPath = get_bloginfo('template_url') . '/picasaview/picasaview_styles.css';
		} elseif(file_exists($defaultCss) && is_readable($defaultCss)) {
			$cssPath = WP_PLUGIN_URL . '/picasaview/templates/picasaview_styles.css';
		}
	
		if($cssPath) {
			echo('<link rel="stylesheet" href="'.$cssPath.'" type="text/css" media="screen" />');
		}
	
	}
	
	public function loadScripts() {
		if($this->options['quickpaging']) {
			wp_enqueue_script('picasaview', plugins_url('picasaview.js', __FILE__), array('jquery'), '1.1.5');
		}
	}	
	
}

$picasaView = new picasaView();