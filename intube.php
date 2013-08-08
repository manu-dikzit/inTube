<?php
/*
Plugin Name: InTube
Plugin URI: https://github.com/fyaconiello/intube
Description: A simple wordpress plugin template
Version: 1.0
Author: Abhimanyu Dikshit
Author URI: https://github.com/manu-dikzit/
License: GPL2
*/
/*
Copyright 2012  Abhimanyu Dikshit (email : abhimanyu@vozeal.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!class_exists('InTube'))
{
  class InTube
	{
    public static $optembedwidth = 640;
    public static $optembedheight = 390;
    public static $defaultheight = 640;
    public static $defaultwidth = 390;
    public static $ytregex = '@^\s*https?://(?:www\.)?(?:youtube.com/watch\?|youtu.be/)([^\s"]+)\s*$@im';
    public static $videoCount  = 0;
    public static $videoArray = array();
    /**
		 * Construct the plugin object
		 */
		public function __construct()
		{
        	// Initialize Settings
            require_once(sprintf("%s/settings.php", dirname(__FILE__)));
            $InTube_Settings = new InTube_Settings();
         // Call main function
            //add_action('admin_init',array($this,'onActivate'));
            register_activation_hook(__FILE__, array('InTube','onActivate' ));
            add_filter('the_content', 'InTube::youtube_non_oembed', 1);
            wp_embed_register_handler('intube_embed', self::$ytregex, 'InTube::youtube_embed_handler', 1);
		} // END public function __construct
	 public function onActivate() {
     $st = InTube::video_sitemap_loop();
     wp_enqueue_script( 'jquery');
     error_log("onActivate ".$st);
   } 
   public function youtube_non_oembed($content) {
      if (strpos($content, 'httpv://') !== false)
        {
            $findv = '@^\s*http[vh]://(?:www\.)?(?:youtube.com/watch\?|youtu.be/)([^\s"]+)\s*$@im';
            $content = preg_replace_callback($findv, "InTube::httpv_convert", $content);
        }
        return $content;
   }
   public static function httpv_convert($m)
    {
        return self::youtube_embed_handler($m, '', $m[0], '');
    }
   public static function get_aspect_height($url)
    {

        // attempt to get aspect ratio correct height from oEmbed
        $aspectheight = round((self::$defaultwidth * 9) / 16, 0);
        if ($url)
        {
            require_once( ABSPATH . WPINC . '/class-oembed.php' );
            $oembed = _wp_oembed_get_object();
            $args = array();
            $args['width'] = self::$defaultwidth;
            $args['height'] = self::$optembedheight;
            $args['discover'] = false;
            $odata = $oembed->fetch('http://www.youtube.com/oembed', $url, $args);

            if ($odata)
            {
                $aspectheight = $odata->height;
            }
        }

        //add 30 for YouTube's own bar
        return $aspectheight + 30;
    }
   public static function init_dimensions($url = null)
    {

        // get default dimensions; try embed size in settings, then try theme's content width, then just 480px
        if (self::$defaultwidth == null)
        {
            self::$optembedwidth = intval(get_option('embed_size_w'));
            self::$optembedheight = intval(get_option('embed_size_h'));

            global $content_width;
            if (empty($content_width))
                $content_width = $GLOBALS['content_width'];

            self::$defaultwidth = self::$optembedwidth ? self::$optembedwidth : ($content_width ? $content_width : 480);
            self::$defaultheight = self::get_aspect_height($url);
        }
    }
   public function youtube_embed_handler($matches, $attr, $url, $rawattr) {
        self::init_dimensions($url);

        $epreq = array(
            "height" => self::$defaultheight,
            "width" => self::$defaultwidth,
            "vars" => "",
            "standard" => "",
            "id" => "ep" . rand(10000, 99999)
        );

        $ytvars = array();
        $matches[1] = preg_replace('/&amp;/i', '&', $matches[1]);
        $ytvars = preg_split('/[&?]/i', $matches[1]);


        // extract youtube vars (special case for youtube id)
        $ytkvp = array();
        foreach ($ytvars as $k => $v)
        {
            $kvp = preg_split('/=/', $v);
            if (count($kvp) == 2)
            {
                $ytkvp[$kvp[0]] = $kvp[1];
            }
            else if (count($kvp) == 1 && $k == 0)
            {
                $ytkvp['v'] = $kvp[0];
            }
        }


        // setup variables for creating embed code
        $epreq['vars'] = 'ytid=';
        $epreq['standard'] = 'http://www.youtube.com/v/';
        if ($ytkvp['v'])
        {
            $epreq['vars'] .= strip_tags($ytkvp['v']) . '&amp;';
            $epreq['standard'] .= strip_tags($ytkvp['v']) . '?fs=1&amp;';
        }
        /* $realheight = intval($ytkvp['h'] ? $ytkvp['h'] : $epreq['height']);
        $epreq['vars'] .= 'height=' . $realheight . '&amp;';
        $epreq['height'] = $realheight;

        $realwidth = intval($ytkvp['w'] ? $ytkvp['w'] : $epreq['width']);
        $epreq['vars'] .= 'width=' . $realwidth . '&amp;';
        $epreq['width'] = $realwidth;



        $realstart = $ytkvp['start'] ? 'start=' . intval($ytkvp['start']) . '&amp;' : '';
        $epreq['vars'] .= $realstart;
        $epreq['standard'] .= $realstart;
*/

        //$epreq['vars'] .= 'rs=w&amp;';
        error_log("video id: ". $epreq['standard']);
        $ytidmatch = explode('/',$epreq['standard']);
        $yid = explode('?',$ytidmatch[4]);
        InTube::update_video_record($yid[0],"manyu",get_permalink(),FALSE);
        return InTube::embed_video($yid[0],InTube::$videoCount);
        //return self::get_embed_code($epreq);
   }

   function embed_video($vid,$count) {
     error_log("Embedding Video Id: ".$vid);
     wp_enqueue_script('jquery',"http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js");
     wp_enqueue_script('suggestions',plugins_url()."/intube/suggestions.js");
     wp_enqueue_script('videoControls',plugins_url().'/intube/ifameVideoControls.js');
     wp_enqueue_style('bootstrapCss','//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css');
     wp_enqueue_script('bootstrapJs','//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js');
     wp_enqueue_script('videoLoad',plugins_url().'/intube/videoControlsIntube.js');
     wp_enqueue_style('jqueryUiCss',"http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css");
     wp_enqueue_script('jqueryUiJs',"http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js");
     InTube::$videoArray[$count] = $vid;
     wp_localize_script('videoLoad','videosArray',InTube::$videoArray);

     $output = '
<div id="ytplayer'.$count.'" class="ytPlayer"></div>';
     if($count == 0){
       $output = $output.'<div id="suggestionsContainer'.$count.'" class = "suggestions" style="cursor:pointer">
         </div>';
     }
     InTube::$videoCount++;
     return $output;
   }
    /*public static function get_embed_code($incomingfromhandler)
    {
        $epheight = $incomingfromhandler['height'];
        $epwidth = $incomingfromhandler['width'];
        $epvars = $incomingfromhandler['vars'];
        $epobjid = $incomingfromhandler['id'];
        $epstandard = $incomingfromhandler['standard'];
        $epfullheight = null;
        $epobjid = htmlspecialchars($epobjid);

        if (is_numeric($epheight))
        {
            $epheight = (int) $epheight;
        }
        else
        {
            $epheight = $this->defaultheight;
        }
        $epfullheight = $epheight + 32;

        if (is_numeric($epwidth))
        {
            $epwidth = (int) $epwidth;
        }
        else
        {
            $epwidth = $this->defaultwidth;
        }

        $epvars = preg_replace('/\s/', '', $epvars);
        $epvars = preg_replace('/¬/', '&not', $epvars);

        if ($epstandard == "")
        {
            $epstandard = "http://www.youtube.com/embed/";
            $ytidmatch = array();
            preg_match('/ytid=([^&]+)&/i', $epvars, $ytidmatch);
            $epstandard .= $ytidmatch[1];
        }

        $epstandard = preg_replace('/\s/', '', $epstandard);

        $epstandard = preg_replace('/youtube.com\/v\//i', 'youtube.com/embed/', $epstandard);
        error_log($epstandard);
        $epoutputstandard = '<iframe class="cantembedplus" title="YouTube video player" width="~width" height="~height" src="~standard" frameborder="0" allowfullscreen></iframe>';


        $epoutput =
                '<object type="application/x-shockwave-flash" width="~width" height="~fullheight" data="http://getembedplus.com/embedplus.swf" id="' . $epobjid . '">' . chr(13) .
                '<param value="http://getembedplus.com/embedplus.swf" name="movie" />' . chr(13) .
                '<param value="high" name="quality" />' . chr(13) .
                '<param value="transparent" name="wmode" />' . chr(13) .
                '<param value="always" name="allowscriptaccess" />' . chr(13) .
                '<param value="true" name="allowFullScreen" />' . chr(13) .
                '<param name="flashvars" value="~vars&amp;rs=w" />' . chr(13) .
                $epoutputstandard . chr(13) .
                '</object>' . chr(13) .
                '<!--[if lte IE 6]> <style type="text/css">.cantembedplus{display:none;}</style><![endif]-->';

        $ua = $_SERVER['HTTP_USER_AGENT'];
        if (strlen($epvars) == 0 ||
                stripos($ua, 'iPhone') !== false ||
                stripos($ua, 'iPad') !== false ||
                stripos($ua, 'iPod') !== false)
        {// if no embedplus vars for some reason, or if iOS
            $epoutput = $epoutputstandard;
        }

        if (function_exists('wp_specialchars_decode'))
        {
            $epvars = wp_specialchars_decode($epvars);
            $epstandard = wp_specialchars_decode($epstandard);
        }
        else
        {
            $epvars = htmlspecialchars_decode($epvars);
            $epstandard = htmlspecialchars_decode($epstandard);
        }
        //strip tags
        $epvars = strip_tags($epvars);
        $epstandard = strip_tags($epstandard);

        $epoutput = str_replace('~height', $epheight, $epoutput);
        $epoutput = str_replace('~fullheight', $epfullheight, $epoutput);
        $epoutput = str_replace('~width', $epwidth, $epoutput);
        $epoutput = str_replace('~standard', $epstandard, $epoutput);
        $epoutput = str_replace('~vars', $epvars, $epoutput);

        // reset static vals for next embed
        self::$optembedwidth = null;
        self::$optembedheight = null;
        self::$defaultheight = null;
        self::$defaultwidth = null;
        error_log($epoutput);
        //send back text to calling function
        return $epoutput;
    }*/
   function video_EscapeXMLEntities($xml) {
      return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $xml);
    }

    private function update_video_record($yt_id,$user_id,$link,$is_channel) {
      $url = 'http://localhost/setSuggestions.php';
      $data = array('yt_id' => $yt_id , 'user_id' => $user_id, 'link' => $link,'is_channel' => $is_channel);

      // use key 'http' even if you send the request to https://...
       $options = array(
           'http' => array(
                   'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                           'method'  => 'POST',
                                   'content' => http_build_query($data),
                                       ),
                                       );
                                       $context  = stream_context_create($options);
                                       $result = file_get_contents($url, false, $context);
                                       error_log($result);

    }
    private function video_sitemap_loop() {
      global $wpdb;
      error_log("here");
      $posts = $wpdb->get_results ("SELECT id, post_title, post_content, post_date_gmt, post_excerpt 
      FROM $wpdb->posts WHERE post_status = 'publish' 
      AND (post_type = 'post' OR post_type = 'page')
      AND post_content LIKE '%youtube.com%' 
      ORDER BY post_date DESC");

      if (empty ($posts)) {
          error_log("Empty posts");
          return false;

      } else {

        $videos = array();
    
        foreach ($posts as $post) {
            $c = 0;
            if (preg_match_all ("/youtube.com\/(v\/|watch\?v=|embed\/)([a-zA-Z0-9\-_]*)/", $post->post_content, $matches, PREG_SET_ORDER)) {

                    $excerpt = ($post->post_excerpt != "") ? $post->post_excerpt : $post->post_title ; 
                    $permalink = InTube::video_EscapeXMLEntities(get_permalink($post->id)); 

                foreach ($matches as $match) {
                        $id = $match [2]; //youtube id 
                        $fix =  $c++==0?'':' [Video '. $c .'] ';
                        if (in_array($id, $videos))
                            continue;
                        array_push($videos, $id);
                        error_log("permalink :". $permalink);
                        error_log("id :".$id);
                        $uid = "manyu";
                        $is_channel = FALSE;
                        InTube::update_video_record($id,$uid,$permalink,$is_channel);
                        $thumbnail = "http://i.ytimg.com/vi/$id/hqdefault.jpg</video:thumbnail_loc>";
                        $title = htmlspecialchars($post->post_title) . $fix;
                        $description = $fix . htmlspecialchars($excerpt);
                        $pub_date = date (DATE_W3C, strtotime ($post->post_date_gmt));
                        $posttags = get_the_tags($post->id); if ($posttags) { 
                        $tagcount=0;
                        foreach ($posttags as $tag) {
                          if ($tagcount++ > 32) break;
                          $tags .= $tag->name;
                        }
                 }
                    $postcats = get_the_category($post->id);

                }
            }
        }

    }
  } //END function loop
		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
      
			// Do nothing
		} // END public static function activate
	
		/**
		 * Deactivate the plugin
		 */		
		public static function deactivate()
		{
			// Do nothing
		} // END public static function deactivate
 
	} // END class InTube
} // END if(!class_exists('InTube'))

if(class_exists('InTube'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('InTube', 'activate'));
	register_deactivation_hook(__FILE__, array('InTube', 'deactivate'));

	// instantiate the plugin class
	$intube = new InTube();
	
    // Add a link to the settings page onto the plugin page
    if(isset($intube))
    {
        // Add the settings link to the plugins page
        function plugin_settings_link($links)
        { 
            $settings_link = '<a href="options-general.php?page=intube">Settings</a>'; 
            array_unshift($links, $settings_link); 
            return $links; 
        }

        $plugin = plugin_basename(__FILE__); 
        add_filter("plugin_action_links_$plugin", 'plugin_settings_link');
    }
}
