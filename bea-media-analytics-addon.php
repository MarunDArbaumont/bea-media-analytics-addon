<?php
/*
Plugin Name: BEA - Media Analytics Addon for media in css
Plugin URI: https://www.perfectogroupe.fr/
Description: BEA - Media Analytcs Addon that founds media in css
Author: Perfecto Tech
Version: 1.0
Author URI: https://www.perfectogroupe.fr/
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_action( 'admin_init', 'add_media_indexing_button' );


/**
 * Add option page for media index
 */
function add_media_indexing_button(): void {
    add_settings_section(
        'media_indexing',
    	( 'Media Indexing' ),
        '__return_null',
        'media'
    );

    add_settings_field(
        'media_indexing_button',
        ( 'Trigger indexing' ),
        static function() {
            if ( isset( $_GET['force_media_index'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
                BEA\Media_Analytics\Main::get_instance()->force_indexation();

                ?>
                <div class="notice notice-info notice-info-stronger"><p>Media index rebuilt successfully</p></div>
                <?php
            }

            ?>
            <a href="<?php echo esc_url( add_query_arg( 'force_media_index', 1, menu_page_url( 'options-media', false ) ) ); ?>" class="button">Force media index</a>
            <?php
        },
        'media',
        'media_indexing'
    );
}


function get_id_from_url( $attachment_url ) {
	global $wpdb;

	// If there is no url, return.
	if ( '' == $attachment_url ) {

		return 0;
	}

	// Get the upload directory paths
	$upload_dir_paths = wp_upload_dir();
	// If the attachement url contains -1600x900 because it has been resized -> to ignore it 
	if(str_contains($attachment_url, '-1600x900')){
		$attachment_url = preg_replace( '/\-\d+x\d+\.(jpe?g|png|gif|pdf)$/', '.$1', $attachment_url );
	}
	// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
	if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {
		
		// Returns the ID of the image 
		$attachment_url = substr($attachment_url, 7);
		$attachment_url = attachment_url_to_postid($attachment_url);
		return $attachment_url;
	}
	return 0;
}

// Found the media in url:https://...
function get_media_from_url($media_ids) {

	if ( empty( $media_ids ) ) {
		return [];
	}
	// match all url="" from img html
	preg_match_all('/"url":"https?:\/\/[^\s"]+\.(png|pdf|jpg|jpeg)/', $media_ids, $images);
	if ( empty( $images ) ) {
		return [];
	}

	$medias = array_map( 'get_id_from_url', $images[0] );
	return array_filter( $medias );
}

function get_media_from_urls( $media_ids, $post_content ) {
	return array_merge( $media_ids, get_media_from_url( $post_content ) );
}

// Indexation
add_filter( 'bea.media_analytics.helper.get_media.post_content', 'get_media_from_urls', 10, 2 );
