<?php
/*
* Plugin Name: Bulk Images to Posts (Raya)
* Plugin URI: http://www.mezzaninegold.com
* Text Domain: bulk-images-to-posts-raya
* Domain Path: /lang
* Description: Adaptation of  Bulk Images to Posts Plugin (http://www.mezzaninegold.com) for Raya.
* Version: 1.0.0.0 (3.6.6.3)
* Author: Gassius (Based on work of Mezzanine gold)
* Author URI: http://carlos.gonzalezri.co
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
* Adding custom code for Raya
* Create custom post type
*/

add_action( 'init', 'bip_raya_custom_post' );

function bip_raya_custom_post() {

register_post_type( 'bulk_products', array(
  'labels' => array(
    'name' => 'Bulk Products',
    'singular_name' => 'Bulk Product',
   ),
  'description' => 'Products to be created by Bulk Image drop.',
  'public' => true,
  'menu_position' => 20,
  'supports' => array( 'title', 'editor', 'custom-fields' ),
	'taxonomies'  => array( 'category' ),
));
}

/**ADDS THE LAYOUT EDITOR TO CPT’s*/
add_filter('avf_builder_boxes','custom_post_types_options');

function custom_post_types_options($boxes)
{
  $boxes[] = array( 'title' =>__('Avia Layout Builder','avia_framework' ), 'id'=>'avia_builder', 'page'=>array('post', 'portfolio', 'bulk_products'), 'context'=>'normal', 'expandable'=>true );
  $boxes[] = array( 'title' =>__('Layout','avia_framework' ), 'id'=>'layout', 'page'=>array('post', 'portfolio', 'bulk_products'), 'context'=>'side', 'priority'=>'low');
  return $boxes;
}

/* title to get the post title  */
function myshortcode_title( ){
   return get_the_title();
}

/* Add shortcode */
add_shortcode('page_title', 'myshortcode_title');

/* Filter the single_template with our custom function
function get_custom_post_type_template($single_template) {
     global $post;

     if ($post->post_type == 'bulk_products') {
          $single_template = dirname( __FILE__ ) . '/single-bulk-products.php';
     }
     return $single_template;
}
add_filter( 'single_template', 'get_custom_post_type_template' );*/


// Add columns for the `posts` post type
add_filter('manage_posts_columns', 'add_posts_columns', 10, 2);
function add_posts_columns($posts_columns, $post_type)
{
    $posts_columns['Price'] = 'Price column';
    $posts_columns['Description'] = 'Description';
    $posts_columns['Iframe'] = 'Iframe';
    return $posts_columns;
}
// But remove them again on the edit screen (other screens to?)
add_filter('manage_edit-post_columns', 'remove_posts_columns');
function remove_posts_columns($posts_columns)
{
    unset($posts_columns['Price']);
    unset($posts_columns['Description']);
    unset($posts_columns['Iframe']);
    return $posts_columns;
}
add_action('manage_posts_custom_column', 'raya_render_post_columns', 10, 2);

function raya_render_post_columns($column_name, $id) {
    switch ($column_name) {
    case 'Price':
        $currentPrice = get_post_meta( $id, 'price', TRUE);
        if ($currentPrice)
            echo $currentPrice;
        else echo "0";
        break;
    case 'Description':
        $currentDesc = get_post_meta( $id, 'description', TRUE);
        if ($currentDesc)
            echo $currentDesc;
        else echo "--";
        break;
    case 'Iframe':
        $currentIframe = get_post_meta( $id, 'iframe', TRUE);
        if ($currentIframe)
            echo '<div class="iframeprew" style="width: 150px; height: 130px; position: relative; overflow: hidden;">'.$currentIframe.'</div>';
        else echo "--";
        break;
    }
}


