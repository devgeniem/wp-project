<?php
/**
 * Plugin Name: Moved Uploads dir
 * Description: Plugin which adds handler for WP_UPLOADS_URL and WP_UPLOADS_DIR constants
 * Version: 1.0.0
 * Author: Onni Hakala
 * Author URI: https://github.com/onnimonni
 * License: MIT License
 */

namespace onnimonni\helper;

class Uploads {
  static function init(){
    add_filter( 'pre_option_upload_path', array(__CLASS__, 'change_upload_path') );
    add_filter( 'pre_option_upload_url_path', array(__CLASS__, 'change_upload_url_path') );
  }

  static function change_upload_path( $option ){
    if( defined('WP_UPLOADS_DIR') ) {
      return WP_UPLOADS_DIR;
    } else {
      return $option;
    }
  }

  static function change_upload_url_path( $option ){
    if( defined('WP_UPLOADS_URL') ) {
      return WP_UPLOADS_URL;
    } else {
      return $option;
    }
  }
}

Uploads::init();