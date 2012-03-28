<?php
/*
Plugin Name: oEmbedProvider
Plugin URI: https://github.com/pfefferle/oEmbedProvider
Description: An oEmbed provider for Wordpress
Version: 1.0
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
*/

/*
Based on the plugin of Craig Andrews <candrews@integralblue.com> http://candrews.integralblue.com
*/

add_action('wp_head', array('OembedProvider', 'add_oembed_links'));
add_action('parse_query', array('OembedProvider', 'print_oembed_file'));
add_filter('query_vars', array('OembedProvider', 'queryvars'));

class OembedProvider {
  function add_oembed_links(){
    if(is_single() || is_page() || is_attachment()){
      print '<link rel="alternate" type="application/json+oembed" href="' . site_url('/') . '?format=json&amp;oembed=' . urlencode(get_permalink())  . '" />';
      print '<link rel="alternate" type="application/xml+oembed" href="' . site_url('/') . '?format=xml&amp;oembed=' . urlencode(get_permalink())  . '" />';
    }
  }
	
	function queryvars($queryvars) {
		$queryvars[] = 'oembed';
		$queryvars[] = 'format';
	  return $queryvars;
	}
	
	function print_oembed_file($wp_query) {
		if (!isset($wp_query->query_vars['oembed'])) {
	    return;
	  }

		$post_ID = url_to_postid($wp_query->query_vars['oembed']);
	  $post = get_post($post_ID);
	  if(empty($post)) {
	    header('Status: 404');
	    die("Not found");
	  } else {
	    $author = get_userdata($post->post_author);
	    $oembed=array();
	    $oembed['version']='1.0';
	    $oembed['provider_name']=get_option('blogname');
	    $oembed['provider_url']=get_option('home');
	    $oembed['author_name']=$author->display_name;
	    $oembed['author_url']=get_author_posts_url($author->ID, $author->nicename);
	    $oembed['title']=$post->post_title;

	    switch(get_post_type($post)) {
	      case 'attachment':
	        if(substr($post->post_mime_type,0,strlen('image/'))=='image/'){
	          $oembed['type']='photo';
	        } else {
	          $oembed['type']='link';
	        }
	        $oembed['url']=wp_get_attachment_url($post->ID);
	        break;
	      case 'post':
	      case 'page':
	        if (function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
						$image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID));
						$oembed['thumbnail_url'] = $image[0];
						$oembed['thumbnail_width'] = $image[1];
						$oembed['thumbnail_height'] = $image[2];
					}
	        $oembed['type']='rich';
	        $oembed['html']=empty($post->post_excerpt)?$post->post_content:$post->post_excerpt;
	        break;
	      default:
	        header('Status: 501');
	        die('oEmbed not supported for posts of type \'' . $post->type . '\'');
	        break;
	      }

	      $format = $_GET['format'];
	      switch($format){
	        case 'xml':
	          header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
            print '<?xml version="1.0" encoding="' . get_option('blog_charset') . '" standalone="yes"?>';
	          print '<oembed>';
	          foreach(array_keys($oembed) as $element){
	            print '<' . $element . '>' . htmlspecialchars($oembed[$element]) . '</' . $element . '>';
	          }
	          print '</oembed>';
	          break;
	        case 'json':
				  default:
		        header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
		        $callback = $_GET['callback'];
		        if($callback){
		          print $callback . '(';
		        }
		        print(json_encode($oembed));
		        if($callback){
		          print ');';
		        }
		        break;
	            
	          header('Status: 501');
	          die('Format \'' . $format . '\' not supported');
	        }
	    }
		exit;
	}
}
?>