// Add our text to the quick edit box
add_action('quick_edit_custom_box', 'on_quick_edit_custom_box', 10, 2);
function on_quick_edit_custom_box($column_name, $post_type)
{
    if ('Price' == $column_name) { ?>
      <fieldset class="inline-edit-col-left">
          <div class="inline-edit-col">
            <span class="title">Price</span>
            <input type="text" name="raya_widget_set_price" id="raya_widget_set_price" value="" />
          </div>
      </fieldset>
    <?php
    }
    if ('Description' == $column_name) {?>
      <fieldset class="inline-edit-col-left">
          <div class="inline-edit-col">
            <span class="title">Description</span>
            <input type="text" name="raya_widget_set_description" id="raya_widget_set_description" value="" />
          </div>
      </fieldset>
    <?php
    }
    if ('Iframe' == $column_name) {?>
      <fieldset class="inline-edit-col-left">
          <div class="inline-edit-col">
            <span class="title">Iframe</span>
            <input type="text" name="raya_widget_set_iframe" id="raya_widget_set_iframe" value="" />
          </div>
      </fieldset>
    <?php
    }
}

/*
* Populate the Quick Edit fields with current vairables. it requieres some JS
* load script in the footer
*/
if ( ! function_exists('wp_my_admin_enqueue_scripts') ):
function wp_my_admin_enqueue_scripts( $hook ) {
	if ( 'edit.php' === $hook &&
		isset( $_GET['post_type'] ) &&
		'bulk_products' === $_GET['post_type'] ) {
		wp_enqueue_script( 'my_custom_script', plugins_url('js/admin_edit.js', __FILE__),
			false, null, true );
	}
}
endif;
add_action( 'admin_enqueue_scripts', 'wp_my_admin_enqueue_scripts' );


add_action( 'save_post', 'save_bulk_products_meta' );
function save_bulk_products_meta( $post_id ) {
  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
  // to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
      return $post_id;
  // Check permissions
  if ( 'bulk_products' == $_POST['post_type'] ) {
      if ( !current_user_can( 'edit_page', $post_id ) )
          return $post_id;
  } else {
      if ( !current_user_can( 'edit_post', $post_id ) )
      return $post_id;
  }
  // OK, we're authenticated: we need to find and save the data
  if ( isset( $_POST['raya_widget_set_price'] ) ) {
      update_post_meta( $post_id, 'price', $_POST['raya_widget_set_price'] );
  }
  if ( isset( $_POST['raya_widget_set_description'] ) ) {
      update_post_meta( $post_id, 'description', $_POST['raya_widget_set_description'] );
  }
  if ( isset( $_POST['raya_widget_set_iframe'] ) ) {
      update_post_meta( $post_id, 'iframe', $_POST['raya_widget_set_iframe'] );
  }
}

// End custom code for Raya

