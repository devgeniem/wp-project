<?php
/**
 * Plugin Name: Remove accents on upload
 * Description: Sanitize accents from Cyrillic, German, French, Polish, Spanish, Hungarian, Czech, Greek, Swedish during upload. Also fix OS-X NFD filenames.
 * Version: 1.0
 * Author: Onni Hakala
 *
 * Author URI: http://github.com/onnimonni
 * License: GPLv3
 */

if( ! function_exists( 'onnimonni_sanitize_filenames_on_upload' ) ) :
  function onnimonni_sanitize_filenames_on_upload( $filename ) {

    // Normalize NFD characters if possible
    if(class_exists('Normalizer')) {
      $filename = Normalizer::normalize($filename);
    }

    // Remove accents
    $filename = remove_accents($filename);

    // filename to lowercase for better urls
    return strtolower( $filename );
  }
  add_filter( 'sanitize_file_name', 'onnimonni_sanitize_filenames_on_upload', 10 );
endif;