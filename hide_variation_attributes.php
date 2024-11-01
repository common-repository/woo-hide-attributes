<?php
/*
Plugin Name: Woocommerce Hide attributes
Plugin URI: https://websitedevelopers.de/
Description: Hide attributes for variable products from frontend
Author: Swapnita Alpha Beta
Version: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

function wpalpha_enqueue_admin_script(){
	wp_enqueue_script( 'bootstrap.min.js', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js', array('jquery'), '', true );
	wp_enqueue_script( 'prettify.min.js', plugin_dir_url( __FILE__ ) . 'js/prettify.min.js', array('jquery'), '', true );
	wp_enqueue_script( 'multiselect.js', plugin_dir_url( __FILE__ ) . 'js/multiselect.js', array('jquery'), '', true );
	wp_register_script( 'submitForms.js', plugin_dir_url( __FILE__ ) . 'js/form_submission.js', array('jquery'), '', true );
	wp_localize_script('submitForms.js', 'object', array('ajaxurl' => admin_url('admin-ajax.php')));    
    wp_enqueue_script('submitForms.js'); 

	wp_enqueue_style( 'bootstrap.min.css', plugin_dir_url( __FILE__ ) . 'css/bootstrap.min.css');
	wp_enqueue_style( 'prettify.css', plugin_dir_url( __FILE__ ) . 'css/prettify.css');
	wp_enqueue_style( 'style.css', plugin_dir_url( __FILE__ ) . 'css/style.css');

}

// This function is only called when our plugin's page loads!
function wpalpha_load_admin_js_css(){
    add_action( 'admin_enqueue_scripts', 'wpalpha_enqueue_admin_script' );
}

//added new setting option inside woocommerce menu
add_action('admin_menu', 'wpalpha_register_hide_attributes_submenu_page');

function wpalpha_register_hide_attributes_submenu_page() {
    $wpalpha_hide_attribute_page = add_submenu_page( 'woocommerce', 'Hide Attributes', 'Hide Attributes', 'manage_options', 'hide-attributes', 'wpalpha_hide_attributes' ); 
    // Load the JS conditionally
    add_action( 'load-' . $wpalpha_hide_attribute_page, 'wpalpha_load_admin_js_css' );
}

function wpalpha_hide_attributes() {
    echo '<h3>Hide Attributes</h3>'; 
    global $wpdb; 
	//get product attributes
	$attributes =  wc_get_attribute_taxonomies();
	
	//get custom attribute names by custom query
	$attribute_taxonomies = $wpdb->get_results( "SELECT distinct(meta_key) FROM " . $wpdb->prefix . "postmeta WHERE meta_key LIKE 'attribute_%';" );

	//merge two array products attributes and custom attributes
	$attributes_array = array_merge($attributes,$attribute_taxonomies); 

	/*converted std object to array*/
    $arrayAttributes = json_decode(json_encode($attributes_array),true);

    //get previous selected hidden attributes
    $frontend_hidden_attributes = get_option('frontend_hidden_attributes');

    $attr_option_arr = unserialize($frontend_hidden_attributes);

    //convert simple array to multidimensional with meta_key as key
	foreach ($attr_option_arr as $i => $value) { 
        $attr_option_arr_meta_key[]['meta_key'] = $value;
	}

	//remove duplicate values from all custom and product attributes
	foreach($arrayAttributes as $newmemberId => $newmember) {
	    foreach($attr_option_arr_meta_key as $oldmember) {
	        if($oldmember['meta_key'] == $newmember['meta_key']) {
	            unset($arrayAttributes[$newmemberId]);
	            break;
	        }
	    }
	}

    ?>
    
	<div class="row">
    	<div class="col-sm-5"></div>
        
        <div class="col-sm-2">
            <div id='loadingmessage' style='display:none'>
				<img src='<?php echo plugin_dir_url( __FILE__ );?>images/loading.gif' />
			</div>
        </div>
        
        <div class="col-sm-5"></div>
        
    </div>
    <form id="hide_attributes_form" class="hide_attributes_form" method="post">
    <div class="row">
        <div class="col-sm-5">
            <select name="from[]" id="multiselect1" class="form-control" size="8" multiple="multiple">
               <?php
                $i=1;
                foreach ($arrayAttributes as $key => $value) {
                	//print_r($value['meta_key']);
                	echo '<option value="'.$value["meta_key"].'" data-position="'.$i.'">'.$value["meta_key"].'</option>';
                	$i++;
                }
                ?>
            </select>
        </div>
        
        <div class="col-sm-2">
            <button type="button" id="multiselect1_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
            <button type="button" id="multiselect1_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
            <button type="button" id="multiselect1_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
            <button type="button" id="multiselect1_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
        </div>
        
        <div class="col-sm-5">
            <select name="to[]" id="multiselect1_to" class="form-control" size="8" multiple="multiple">
            	<?php
            	if(!empty($attr_option_arr_meta_key)){
            		$j=1;
	                foreach ($attr_option_arr_meta_key as $key1 => $meta_value) {
	                	//print_r($value['meta_key']);
	                	echo '<option value="'.$meta_value["meta_key"].'" data-position="'.$j.'">'.$meta_value["meta_key"].'</option>';
	                	$j++;
	                }
            	}
            	?>
            </select>
        </div>
    </div>

    <div class="row">
    	<div class="col-sm-5"></div>
        
        <div class="col-sm-2">
            <input type="submit" name="add_attributes" value="Save" id="submit_attributes">
        </div>
        
        <div class="col-sm-5"></div>
        
    </div>
    </form>

   <?php
}

/* ajax action tosave selected attributes in db which you want to hide from frontend */
add_action( 'wp_ajax_save_sel_hidden_attributes', 'save_sel_hidden_attributes' ); 
function save_sel_hidden_attributes(){ 
    global $wpdb; 
    $attr = $_POST['attr']; //post value
    $attr_option = serialize($attr);
    update_option('frontend_hidden_attributes',$attr_option);
    echo "updated";
    die;
}

/* hook to hide attributes from frontend */
function my_attribute_hider ( $attributes ) {
	$frontend_hidden_attributes = get_option('frontend_hidden_attributes');
    $attr_option_arr = unserialize($frontend_hidden_attributes);
    if( is_product() ){ //hide only from product page 
    	foreach ($attr_option_arr as $key => $value) {
    		$attribute_name = str_replace('attribute_', '', $value);
    		if ( isset( $attributes[$attribute_name] ) ){
	            unset( $attributes[$attribute_name] );
	        }
    	}
    }
    return $attributes;
}
add_filter( 'woocommerce_get_product_attributes', 'my_attribute_hider' );

?>