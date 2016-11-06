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
      // Register actions
      //add_action('admin_init', [$this, 'admin_init']);
      //add_action('admin_menu', [$this, 'add_menu']);

      // Register filters
      add_filter('default_content', [$this,'content_template']);
      add_filter('default_title', [$this,'title_template']);
      add_filter('default_excerpt', [$this,'excerpt_template']);
      
      // Register shortcode
      add_shortcode('new_post', [$this,'new_post_template_link']);
    }
    /**
     * Create a [new_post] short code that create a link that can be
     * used to create a new post from an existing post template.
     */
    public function new_post_template_link($atts,$content='') {
      extract( shortcode_atts( array(
	'id' => '',
	'noanchor' => '',
	'title' => '',
	'text' => '',
	'class' => '',
      ), $atts ) );
      if (empty($id)) {
	global $post;
	$id = $post->ID;
      }
      $link = admin_url('post-new.php?id='.$id);
      if ('yes' == $noanchor) return $link;
      if (empty($title)) $title = sprintf('New post using "%s" template', get_the_title($id));
      if (empty($content)) $content = 'New Post';
      return sprintf('<a href="%s" title="%s"%s>%s</a>',
		      $link, $title,
		      empty($class) ? '' : ' class="'.$class.'"',
		      $content);
    }
    public function content_template($content,$post) {
      return print_r($post,TRUE);
    }
    public function title_template($title,$post) {
      return 'MY TITLE';
    }
    public function excerpt_template($excerpt,$post) {
      return 'MY EXCERPT';
    }

    /*********************** FROM TEMPLATE ************************/
    public function admin_init() {
      $this->init_settings();
    }
    public function init_settings() {
      // register the settings for this plugin
      register_setting('auto-content-group', 'AUTO_CONTENT_CATEGORY');
    }
    /**
     * add a menu
     */     
    public function add_menu() {
      add_options_page('Auto Content Settings', 'auto-content', 'manage_options', 'auto-content', [&$this, 'plugin_settings_page']);
    }

    /**
     * Menu Callback
     */     
    public function plugin_settings_page() {
      if(!current_user_can('manage_options')) {
	wp_die(__('You do not have sufficient permissions to access this page.'));
      }

      // Render the settings template
      include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
    }
  }
}
if (class_exists('AutoContent')) {
  $auto_content = new AutoContent();
}

