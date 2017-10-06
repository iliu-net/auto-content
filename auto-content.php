<?php
/*
Plugin Name: auto-content
Plugin URI: https://github.com/iliu-net/auto-content
Description: Pre-fill new post with text
Version: 1.0
Author: Alejandro Liu
Author URI: http://0ink.net
License: GPL2
*/
/*
Copyright 2016 Alejandro Liu (alejandro_liu@hotmail.com)

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

if (!class_exists('AutoContent')) {
  class AutoContent {
    public function __construct() {
      // Register filters
      add_filter('default_content', [$this,'content_template']);
      add_filter('default_title', [$this,'title_template']);
      add_filter('default_excerpt', [$this,'excerpt_template']);
      add_filter('wp_get_object_terms', [$this,'category_template'], 10, 4);

      // Register shortcode
      add_shortcode('new_post', [$this,'new_post_template_link']);
    }
    /**
     * Create a [new_post] short code that create a link that can be
     * used to create a new post from an existing post template.
     *
     * The following attributes are defined:
     *
     * - id : template post id.  Defaults to the current post.
     * - noanchor : if set to yes simply returns the URL
     * - title : anchor title
     * - class : class for the link
     *
     * Example:
     * - [new_post]Create post[/new_post]
     */
    public function new_post_template_link($atts,$content='') {
      extract( shortcode_atts( array(
	'id' => '',
	'noanchor' => '',
	'title' => '',
	'class' => '',
      ), $atts ) );
      if (empty($id)) {
	global $post;
	$id = $post->ID;
      }
      $link = admin_url('post-new.php?template_id='.$id);
      if ('yes' == $noanchor) return $link;
      if (empty($title)) $title = sprintf('New post using "%s" template', get_the_title($id));
      if (empty($content)) $content = 'New Post';
      return sprintf('<a href="%s" title="%s"%s>%s</a>',
		      $link, $title,
		      empty($class) ? '' : ' class="'.$class.'"',
		      do_shortcode($content));
    }
    public function get_template_post() {
      if (empty($_GET) || empty($_GET['template_id'])) return NULL;
      $id= $_GET['template_id'];
      $template = get_post($id);
      if ($template === NULL) return NULL;
      return $template;
    }
    public function expand_template($inp) {
      $out = '';
      $off = 0;
      while (preg_match('/\$[^$]+\$/',$inp, $mv, PREG_OFFSET_CAPTURE, $off)) {
	$out .= substr($inp,$off, $mv[0][1]-$off);
	$off = $mv[0][1]+strlen($mv[0][0]);

	$tag = $mv[0][0];
	if (!preg_match('/\$([A-Za-z]+)\$/',$tag,$mv) && !preg_match('/\$\s*([A-Za-z]+):\s*.*\$/',$tag,$mv)) {
	  $out .= $tag;
	  continue;
	}
	switch (strtolower($mv[1])) {
	case 'fdate':
	  $out .= '$FDate: '.date_i18n(get_option( 'date_format' ),time()).' $';
	  break;
	case 'date':
	  $out .= '$Date: '.date_i18n('Y-m-d',time()).' $';
	  break;
	case 'week':
	  $out .= '$Week: '.date_i18n('Y-W',time()).' $';
	  break;
	case 'datetime':
	  $out .= '$DateTime: '.current_time('mysql').' $';
	  break;
	case 'login':
	  $current_user = wp_get_current_user();
	  $out .= '$Login: '.$current_user->user_login.' $';
	  break;
	case 'email':
	  $current_user = wp_get_current_user();
	  $out .= '$EMail: '.$current_user->user_email.' $';
	  break;
	case 'user':
	  $current_user = wp_get_current_user();
	  $out .= '$User: '.$current_user->user_name.' $';
	  break;
	default:
	  $out .= $tag;
	}
      }
      $out .= substr($inp,$off);

      return $out;
    }
    public function content_template($content) {
      $template = $this->get_template_post();
      if ($template === NULL) return $content;
      if (empty($template->post_content)) return $content;
      return $this->expand_template($template->post_content);
    }
    public function title_template($title) {
      $template = $this->get_template_post();
      if ($template === NULL) return $content;
      if (empty($template->post_title)) return $content;

      return $this->expand_template($template->post_title);
    }
    public function excerpt_template($excerpt) {
      $template = $this->get_template_post();
      if ($template === NULL) return $content;
      if (empty($template->post_excerpt)) return $content;

      return $this->expand_template($template->post_excerpt);
    }
    public function category_template($terms, $object_ids, $taxonomies, $args) {
      if (empty($_GET) || empty($_GET['template_id'])) return $terms;
      $id= $_GET['template_id'];
      unset($_GET['template_id']);
      $cats = wp_get_post_categories($id,['fields'=>'ids']);
      $_GET['template_id'] = $id;
      return $cats;
    }
  }
}
if (class_exists('AutoContent')) {
  $auto_content = new AutoContent();
}
