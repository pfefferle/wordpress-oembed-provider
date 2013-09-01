<?php
/*
Plugin Name: oEmbed Provider
Plugin URI: http://wordpress.org/extend/plugins/oembed-provider/
Description: An oEmbed provider for Wordpress
Version: 2.0.1-dev
Author: pfefferle, candrews
Author URI: https://github.com/pfefferle/oEmbedProvider/
*/

/**
 * oEmbed Provider for WordPress
 *
 * @author Matthias Pfefferle
 * @author Craig Andrews
 */
class OembedProvider {
  
  /**
   * auto discovery links
   */
  function add_oembed_links(){
    if(is_singular()){
      echo '<link rel="alternate" type="application/json+oembed" href="' . site_url('/?oembed=true&amp;format=json&amp;url=' . urlencode(get_permalink())) . '" />'."\n";
      echo '<link rel="alternate" type="text/xml+oembed" href="' . site_url('/?oembed=true&amp;format=xml&amp;url=' . urlencode(get_permalink())) . '" />'."\n";
    }
  }

  /**
   * adds query vars
   */
  function query_vars($query_vars) {
    $query_vars[] = 'oembed';
    $query_vars[] = 'format';
    $query_vars[] = 'url';
    $query_vars[] = 'callback';

    return $query_vars;
  }
  
  /**
   * handles request
   */
  function parse_query($wp) {
    if (!array_key_exists('oembed', $wp->query_vars) ||
        !array_key_exists('url', $wp->query_vars)) {
      return;
    }

    $post_ID = url_to_postid($wp->query_vars['url']);
    $post = get_post($post_ID);
    
    if(!$post) {
      header('Status: 404');
      wp_die("Not found");
    }
    
    $post_type = get_post_type($post);
    
    // add support for alternate output formats
    $oembed_provider_formats = apply_filters("oembed_provider_formats", array('json', 'xml'));
    
    // check output format
    $format = "json";
    if (array_key_exists('format', $wp->query_vars) && in_array(strtolower($wp->query_vars['format']), $oembed_provider_formats)) {
      $format = $wp->query_vars['format'];
    }
    
    // content filter
    $oembed_provider_data = apply_filters("oembed_provider_data", array(), $post_type, $post);
    $oembed_provider_data = apply_filters("oembed_provider_data_{$post_type}", $oembed_provider_data, $post);
    
    do_action("oembed_provider_render", $format, $oembed_provider_data, $wp->query_vars);
    do_action("oembed_provider_render_{$format}", $oembed_provider_data, $wp->query_vars);
  }
  
  /**
   * adds default content
   *
   * @param array $oembed_provider_data
   * @param string $post_type
   * @param Object $post
   */
  function generate_default_content($oembed_provider_data, $post_type, $post) {
    $author = get_userdata($post->post_author);

    $oembed_provider_data['version'] = '1.0';
    $oembed_provider_data['provider_name'] = get_bloginfo('name');
    $oembed_provider_data['provider_url'] = home_url();
    $oembed_provider_data['author_name'] = $author->display_name;
    $oembed_provider_data['author_url'] = get_author_posts_url($author->ID, $author->nicename);
    $oembed_provider_data['title'] = $post->post_title;
    
    return $oembed_provider_data;
  }
  
  /**
   * adds attachement specific content
   *
   * @param array $oembed_provider_data
   * @param Object $post
   */
  function generate_attachment_content($oembed_provider_data, $post) {
    if (substr($post->post_mime_type,0,strlen('image/'))=='image/') {
      $oembed_provider_data['type']='photo';
    } else {
      $oembed_provider_data['type']='link';
    }
    $oembed_provider_data['url'] = wp_get_attachment_url($post->ID);
    
    return $oembed_provider_data;
  }
  
  /**
   * adds post/page specific content
   *
   * @param array $oembed_provider_data
   * @param Object $post
   */
  function generate_post_content($oembed_provider_data, $post) {
    if (function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
      $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID));
      $oembed_provider_data['thumbnail_url'] = $image[0];
      $oembed_provider_data['thumbnail_width'] = $image[1];
      $oembed_provider_data['thumbnail_height'] = $image[2];
    }
    $oembed_provider_data['type']='rich';
    $oembed_provider_data['html'] = empty($post->post_excerpt) ? $post->post_content : $post->post_excerpt;

    return $oembed_provider_data;
  }
  
  /**
   * render json output
   *
   * @param array $oembed_provider_data
   */
  function render_json($oembed_provider_data, $wp_query) {
    header('Content-Type: application/json; charset=' . get_bloginfo('charset'), true);
    
    // render json output
    $json = json_encode($oembed_provider_data);
    
    // add callback if available
    if (array_key_exists('callback', $wp_query)) {
      $json = $wp_query['callback'] . "($json);";
    }
    
    echo $json;
    exit;
  }
  
  /**
   * render xml output
   *
   * @param array $oembed_provider_data
   */
  function render_xml($oembed_provider_data) {
    header('Content-Type: text/xml; charset=' . get_bloginfo('charset'), true);
    
    // render xml-output
    echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '" ?>';
    echo '<oembed>';
    foreach(array_keys($oembed_provider_data) as $element){
      echo '<' . $element . '>' . htmlspecialchars($oembed_provider_data[$element]) . '</' . $element . '>';
    }
    echo '</oembed>';
    exit;
  }
}

add_action('wp_head', array('OembedProvider', 'add_oembed_links'));
add_action('parse_query', array('OembedProvider', 'parse_query'));
add_filter('query_vars', array('OembedProvider', 'query_vars'));
add_filter('oembed_provider_data', array('OembedProvider', 'generate_default_content'), 90, 3);
add_filter('oembed_provider_data_attachment', array('OembedProvider', 'generate_attachment_content'), 91, 2);
add_filter('oembed_provider_data_post', array('OembedProvider', 'generate_post_content'), 91, 2);
add_filter('oembed_provider_data_page', array('OembedProvider', 'generate_post_content'), 91, 2);
add_action('oembed_provider_render_json', array('OembedProvider', 'render_json'), 99, 2);
add_action('oembed_provider_render_xml', array('OembedProvider', 'render_xml'), 99);