add_action('plugins_loaded', 'bip_load_textdomain');
function bip_load_textdomain() {
	load_plugin_textdomain( 'bulk-images-to-posts-raya', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
}


require_once( 'includes/bip-category-walker.php' );
require_once( 'includes/bip-settings.php' );

add_action( 'admin_init', 'bip_admin_init' );

   function bip_admin_init() {
       /* Register our stylesheet and javascript. */
       wp_register_style( 'bip-css', plugins_url('css/style.css', __FILE__) );
       wp_register_script( 'bip-js', plugins_url('js/script.js', __FILE__), array( 'jquery' ), '', true );
       wp_register_script( 'dropzone-js', plugins_url('js/dropzone.js', __FILE__), array( 'jquery' ), '', true );
   }
   function bip_admin_styles() {
       wp_enqueue_style( 'bip-css' );
       wp_enqueue_script( 'bip-js' );
       wp_enqueue_script( 'dropzone-js' );
	   wp_enqueue_script( 'jquery-form' );
	   wp_enqueue_style( 'dashicons' );
   }

   function bip_admin_notice(){ ?>
    <div class="notice notice-error error is-dismissible">
        <p><?php _e( 'Bulk Images to Posts','bulk-images-to-posts') ?>: <a href="<?php echo site_url('wp-admin/admin.php?page=bip-settings-page'); ?>"><?php _e('Please update your settings before uploading!', 'bulk-images-to-posts' ); ?></a></p>
    </div>

<?php }
$bipUpdated = get_option('bip_updated');
if ( empty($bipUpdated) ) {
	add_action('admin_notices', 'bip_admin_notice');
}


// create plugin settings menu
add_action('admin_menu', 'bip_create_menu');


// Used in category walker
function bip_in_array_check($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && bip_in_array_check($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

function bip_create_menu() {

    // create new top-level menu
    global $bip_admin_page;
    $bip_admin_page = add_menu_page(__('Bulk Images to Posts Uploader','bulk-images-to-posts'), __('Bulk','bulk-images-to-posts'), 'manage_options', 'bulk-images-to-post','bip_upload_page','dashicons-images-alt2');
    // create submenu pages
    add_submenu_page( 'bulk-images-to-post', __('Bulk Images to Post - Upload','bulk-images-to-posts'), __('Uploader','bulk-images-to-posts'), 'manage_options', 'bulk-images-to-post');
	$bip_submenu_page = add_submenu_page( 'bulk-images-to-post', __('Bulk Images to Post - Settings','bulk-images-to-posts'), __('Settings','bulk-images-to-posts'), 'manage_options', 'bip-settings-page', 'bip_settings_page');
    // call register settings function
    add_action( 'admin_init', 'bip_register_settings' );
    // enqueue scripts
    add_action( 'admin_print_styles-' . $bip_admin_page, 'bip_admin_styles' );
    add_action( 'admin_print_styles-' . $bip_submenu_page, 'bip_admin_styles' );

}


	/*
	* Register Setting - Needs updating to an array of options.
	*/
function bip_register_settings() {
    register_setting( 'bip-upload-group', 'bip_terms' );
    register_setting( 'bip-settings-group', 'bip_updated' );
    register_setting( 'bip-settings-group', 'bip_post_type' );
    register_setting( 'bip-settings-group', 'bip_image_title' );
    register_setting( 'bip-settings-group', 'bip_post_status' );
    register_setting( 'bip-settings-group', 'bip_taxonomy' );
    register_setting( 'bip-settings-group', 'bip_image_content' );
    register_setting( 'bip-settings-group', 'bip_image_content_size' );
    register_setting( 'bip-settings-group', 'bip_image_feature' );
}

	/*
	* The main upload page
	*/
function bip_upload_page() { ?>

<div class="grid">
	<div class="whole unit">

	<h2><?php _e('Bulk Images to Posts - Uploader','bulk-images-to-posts'); ?></h2>
	<p><?php _e('Please use the settings page to configure your uploads','bulk-images-to-posts'); ?>
		<a href="<?php echo site_url('wp-admin/admin.php?page=bip-settings-page') ?>">
			<?php _e('Click here','bulk-images-to-posts'); ?>
		</a>
	</p>
	</div>
</div>
<div id="poststuff" class="grid">
        <div class="one-third unit">
			<form method="post" action="options.php" id="bip-upload-form">
			    <?php settings_fields( 'bip-upload-group' ); ?>
			    <?php do_settings_sections( 'bip-upload-group' ); ?>



				    <?php
$selected_taxs = get_option('bip_taxonomy');
if (!empty($selected_taxs)) {
foreach ($selected_taxs as $selected_tax) { ?>


					<?php
					$selected_cats = get_option('bip_terms');
				    $walker = new Walker_Bip_Terms( $selected_cats, $selected_tax ); ?>
				    <div class="postbox">
					  	<div title="Click to toggle" class="handlediv"><br></div>
					  	<h3 class="hndle"><span><?php echo $selected_tax ?></span></h3>
					    <div class="inside">
						    <div class="buttonbox">
						    <p class="uncheck"><input type="button" class="check button button-primary" value="Uncheck All" /></p>
						    <?php submit_button(); ?>
						    </div>
						    <div class="categorydiv">
							    <div class="tabs-panel">
								    <div class="checkbox-container">
									    <ul class="categorychecklist ">
											<?php
										    $args = array(
										    'descendants_and_self'  => 0,
										    'selected_cats'         => $selected_cats,
										    'popular_cats'          => false,
										    'walker'                => $walker,
										    'taxonomy'              => $selected_tax,
										    'checked_ontop'         => false ); ?>
											<?php wp_terms_checklist( 0, $args ); ?>
									    </ul>
								    </div>
							    </div>
						    </div>
					    </div>
				    </div>

				    <?php } } ?>

			</form>
			<div id="saveResult"></div>
</div>
<div class="two-thirds unit">
	<div class="postbox">
		<div title="Click to toggle" class="handlediv"><br></div><h3 class="hndle"><span><?php _e('Images','bulk-images-to-posts'); ?></span></h3>
			<?php include 'includes/bip-dropzone.php';?>
		</div>
	</div>
</div>
<?php } ?>
