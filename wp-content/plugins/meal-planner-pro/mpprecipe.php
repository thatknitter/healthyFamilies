<?php
/*
Plugin Name: MealPlannerPro Recipe Plugin
Plugin URI: http://www.mealplannerpro.com/recipe_plugin
Plugin GitHub: https://github.com/Ziplist/recipe_plugin
Description: A plugin that adds all the necessary microdata to your recipes, so they will show up in Google's Recipe Search
Version: 5.3
Author: MealPlannerPro.com
Author URI: http://www.mealplannerpro.com/
License: GPLv3 or later

Copyright 2011, 2012, 2013, 2014 MealPlannerPro, Inc.
This code is derived from the 1.3.1 build of RecipeSEO released by codeswan: http://sushiday.com/recipe-seo-plugin/ and licensed under GPLv2 or later
*/

/*
    This file is part of MealPlannerPro Recipe Plugin.

    MealPlannerPro Recipe Plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    MealPlannerPro Recipe Plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with MealPlannerPro Recipe Plugin. If not, see <http://www.gnu.org/licenses/>.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hey!  This is just a plugin, not much it can do when called directly.";
	exit;
}

if (!defined('MPPRECIPE_VERSION_KEY'))
    define('MPPRECIPE_VERSION_KEY', 'mpprecipe_version');

if (!defined('MPPRECIPE_VERSION_NUM'))
    define('MPPRECIPE_VERSION_NUM', '5.3');

if (!defined('MPPRECIPE_PLUGIN_DIRECTORY'))
    define('MPPRECIPE_PLUGIN_DIRECTORY', plugins_url() . '/' . dirname(plugin_basename(__FILE__)) . '/');


function strip( $i )
{
    // Strip JS, HTML, CSS, Comments
    $search = array(
        '@<script[^>]*?>.*?</script>@si', '@<[\/\!]*?[^<>]*?>@si',
        '@<style[^>]*?>.*?</style>@siU',  '@<![\s\S]*?--[ \t\n\r]*>@'
    );

    $o = preg_replace($search, '', $i);
    return $o;
}
function sanitize( $i )
{
    if (is_array($i)) 
    {
        foreach($i as $v=>$val)
            $o[$v] = sanitize($val);
    }
    else 
    {
        if (get_magic_quotes_gpc())
            $i = stripslashes($i);

        $i = strip($i);
        $o = mysql_escape_string($i);
    }
    return $o;
}

add_option(MPPRECIPE_VERSION_KEY, MPPRECIPE_VERSION_NUM);  // sort of useless as is never updated
add_option("mpprecipe_db_version"); // used to store DB version

add_option('mealplannerpro_partner_key', '');
add_option('mealplannerpro_recipe_button_hide', '');
add_option('mealplannerpro_attribution_hide', '');
add_option('mpprecipe_printed_permalink_hide', '');
add_option('mpprecipe_printed_copyright_statement', '');
add_option('mpprecipe_stylesheet', 'mpprecipe-std');
add_option('recipe_title_hide', '');
add_option('mpprecipe_image_hide', '');
add_option('mpprecipe_image_hide_print', 'Hide');
add_option('mpprecipe_print_link_hide', '');
add_option('mpprecipe_ingredient_label', 'Ingredients');
add_option('mpprecipe_ingredient_label_hide', '');
add_option('mpprecipe_ingredient_list_type', 'ul');
add_option('mpprecipe_instruction_label', 'Instructions');
add_option('mpprecipe_instruction_label_hide', '');
add_option('mpprecipe_instruction_list_type', 'ol');
add_option('mpprecipe_notes_label', 'Notes');
add_option('mpprecipe_notes_label_hide', '');
add_option('mpprecipe_prep_time_label', 'Prep Time');
add_option('mpprecipe_prep_time_label_hide', '');
add_option('mpprecipe_cook_time_label', 'Cook Time');
add_option('mpprecipe_cook_time_label_hide', '');
add_option('mpprecipe_total_time_label', 'Total Time');
add_option('mpprecipe_total_time_label_hide', '');
add_option('mpprecipe_yield_label', 'Yields');
add_option('mpprecipe_yield_label_hide', '');
add_option('mpprecipe_serving_size_label', 'Serves');
add_option('mpprecipe_serving_size_label_hide', '');
add_option('mpprecipe_rating_label', 'Rating:');
add_option('mpprecipe_rating_label_hide', '');
add_option('mpprecipe_outer_border_style', '');
add_option('mpprecipe_custom_save_image', '');
add_option('mpprecipe_custom_print_image', '');

add_option('mpprecipe_personalizedplugin', '');
add_option('mpprecipe_subdomain', '');

define('MPPRECIPE_AUTO_HANDLE_TOTALTIME',0);

register_activation_hook(__FILE__, 'mpprecipe_install');
add_action('plugins_loaded', 'mpprecipe_upgradedb');

add_action('admin_init', 'mpprecipe_add_recipe_button');
add_action('admin_head','mpprecipe_js_vars');


function mpprecipe_register() 
{ 
    if (get_option('mpprecipe_subdomain'))
        return;

    $h = null;
    if( isset( $_SERVER['SERVER_NAME'] ) )
        $h = $_SERVER['SERVER_NAME'];
    elseif( isset( $_SERVER['HOST_NAME'] ) )
        $h = $_SERVER['HOST_NAME'];

    $u = "http://mealplannerpro.com/api/wordpress/register?host=$h";
    $r = trim(file_get_contents( $u ));
    update_option('mpprecipe_subdomain', $r );
}

function mpprecipe_js_vars() {

    global $current_screen;
    $type = $current_screen->post_type;

    if (is_admin()) {
        ?>
        <script type="text/javascript">
        var mpp_post_id = '<?php global $post; echo $post->ID; ?>';
        </script>
        <?php
    }
}

if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=mpprecipe') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
    if (!empty($_POST)) sanitize($_POST);
    if (!empty($_GET )) sanitize($_GET );

	mpprecipe_iframe_content($_POST, $_REQUEST);
	exit;
}


global $mpprecipe_db_version;
// This must be changed when the DB structure is modified
$mpprecipe_db_version = "3.4";	

// Creates MPPRecipe tables in the db if they don't exist already.
// Don't do any data initialization in this routine as it is called on both install as well as
//   every plugin load as an upgrade check.
//
// Updates the table if needed
// Plugin Ver         DB Ver
//   1.0 - 1.3        3.0
//   1.4x - 3.1       3.1  Adds Notes column to recipes table
//   3.9.2            3.2  Adds author,original columns to recipes table
//   3.9.4            3.3  Adds cuisine,type columns to recipes table
//   4.8              3.4  Adds original_*
function mpprecipe_upgradedb() {
    global $wpdb;
    global $mpprecipe_db_version;

    $recipes_table = $wpdb->prefix . "mpprecipe_recipes";
    $installed_db_ver = get_option("mpprecipe_db_version");

    // An older (or no) database table exists
    if(strcmp($installed_db_ver, $mpprecipe_db_version) != 0) {				
        $sql = "CREATE TABLE " . $recipes_table . " (
            recipe_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            recipe_title TEXT,
            recipe_image TEXT,
            summary TEXT,
            rating TEXT,
            prep_time TEXT,
            cook_time TEXT,
            total_time TEXT,
            yield TEXT,
            serving_size VARCHAR(50),
            calories VARCHAR(50),
            fat VARCHAR(50),
            ingredients TEXT,
            instructions TEXT,
            notes TEXT,
            author TEXT,
            original TEXT,
            original_excerpt TEXT,
            original_type    TEXT,
            cuisine TEXT,
            type TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option("mpprecipe_db_version", $mpprecipe_db_version);
    }
}

function mpprecipe_install() {
    mpprecipe_upgradedb();
}

add_action('admin_menu', 'mpprecipe_menu_pages');

// Adds module to left sidebar in wp-admin for MPPRecipe
function mpprecipe_menu_pages() {
    // Add the top-level admin menu
    $page_title = 'MealPlannerPro Recipe Plugin Settings';
    $menu_title = 'MealPlannerPro Recipe Plugin';
    $capability = 'manage_options';
    $menu_slug = 'mpprecipe-settings';
    $function = 'mpprecipe_settings';
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

    // Add submenu page with same slug as parent to ensure no duplicates
    $settings_title = 'Settings';
    add_submenu_page($menu_slug, $page_title, $settings_title, $capability, $menu_slug, $function);
}

// Adds 'Settings' page to the MPPRecipe module
function mpprecipe_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $mpprecipe_icon = MPPRECIPE_PLUGIN_DIRECTORY . "mpprecipe.png";

    if ($_POST['ingredient-list-type']) {

        sanitize($_POST);

    	$mealplannerpro_partner_key        = $_POST['mealplannerpro-partner-key'];
        $mealplannerpro_recipe_button_hide = $_POST['mealplannerpro-recipe-button-hide'];
        $mealplannerpro_attribution_hide   = $_POST['mealplannerpro-attribution-hide'];
        $printed_permalink_hide            = $_POST['printed-permalink-hide'];
        $printed_copyright_statement       = $_POST['printed-copyright-statement'];
        $stylesheet                        = $_POST['stylesheet'];
        $recipe_title_hide                 = $_POST['recipe-title-hide'];
        $image_hide                        = $_POST['image-hide'];
        $image_hide_print                  = $_POST['image-hide-print'];
        $print_link_hide                   = $_POST['print-link-hide'];
        $ingredient_label                  = $_POST['ingredient-label'];
        $ingredient_label_hide             = $_POST['ingredient-label-hide'];
        $ingredient_list_type              = $_POST['ingredient-list-type'];
        $instruction_label                 = $_POST['instruction-label'];
        $instruction_label_hide            = $_POST['instruction-label-hide'];
        $instruction_list_type             = $_POST['instruction-list-type'];
        $notes_label                       = $_POST['notes-label'];
        $notes_label_hide                  = $_POST['notes-label-hide'];
        $prep_time_label                   = $_POST['prep-time-label'];
        $prep_time_label_hide              = $_POST['prep-time-label-hide'];
        $cook_time_label                   = $_POST['cook-time-label'];
        $cook_time_label_hide              = $_POST['cook-time-label-hide'];
        $total_time_label                  = $_POST['total-time-label'];
        $total_time_label_hide             = $_POST['total-time-label-hide'];
        $yield_label                       = $_POST['yield-label'];
        $yield_label_hide                  = $_POST['yield-label-hide'];
        $serving_size_label                = $_POST['serving-size-label'];
        $serving_size_label_hide           = $_POST['serving-size-label-hide'];
        $rating_label                      = $_POST['rating-label'];
        $rating_label_hide                 = $_POST['rating-label-hide'];
        $image_width                       = $_POST['image-width'];
        $outer_border_style                = $_POST['outer-border-style'];
        $custom_save_image                 = $_POST['custom-save-image'];
        $custom_print_image                = $_POST['custom-print-image'];
        $personalizedplugin                = $_POST['personalizedplugin'];

        update_option('mealplannerpro_partner_key', $mealplannerpro_partner_key);
        update_option('mealplannerpro_recipe_button_hide', $mealplannerpro_recipe_button_hide);
        update_option('mealplannerpro_attribution_hide', $mealplannerpro_attribution_hide);
        update_option('mpprecipe_printed_permalink_hide', $printed_permalink_hide );
        update_option('mpprecipe_printed_copyright_statement', $printed_copyright_statement);
        update_option('mpprecipe_stylesheet', $stylesheet);
        update_option('recipe_title_hide', $recipe_title_hide);
        update_option('mpprecipe_image_hide', $image_hide);
        update_option('mpprecipe_image_hide_print', $image_hide_print);
        update_option('mpprecipe_print_link_hide', $print_link_hide);
        update_option('mpprecipe_ingredient_label', $ingredient_label);
        update_option('mpprecipe_ingredient_label_hide', $ingredient_label_hide);
        update_option('mpprecipe_ingredient_list_type', $ingredient_list_type);
        update_option('mpprecipe_instruction_label', $instruction_label);
        update_option('mpprecipe_instruction_label_hide', $instruction_label_hide);
        update_option('mpprecipe_instruction_list_type', $instruction_list_type);
        update_option('mpprecipe_notes_label', $notes_label);
        update_option('mpprecipe_notes_label_hide', $notes_label_hide);
        update_option('mpprecipe_prep_time_label', $prep_time_label);
        update_option('mpprecipe_prep_time_label_hide', $prep_time_label_hide);
        update_option('mpprecipe_cook_time_label', $cook_time_label);
        update_option('mpprecipe_cook_time_label_hide', $cook_time_label_hide);
        update_option('mpprecipe_total_time_label', $total_time_label);
        update_option('mpprecipe_total_time_label_hide', $total_time_label_hide);
        update_option('mpprecipe_yield_label', $yield_label);
        update_option('mpprecipe_yield_label_hide', $yield_label_hide);
        update_option('mpprecipe_serving_size_label', $serving_size_label);
        update_option('mpprecipe_serving_size_label_hide', $serving_size_label_hide);
        update_option('mpprecipe_calories_label', $calories_label);
        update_option('mpprecipe_calories_label_hide', $calories_label_hide);
        update_option('mpprecipe_fat_label', $fat_label);
        update_option('mpprecipe_fat_label_hide', $fat_label_hide);
        update_option('mpprecipe_rating_label', $rating_label);
        update_option('mpprecipe_rating_label_hide', $rating_label_hide);
        update_option('mpprecipe_outer_border_style', $outer_border_style);
        update_option('mpprecipe_custom_save_image', $custom_save_image);
        update_option('mpprecipe_custom_print_image', $custom_print_image);
        update_option('mpprecipe_personalizedplugin', $personalizedplugin);
    } else {
        $mealplannerpro_partner_key        = get_option('mealplannerpro_partner_key');
        $mealplannerpro_recipe_button_hide = get_option('mealplannerpro_recipe_button_hide');
        $mealplannerpro_attribution_hide   = get_option('mealplannerpro_attribution_hide');
        $printed_permalink_hide            = get_option('mpprecipe_printed_permalink_hide');
        $printed_copyright_statement       = get_option('mpprecipe_printed_copyright_statement');
        $stylesheet                        = get_option('mpprecipe_stylesheet');
        $recipe_title_hide                 = get_option('recipe_title_hide');
        $image_hide                        = get_option('mpprecipe_image_hide');
        $image_hide_print                  = get_option('mpprecipe_image_hide_print');
        $print_link_hide                   = get_option('mpprecipe_print_link_hide');
        $ingredient_label                  = get_option('mpprecipe_ingredient_label');
        $ingredient_label_hide             = get_option('mpprecipe_ingredient_label_hide');
        $ingredient_list_type              = get_option('mpprecipe_ingredient_list_type');
        $instruction_label                 = get_option('mpprecipe_instruction_label');
        $instruction_label_hide            = get_option('mpprecipe_instruction_label_hide');
        $instruction_list_type             = get_option('mpprecipe_instruction_list_type');
        $notes_label                       = get_option('mpprecipe_notes_label');
        $notes_label_hide                  = get_option('mpprecipe_notes_label_hide');
        $prep_time_label                   = get_option('mpprecipe_prep_time_label');
        $prep_time_label_hide              = get_option('mpprecipe_prep_time_label_hide');
        $cook_time_label                   = get_option('mpprecipe_cook_time_label');
        $cook_time_label_hide              = get_option('mpprecipe_cook_time_label_hide');
        $total_time_label                  = get_option('mpprecipe_total_time_label');
        $total_time_label_hide             = get_option('mpprecipe_total_time_label_hide');
        $yield_label                       = get_option('mpprecipe_yield_label');
        $yield_label_hide                  = get_option('mpprecipe_yield_label_hide');
        $serving_size_label                = get_option('mpprecipe_serving_size_label');
        $serving_size_label_hide           = get_option('mpprecipe_serving_size_label_hide');
        $calories_label                    = get_option('mpprecipe_calories_label');
        $calories_label_hide               = get_option('mpprecipe_calories_label_hide');
        $fat_label                         = get_option('mpprecipe_fat_label');
        $fat_label_hide                    = get_option('mpprecipe_fat_label_hide');
        $rating_label                      = get_option('mpprecipe_rating_label');
        $rating_label_hide                 = get_option('mpprecipe_rating_label_hide');
        $outer_border_style                = get_option('mpprecipe_outer_border_style');
        $custom_save_image                 = get_option('mpprecipe_custom_save_image');
        $custom_print_image                = get_option('mpprecipe_custom_print_image');
        $personalizedplugin               = get_option('mpprecipe_personalizedplugin');
    }

    $mealplannerpro_partner_key  = esc_attr($mealplannerpro_partner_key);
    $printed_copyright_statement = esc_attr($printed_copyright_statement);
    $ingredient_label            = esc_attr($ingredient_label);
    $instruction_label           = esc_attr($instruction_label);
    $notes_label                 = esc_attr($notes_label);
    $prep_time_label             = esc_attr($prep_time_label);
    $prep_time_label             = esc_attr($prep_time_label);
    $cook_time_label             = esc_attr($cook_time_label);
    $total_time_label            = esc_attr($total_time_label);
    $total_time_label            = esc_attr($total_time_label);
    $yield_label                 = esc_attr($yield_label);
    $serving_size_label          = esc_attr($serving_size_label);
    $calories_label              = esc_attr($calories_label);
    $fat_label                   = esc_attr($fat_label);
    $rating_label                = esc_attr($rating_label);
    $image_width                 = esc_attr($image_width);
	$custom_save_image           = esc_attr($custom_save_image);
	$custom_print_image          = esc_attr($custom_print_image);

    $mealplannerpro_recipe_button_hide = (strcmp($mealplannerpro_recipe_button_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $mealplannerpro_attribution_hide = (strcmp($mealplannerpro_attribution_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $printed_permalink_hide = (strcmp($printed_permalink_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $recipe_title_hide = (strcmp($recipe_title_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $image_hide = (strcmp($image_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $image_hide_print = (strcmp($image_hide_print, 'Hide') == 0 ? 'checked="checked"' : '');
    $print_link_hide = (strcmp($print_link_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $personalizedplugin = (strcmp($personalizedplugin, 'Show') == 0 ? 'checked="checked"' : '');

    // Stylesheet processing
    $stylesheet = (strcmp($stylesheet, 'mpprecipe-std') == 0 ? 'checked="checked"' : '');

    // Outer (hrecipe) border style
	$obs = '';
	$borders = array('None' => '', 'Solid' => '1px solid', 'Dotted' => '1px dotted', 'Dashed' => '1px dashed', 'Thick Solid' => '2px solid', 'Double' => 'double');
	foreach ($borders as $label => $code) {
		$obs .= '<option value="' . $code . '" ' . (strcmp($outer_border_style, $code) == 0 ? 'selected="true"' : '') . '>' . $label . '</option>';
	}

    $ingredient_label_hide   = (strcmp($ingredient_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $ing_ul                  = (strcmp($ingredient_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ing_ol                  = (strcmp($ingredient_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ing_p                   = (strcmp($ingredient_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ing_div                 = (strcmp($ingredient_list_type, 'div') == 0 ? 'checked="checked"' : '');
    $instruction_label_hide  = (strcmp($instruction_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $ins_ul                  = (strcmp($instruction_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ins_ol                  = (strcmp($instruction_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ins_p                   = (strcmp($instruction_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ins_div                 = (strcmp($instruction_list_type, 'div') == 0 ? 'checked="checked"' : '');
    $prep_time_label_hide    = (strcmp($prep_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $cook_time_label_hide    = (strcmp($cook_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $total_time_label_hide   = (strcmp($total_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $yield_label_hide        = (strcmp($yield_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $serving_size_label_hide = (strcmp($serving_size_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $calories_label_hide     = (strcmp($calories_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $fat_label_hide          = (strcmp($fat_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $rating_label_hide       = (strcmp($rating_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $notes_label_hide        = (strcmp($notes_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $other_options           = '';
    $other_options_array     = array(
        'Rating', 'Prep Time', 'Cook Time', 'Total Time', 'Yield', 'Serving Size', 
        'Notes');

    $stylesheets_options_array = array(
        'Elegant' => 'mpprecipe-std',
        //'None'    => '',
        'Traditional 1'    => 'mpprecipe-design2',
        'Traditional 2'    => 'mpprecipe-design3',
        'Traditional 3'    => 'mpprecipe-design4',
        'Traditional 4'    => 'mpprecipe-design5',
        'Traditional 5'    => 'mpprecipe-design7',
        'Stand Out'    => 'mpprecipe-design6',
        'Stand Out 2'    => 'mpprecipe-design9',
        'Compact'    => 'mpprecipe-design8',

    );

    foreach ($stylesheets_options_array as $k => $v )  {
    	if ($v == get_option('mpprecipe_stylesheet')) {
        	$stylesheets_options .= "<option value='$v' selected> $k </option>";
        }
        else {
         	$stylesheets_options .= "<option value='$v'> $k </option>";       
        }
	}

    foreach ($other_options_array as $option) {
        $name = strtolower(str_replace(' ', '-', $option));
        $value = strtolower(str_replace(' ', '_', $option)) . '_label';
        $value_hide = strtolower(str_replace(' ', '_', $option)) . '_label_hide';
        $other_options .= '<tr valign="top">
            <th scope="row">\'' . $option . '\' Label</th>
            <td><input type="text" name="' . $name . '-label" value="' . ${$value} . '" class="regular-text" /><br />
            <label><input type="checkbox" name="' . $name . '-label-hide" value="Hide" ' . ${$value_hide} . ' /> Don\'t show ' . $option . ' label</label></td>
        </tr>';
    }

    echo '<style>
        .form-table label { line-height: 2.5; }
        hr { border: 1px solid #DDD; border-left: none; border-right: none; border-bottom: none; margin: 30px 0; }
    </style>
    <div class="wrap">
        <form enctype="multipart/form-data" method="post" action="" name="mpprecipe_settings_form">
            <h2><img src="' . $mpprecipe_icon . '" /> MealPlannerPro Recipe Plugin Settings</h2>
			<h3>General</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Stylesheet</th>
                    <td>
                    <label>
						<select name="stylesheet"> ' . $stylesheets_options  . ' </select>
                    </label>
                    </td>
                </tr>
                <tr valign="top">
                    <td>
                    	<label><input type="hidden" name="personalizedplugin" value="" ' . $personalizedplugin . ' /> 
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Recipe Title</th>
                    <td><label><input type="checkbox" name="recipe-title-hide" value="Hide" ' . $recipe_title_hide . ' /> Don\'t show Recipe Title in post (still shows in print view)</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Print Button</th>
                    <td><label><input type="checkbox" name="print-link-hide" value="Hide" ' . $print_link_hide . ' /> Don\'t show Print Button</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Width</th>
                    <td><label><input type="text" name="image-width" value="' . $image_width . '" class="regular-text" /> pixels</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Display</th>
                    <td>
                    	<label><input type="checkbox" name="image-hide" value="Hide" ' . $image_hide . ' /> Don\'t show Image in post</label>
                    	<br />
                    	<label><input type="checkbox" name="image-hide-print" value="Hide" ' . $image_hide_print . ' /> Don\'t show Image in print view</label>
                    </td>
                </tr>
                <tr valign="top">
                	<th scope="row">Border Style</th>
                	<td>
						<select name="outer-border-style">' . $obs . '</select>
					</td>
				</tr>
            </table>
            <hr />
			<h3>Printing</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                    	Custom Print Button
                    	<br />
                    	(Optional)
                    </th>
                    <td>
                        <input placeholder="URL to custom Print button image" type="text" name="custom-print-image" value="' . $custom_print_image . '" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Recipe Permalink</th>
                    <td><label><input type="checkbox" name="printed-permalink-hide" value="Hide" ' . $printed_permalink_hide . ' /> Don\'t show permalink in printed output</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Printed Output: Copyright Statement</th>
                    <td><input type="text" name="printed-copyright-statement" value="' . $printed_copyright_statement . '" class="regular-text" /></td>
                </tr>
            </table>
            <hr />
            <h3>Ingredients</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Ingredients\' Label</th>
                    <td><input type="text" name="ingredient-label" value="' . $ingredient_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="ingredient-label-hide" value="Hide" ' . $ingredient_label_hide . ' /> Don\'t show Ingredients label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Ingredients\' List Type</th>
                    <td><input type="radio" name="ingredient-list-type" value="ul" ' . $ing_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="ingredient-list-type" value="ol" ' . $ing_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="ingredient-list-type" value="p" ' . $ing_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="ingredient-list-type" value="div" ' . $ing_div . ' /> <label>Divs</label></td>
                </tr>
            </table>

            <hr />

            <h3>Instructions</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Instructions\' Label</th>
                    <td><input type="text" name="instruction-label" value="' . $instruction_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="instruction-label-hide" value="Hide" ' . $instruction_label_hide . ' /> Don\'t show Instructions label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Instructions\' List Type</th>
                    <td><input type="radio" name="instruction-list-type" value="ol" ' . $ins_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="instruction-list-type" value="ul" ' . $ins_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="instruction-list-type" value="p" ' . $ins_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="instruction-list-type" value="div" ' . $ins_div . ' /> <label>Divs</label></td>
                </tr>
            </table>

            <hr />

            <h3>Other Options</h3>
            <table class="form-table">
                ' . $other_options . '
            </table>

            <p><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
        </form>
    </div>'. mpp_convert_js() . mpp_convert_ziplist_entries_form() . mpp_convert_yummly_entries_form() . mpp_convert_easyrecipe_entries_form() . mpp_revert_yummly_entries_form() . mpp_revert_ziplist_entries_form() .  mpp_revert_easyrecipe_entries_form();
}


function mpprecipe_tinymce_plugin($plugin_array) {
	$plugin_array['mpprecipe'] = plugins_url( '/mpprecipe_editor_plugin.js?sver=' . MPPRECIPE_VERSION_NUM, __FILE__ );
	return $plugin_array;
}

function mpprecipe_register_tinymce_button($buttons) {
   array_push($buttons, "mpprecipe");
   return $buttons;
}

function mpprecipe_add_recipe_button() {

    // check user permissions
    if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
   	return;
    }

	// check if WYSIWYG is enabled
	if ( get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'mpprecipe_tinymce_plugin');
		add_filter('mce_buttons', 'mpprecipe_register_tinymce_button');
	}
}

// Content for the popup iframe when creating or editing a recipe
function mpprecipe_iframe_content($post_info = null, $get_info = null) {
    $recipe_id = 0;
    if ($post_info || $get_info) {

    	if( $get_info["add-recipe-button"] || strpos($get_info["post_id"], '-') !== false ) {
        	$iframe_title = "Update Your Recipe";
        	$submit = "Update Recipe";
        } else {
    		$iframe_title = "Add a Recipe";
    		$submit = "Add Recipe";
        }


        if ($get_info["post_id"] && !$get_info["add-recipe-button"] && strpos($get_info["post_id"], '-') !== false) {
            $recipe_id = preg_replace('/[0-9]*?\-/i', '', $get_info["post_id"]);
            $recipe = mpprecipe_select_recipe_db($recipe_id);
            $recipe_title = $recipe->recipe_title;
            $recipe_image = $recipe->recipe_image;
            $summary = $recipe->summary;
            $author  = $recipe->author;
            $cuisine  = $recipe->cuisine;
            $type  = $recipe->type;
            $notes = $recipe->notes;
            $rating = $recipe->rating;
            $ss = array();
            $ss[(int)$rating] = 'selected="true"';
            $prep_time_input = '';
            $cook_time_input = '';
            $total_time_input = '';
            if (class_exists('DateInterval') and MPPRECIPE_AUTO_HANDLE_TOTALTIME ) {
                try {
                    $prep_time = new DateInterval($recipe->prep_time);
                    $prep_time_seconds = $prep_time->s;
                    $prep_time_minutes = $prep_time->i;
                    $prep_time_hours = $prep_time->h;
                    $prep_time_days = $prep_time->d;
                    $prep_time_months = $prep_time->m;
                    $prep_time_years = $prep_time->y;
                } catch (Exception $e) {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }

                try {
                    $cook_time = new DateInterval($recipe->cook_time);
                    $cook_time_seconds = $cook_time->s;
                    $cook_time_minutes = $cook_time->i;
                    $cook_time_hours = $cook_time->h;
                    $cook_time_days = $cook_time->d;
                    $cook_time_months = $cook_time->m;
                    $cook_time_years = $cook_time->y;
                } catch (Exception $e) {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }

                try {
                    $total_time = new DateInterval($recipe->total_time);
                    $total_time_seconds = $total_time->s;
                    $total_time_minutes = $total_time->i;
                    $total_time_hours = $total_time->h;
                    $total_time_days = $total_time->d;
                    $total_time_months = $total_time->m;
                    $total_time_years = $total_time->y;
                } catch (Exception $e) {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            } else {
                if (preg_match('(^[A-Z0-9]*$)', $recipe->prep_time) == 1) {
                    preg_match('(\d*S)', $recipe->prep_time, $pts);
                    $prep_time_seconds = str_replace('S', '', $pts[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptm, PREG_OFFSET_CAPTURE, strpos($recipe->prep_time, 'T'));
                    $prep_time_minutes = str_replace('M', '', $ptm[0][0]);
                    preg_match('(\d*H)', $recipe->prep_time, $pth);
                    $prep_time_hours = str_replace('H', '', $pth[0]);
                    preg_match('(\d*D)', $recipe->prep_time, $ptd);
                    $prep_time_days = str_replace('D', '', $ptd[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptmm);
                    $prep_time_months = str_replace('M', '', $ptmm[0]);
                    preg_match('(\d*Y)', $recipe->prep_time, $pty);
                    $prep_time_years = str_replace('Y', '', $pty[0]);
                } else {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }

                if (preg_match('(^[A-Z0-9]*$)', $recipe->cook_time) == 1) {
                    preg_match('(\d*S)', $recipe->cook_time, $cts);
                    $cook_time_seconds = str_replace('S', '', $cts[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctm, PREG_OFFSET_CAPTURE, strpos($recipe->cook_time, 'T'));
                    $cook_time_minutes = str_replace('M', '', $ctm[0][0]);
                    preg_match('(\d*H)', $recipe->cook_time, $cth);
                    $cook_time_hours = str_replace('H', '', $cth[0]);
                    preg_match('(\d*D)', $recipe->cook_time, $ctd);
                    $cook_time_days = str_replace('D', '', $ctd[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctmm);
                    $cook_time_months = str_replace('M', '', $ctmm[0]);
                    preg_match('(\d*Y)', $recipe->cook_time, $cty);
                    $cook_time_years = str_replace('Y', '', $cty[0]);
                } else {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }

                if (preg_match('(^[A-Z0-9]*$)', $recipe->total_time) == 1) {
                    preg_match('(\d*S)', $recipe->total_time, $tts);
                    $total_time_seconds = str_replace('S', '', $tts[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttm, PREG_OFFSET_CAPTURE, strpos($recipe->total_time, 'T'));
                    $total_time_minutes = str_replace('M', '', $ttm[0][0]);
                    preg_match('(\d*H)', $recipe->total_time, $tth);
                    $total_time_hours = str_replace('H', '', $tth[0]);
                    preg_match('(\d*D)', $recipe->total_time, $ttd);
                    $total_time_days = str_replace('D', '', $ttd[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttmm);
                    $total_time_months = str_replace('M', '', $ttmm[0]);
                    preg_match('(\d*Y)', $recipe->total_time, $tty);
                    $total_time_years = str_replace('Y', '', $tty[0]);
                } else {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            }

            $yield = $recipe->yield;
            $serving_size = $recipe->serving_size;
            $ingredients = $recipe->ingredients;
            $instructions = $recipe->instructions;
        } else {
        	foreach ($post_info as $key=>$val) {
        		$post_info[$key] = stripslashes($val);
        	}

            $recipe_id = $post_info["recipe_id"];
            if( !$get_info["add-recipe-button"] )
                 $recipe_title = get_the_title( $get_info["post_id"] );
            else
                 $recipe_title = $post_info["recipe_title"];
            $recipe_image = $post_info["recipe_image"];
            $summary = $post_info["summary"];
            $author  = $post_info["author"];
            $cuisine  = $post_info["cuisine"];
            $type  = $post_info["type"];
            $notes = $post_info["notes"];
            $rating = $post_info["rating"];
            $prep_time_seconds = $post_info["prep_time_seconds"];
            $prep_time_minutes = $post_info["prep_time_minutes"];
            $prep_time_hours = $post_info["prep_time_hours"];
            $prep_time_days = $post_info["prep_time_days"];
            $prep_time_weeks = $post_info["prep_time_weeks"];
            $prep_time_months = $post_info["prep_time_months"];
            $prep_time_years = $post_info["prep_time_years"];
            $cook_time_seconds = $post_info["cook_time_seconds"];
            $cook_time_minutes = $post_info["cook_time_minutes"];
            $cook_time_hours = $post_info["cook_time_hours"];
            $cook_time_days = $post_info["cook_time_days"];
            $cook_time_weeks = $post_info["cook_time_weeks"];
            $cook_time_months = $post_info["cook_time_months"];
            $cook_time_years = $post_info["cook_time_years"];
            $total_time_seconds = $post_info["total_time_seconds"];
            $total_time_minutes = $post_info["total_time_minutes"];
            $total_time_hours = $post_info["total_time_hours"];
            $total_time_days = $post_info["total_time_days"];
            $total_time_weeks = $post_info["total_time_weeks"];
            $total_time_months = $post_info["total_time_months"];
            $total_time_years = $post_info["total_time_years"];
            $yield = $post_info["yield"];
            $serving_size = $post_info["serving_size"];
            $ingredients = $post_info["ingredients"];
            $instructions = $post_info["instructions"];
            if ($recipe_title != null && $recipe_title != '' && $ingredients != null && $ingredients != '') {
                $recipe_id = mpprecipe_insert_db($post_info);
            }
        }
    }

	$recipe_title       = esc_attr($recipe_title);
	$recipe_image       = esc_attr($recipe_image);
	$prep_time_hours    = esc_attr($prep_time_hours);
	$prep_time_minutes  = esc_attr($prep_time_minutes);
	$cook_time_hours    = esc_attr($cook_time_hours);
	$cook_time_minutes  = esc_attr($cook_time_minutes);
	$total_time_hours   = esc_attr($total_time_hours);
	$total_time_minutes = esc_attr($total_time_minutes);
	$yield              = esc_attr($yield);
	$serving_size       = esc_attr($serving_size);
	$ingredients        = esc_textarea($ingredients);
	$instructions       = esc_textarea($instructions);
	$summary            = esc_textarea($summary);
	$notes              = esc_textarea($notes);

    $id = (int) $_REQUEST["post_id"];
    $plugindir = MPPRECIPE_PLUGIN_DIRECTORY;
    $submitform = '';
    if ($post_info != null) {
        $submitform .= "<script>window.onload = MPPRecipeSubmitForm;</script>";
    }

    if (class_exists('DateInterval') and MPPRECIPE_AUTO_HANDLE_TOTALTIME ) 
        $total_time_input_container = '';
    else
    {
        $total_time_input_container = <<<HTML
                <p class="cls"><label>Total Time</label>
                    $total_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="240" id='total_time_hours' name='total_time_hours' value='$total_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60"  id='total_time_minutes' name='total_time_minutes' value='$total_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
HTML;
    }

    echo <<< HTML

<!DOCTYPE html>
<head>
		<link rel="stylesheet" href="$plugindir/mpprecipe-dlog.css" type="text/css" media="all" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    <script type="text/javascript">//<!CDATA[

        function MPPRecipeSubmitForm() {
            var title = document.forms['recipe_form']['recipe_title'].value;

            if (title==null || title=='') {
                $('#recipe-title input').addClass('input-error');
                $('#recipe-title').append('<p class="error-message">You must enter a title for your recipe.</p>');

                return false;
            }
            var ingredients = $('#mpprecipe_ingredients textarea').val();
            if (ingredients==null || ingredients=='' || ingredients==undefined) {
                $('#mpprecipe_ingredients textarea').addClass('input-error');
                $('#mpprecipe_ingredients').append('<p class="error-message">You must enter at least one ingredient.</p>');

                return false;
            }
            window.parent.MPPRecipeInsertIntoPostEditor('$recipe_id');
            top.tinymce.activeEditor.windowManager.close(window);
        }

        $(document).ready(function() {
            $('#more-options').hide();
            $('#more-options-toggle').click(function() {
                $('#more-options').toggle(400);
                return false;
            });
        });
    //]]>
    </script>
    $submitform
</head>
<body id="mpprecipe-uploader">
    <form enctype='multipart/form-data' method='post' action='' name='recipe_form'>
        <h3 class='mpprecipe-title'>$iframe_title</h3>
        <div id='mpprecipe-form-items'>
            <input type='hidden' name='post_id' value='$id' />
            <input type='hidden' name='recipe_id' value='$recipe_id' />
            <p id='recipe-title'><label>Recipe Title <span class='required'>*</span></label> <input type='text' name='recipe_title' value='$recipe_title' /></p>
            <p id='recipe-image'><label>Recipe Image</label> <input type='text' name='recipe_image' value='$recipe_image' /></p>
            <p id='mpprecipe_ingredients'  class='cls'><label>Ingredients <span class='required'>*</span> <small>Put each ingredient on a separate line.  There is no need to use bullets for your ingredients. To add sub-headings put them on a new line beginning with "!". Example will be "!for the dressing:"</small></label><textarea name='ingredients'>$ingredients</textarea></label></p>
            <p id='mpprecipe-instructions' class='cls'><label>Instructions <small>Press return after each instruction. There is no need to number your instructions.</small></label><textarea name='instructions'>$instructions</textarea></label></p>
            <p><a href='#' id='more-options-toggle'>More options</a></p>
            <div id='more-options'>
                <p class='cls'><label>Author</label> <input type='text' name='author' value='$author'/></p>

                <p class='cls'><label>Summary</label> <textarea name='summary'>$summary</textarea></p>
                <p class='cls'><label>Rating</label>
                	<span class='rating'>
						<select name="rating">
							  <option value="0">None</option>
							  <option value="1" $ss[1]>1 Star</option>
							  <option value="2" $ss[2]>2 Stars</option>
							  <option value="3" $ss[3]>3 Stars</option>
							  <option value="4" $ss[4]>4 Stars</option>
							  <option value="5" $ss[5]>5 Stars</option>
						</select>
					</span>
				</p>
                <p class="cls"><label>Prep Time</label>
                    $prep_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" id='prep_time_hours' name='prep_time_hours' value='$prep_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" id='prep_time_minutes' name='prep_time_minutes' value='$prep_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class="cls"><label>Cook Time</label>
                    $cook_time_input
                    <span class="time">
                    	<span><input type='number' min="0" max="24" id='cook_time_hours' name='cook_time_hours' value='$cook_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" id='cook_time_minutes' name='cook_time_minutes' value='$cook_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                $total_time_input_container
                <p><label>Yield</label> <input type='text' name='yield' value='$yield' /></p>
                <p><label>Serving Size</label> <input type='text' name='serving_size' value='$serving_size' /></p>
                <p class='cls'><label>Notes</label> <textarea name='notes'>$notes</textarea></label></p>

                <p class='cls'><label>Cuisine <input type='text' name='cuisine' value='$cuisine'/></p>
                <p class='cls'><label>Type</label> <input type='text' name='type' value='$type'/></p>
            </div>
            <input type='submit' value='$submit' name='add-recipe-button' />
        </div>
    </form>
</body>

<script>
        var g = function( id ) { return document.getElementById( id ) }
        var v = function( id ) {
            var v = parseInt( g(id).value )  
            return isNaN( v ) ? 0 : v
        }

        function calc()
        {
            var h = v('cook_time_hours')   + v('prep_time_hours')
            var m = v('cook_time_minutes') + v('prep_time_minutes')

            var h_from_m  = Math.floor(m/60)

            // minutes after hour-equivalents removed
            var m = m % (60*Math.max(h_from_m,1)) 
            var h = h + h_from_m

            g('total_time_hours').value   =  h
            g('total_time_minutes').value =  m
        }

        g('cook_time_hours').onchange   = calc
        g('cook_time_minutes').onchange = calc
        g('prep_time_hours').onchange   = calc
        g('prep_time_minutes').onchange = calc

</script>

HTML;
}

/**
 * Deal with the aggregation of input duration-time parts.
 */
function collate_time_input( $type, $post )
{
    $duration_units = array(
        $type . '_time_years'  => 'Y',
        $type . '_time_months' => 'M',
        $type . '_time_days'   => 'D',
    );
    $time_units    = array(
        $type . '_time_hours'   => 'H',
        $type . '_time_minutes' => 'M',
        $type . '_time_seconds' => 'S',
    );

    if (!( $post[$type . '_time_years']   || $post[$type . '_time_months'] 
        || $post[$type . '_time_days']    || $post[$type . '_time_hours'] 
        || $post[$type . '_time_minutes'] || $post[$type . '_time_seconds']
    ))
        $o = $post[$type . '_time'];
    else
    {
        $o = 'P';
        foreach($duration_units as $d => $u)
        {
            if( $post[$d] ) $time .= $post[$d] . $u;
        }
        if (   $post[$type . '_time_hours'] 
            || $post[$type . '_time_minutes'] 
            || $post[$type . '_time_seconds']
        )
            $o .= 'T';
        foreach( $time_units as $t => $u )
        {
            if( $post[$t] ) $o .= $post[$t] . $u;
        }
    } 

    return $o;
}

// Inserts the recipe into the database
function mpprecipe_insert_db($post_info) {
    global $wpdb;

    $recipe      = array ();
    $recipe_keys = array (
        "recipe_title" , "recipe_image", "summary", "rating", "yield", 
        "serving_size", 
        "calories", "fat",
        "ingredients", "instructions", 
        "notes",
        "author", "cuisine", "type"
    );
    foreach( $recipe_keys as $k )
        $recipe[ $k ] = $post_info[ $k ];

    $recipe["prep_time"]  = collate_time_input( 'prep',  $post_info );
    $recipe["cook_time"]  = collate_time_input( 'cook',  $post_info );
    $recipe["total_time"] = collate_time_input( 'total', $post_info );

    if( mpprecipe_select_recipe_db($recipe_id) )
        $wpdb->update( $wpdb->prefix . "mpprecipe_recipes", $recipe, array( 'recipe_id' => $recipe_id ));
    else
    {
    	$recipe["post_id"] = $post_info["post_id"];
        $wpdb->insert( $wpdb->prefix . "mpprecipe_recipes", $recipe );
        $recipe_id = $wpdb->insert_id;
    }

    return $recipe_id;
}

// Inserts the recipe into the post editor
function mpprecipe_plugin_footer() {
	$url = site_url();
	$plugindir = MPPRECIPE_PLUGIN_DIRECTORY;

    echo <<< HTML
    <style type="text/css" media="screen">
        #wp_editrecipebtns { position:absolute;display:block;z-index:999998; }
        #wp_editrecipebtn { margin-right:20px; }
        #wp_editrecipebtn,#wp_delrecipebtn { cursor:pointer; padding:12px;background:#010101; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px; filter:alpha(opacity=80); -moz-opacity:0.8; -khtml-opacity: 0.8; opacity: 0.8; }
        #wp_editrecipebtn:hover,#wp_delrecipebtn:hover { background:#000; filter:alpha(opacity=100); -moz-opacity:1; -khtml-opacity: 1; opacity: 1; }
        .mce-window .mce-container-body.mce-abs-layout
        {
            -webkit-overflow-scrolling: touch;
            overflow-y: auto;
        }
    </style>
    <script>//<![CDATA[
    var baseurl = '$url';          // This variable is used by the editor plugin
    var plugindir = '$plugindir';  // This variable is used by the editor plugin

        function MPPRecipeInsertIntoPostEditor(rid) {
            tb_remove();

            var ed;

            var output = '<img id="mpprecipe-recipe-';
            output += rid;
						output += '" class="mpprecipe-recipe" src="' + plugindir + '/mpprecipe-placeholder.png" alt="" />';

        	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() && ed.id=='content') {  //path followed when in Visual editor mode
        		ed.focus();
        		if ( tinymce.isIE )
        			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

        		ed.execCommand('mceInsertContent', false, output);

        	} else if ( typeof edInsertContent == 'function' ) {  // path followed when in HTML editor mode
                output = '[mpprecipe-recipe:';
                output += rid;
                output += ']';
                edInsertContent(edCanvas, output);
        	} else {
                output = '[mpprecipe-recipe:';
                output += rid;
                output += ']';
        		jQuery( edCanvas ).val( jQuery( edCanvas ).val() + output );
        	}
        }
    //]]></script>
HTML;
}

add_action('admin_footer', 'mpprecipe_plugin_footer');

// Converts the image to a recipe for output
function mpprecipe_convert_to_recipe($post_text) {
    $output = $post_text;
    $needle_old = 'id="mpprecipe-recipe-';
    $preg_needle_old = '/(id)=("(mpprecipe-recipe-)[0-9^"]*")/i';
    $needle = '[mpprecipe-recipe:';
    $preg_needle = '/\[mpprecipe-recipe:([0-9]+)\]/i';

    if (strpos($post_text, $needle_old) !== false) {
        // This is for backwards compatability. Please do not delete or alter.
        preg_match_all($preg_needle_old, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('id="mpprecipe-recipe-', '', $match);
            $recipe_id = str_replace('"', '', $recipe_id);
            $recipe = mpprecipe_select_recipe_db($recipe_id);
            $formatted_recipe = mpprecipe_format_recipe($recipe);
            $output = str_replace('<img id="mpprecipe-recipe-' . $recipe_id . '" class="mpprecipe-recipe" src="' . plugins_url() . '/' . dirname(plugin_basename(__FILE__)) . '/mpprecipe-placeholder.png?ver=1.0" alt="" />', $formatted_recipe, $output);
        }
    }

    if (strpos($post_text, $needle) !== false) {
        preg_match_all($preg_needle, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('[mpprecipe-recipe:', '', $match);
            $recipe_id = str_replace(']', '', $recipe_id);
            $recipe = mpprecipe_select_recipe_db($recipe_id);
            $formatted_recipe = mpprecipe_format_recipe($recipe);
            $output = str_replace('[mpprecipe-recipe:' . $recipe_id . ']', $formatted_recipe, $output);
        }
    }

    return $output;
}


# Allow MPP formatting of ziplist entries without conversion
function mpp_format_ziplist_entries( $output )
{
    $zl_id   = 'amd-zlrecipe-recipe';
    # Match string that
    # - opens with     <img id="      or     [
    # - contains amd-zlrecipe-recipe 
    # - followed by a : or -
    # - followed by a string of digits
    # - closed by a    "(anything) /> or     ]
    # FIXME: Restore legacy support.
    $regex   = '/\[amd-zlrecipe-recipe:(\d+)\]/i';
    $matches = array();

    if( strpos( $output, $zl_id ) === False )
        return $output;

    preg_match_all( $regex, $output, $matches );

    foreach( $matches[1] as $match_index => $recipe_id )
    {
        $matched_str      = $matches[0][$match_index];

        $recipe           = mpprecipe_select_recipe_db( $recipe_id, 'amd_zlrecipe_recipes' );
        $formatted_recipe = mpprecipe_format_recipe($recipe);

        $output = str_replace( $matched_str, $formatted_recipe, $output );
    }

    return $output;
}


add_filter('the_content', 'mpprecipe_convert_to_recipe');

// Pulls a recipe from the db
function mpprecipe_select_recipe_db($recipe_id, $table = 'mpprecipe_recipes' ) {
    global $wpdb;
    return $wpdb->get_row( "SELECT * FROM $wpdb->prefix$table WHERE recipe_id=$recipe_id" );
}

// Format an ISO8601 duration for human readibility
function mpprecipe_format_duration($duration) 
{
    $date_abbr = array(
        'y' => 'year', 'm' => 'month', 
        'd' => 'day', 'h' => 'hr', 
        'i' => '', 's' => 'second'
    );
	$result = '';

    if (class_exists('DateInterval'))
    {
		try {
            if( !($duration instanceof DateInterval ))
		        $duration = new DateInterval($duration);

            foreach ($date_abbr as $abbr => $name) 
            {
                if ($duration->$abbr > 0) 
                {
					$result .= $duration->$abbr . ' ' . $name;

					if ($duration->$abbr > 1) 
						$result .= '';

					$result .= ', ';
				}
			}

			$result = trim($result, ' \t,');
		} catch (Exception $e) {
			$result = $duration;
		}
	} else { // else we have to do the work ourselves so the output is pretty
		$arr = explode('T', $duration);

        // This mimics the DateInterval property name
        $arr[1]   = str_replace('M', 'I', $arr[1]); 

		$duration = implode('T', $arr);

        foreach ($date_abbr as $abbr => $name) 
        {
            if (preg_match('/(\d+)' . $abbr . '/i', $duration, $val))
            {
                $result .= $val[1] . ' ' . $name;

                if ($val[1] > 1)
                    $result .= 's';

                $result .= ', ';
            }
		}

		$result = trim($result, ' \t,');
	}
	return $result;
}

// function to include the javascript for the Add Recipe button
function mpprecipe_process_head() {

	// Always add the print script
    $header_html='<script type="text/javascript" async="" src="' . MPPRECIPE_PLUGIN_DIRECTORY . 'mpprecipe_print.js"></script>
';

// adding google font
$header_html .= '<link href="http://fonts.googleapis.com/css?family=Lato:400,700,300italic,300,100" rel="stylesheet" type="text/css">';

// Add common stylesheet
$header_html .= '<link charset="utf-8" href="' . MPPRECIPE_PLUGIN_DIRECTORY .  'mpprecipe-common' . '.css" rel="stylesheet" type="text/css" />';


	// Recipe styling
	$css = get_option('mpprecipe_stylesheet');
	if (strcmp($css, '') != 0) {
		$header_html .= '<link charset="utf-8" href="' . MPPRECIPE_PLUGIN_DIRECTORY .  $css . '.css" rel="stylesheet" type="text/css" />
';
	/* Dev Testing	$header_html .= '<link charset="utf-8" href="http://dev.mealplannerpro.com.s3.amazonaws.com/' . $css . '.css" rel="stylesheet" type="text/css" />
'; */
	}

    echo $header_html;
}
add_filter('wp_head', 'mpprecipe_process_head');

// Replaces the [a|b] pattern with text a that links to b
// Replaces _words_ with an italic span and *words* with a bold span
function mpprecipe_richify_item($item, $class) {
	$output = preg_replace('/\[([^\]\|\[]*)\|([^\]\|\[]*)\]/', '<a href="\\2" class="' . $class . '-link" target="_blank">\\1</a>', $item);
	$output = preg_replace('/(^|\s)\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*(\W|$)/', '\\1<span class="bold">\\2</span>\\3', $output);
	return preg_replace('/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '\\1<span class="italic">\\2</span>\\3', $output);
}

function mpprecipe_break( $otag, $text, $ctag) {
	$output = "";
	$split_string = explode( "\r\n\r\n", $text, 10 );
	foreach ( $split_string as $str )
	{
		$output .= $otag . $str . $ctag;
	}
	return $output;
}

// Processes markup for attributes like labels, images and links
// !Label
// %image
function mpprecipe_format_item($item, $elem, $class, $itemprop, $id, $i) {

	if (preg_match("/^%(\S*)/", $item, $matches)) {	// IMAGE Updated to only pull non-whitespace after some blogs were adding additional returns to the output
		$output = '<img class = "' . $class . '-image" src="' . $matches[1] . '" />';
		return $output; // Images don't also have labels or links so return the line immediately.
	}

	if (preg_match("/^!(.*)/", $item, $matches)) {	// LABEL
		$class .= '-label';
		$elem = 'div';
		$item = $matches[1];
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" >';	// No itemprop for labels
	} else {
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" itemprop="' . $itemprop . '">';
	}

	$output .= mpprecipe_richify_item($item, $class);
	$output .= '</' . $elem . '>';

	return $output;
}

// Formats the recipe for output
function mpprecipe_format_recipe($recipe) {
    $output = "";
    $permalink = get_permalink();
	
	if("mpprecipe-design2" == get_option('mpprecipe_stylesheet') or "mpprecipe-design3" == get_option('mpprecipe_stylesheet') or "mpprecipe-design7" == get_option('mpprecipe_stylesheet') or "mpprecipe-design8" == get_option('mpprecipe_stylesheet') or "mpprecipe-design4" == get_option('mpprecipe_stylesheet') or "mpprecipe-design5" == get_option('mpprecipe_stylesheet')) {
	
			// Output main recipe div with border style
			$style_tag = '';
			$border_style = get_option('mpprecipe_outer_border_style');
			if ($border_style != null)
				$style_tag = 'style="border: ' . $border_style . ';"';
			$output .= '
			<div id="mpprecipe-container-' . $recipe->recipe_id . '" class="mpprecipe-container-border" ' . $style_tag . '>
			<div itemscope itemtype="http://schema.org/Recipe" id="mpprecipe-container" class="serif mpprecipe">
			  <div id="mpprecipe-innerdiv">
				<div class="item mpp-top">';

			$image_hide = strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0;	

			//!! Adjust to full width if no image

			if (!$recipe->recipe_image or $image_hide ) {


				$output .= "<style>
					#mpprecipe-container-$recipe->id .mpp-topleft {
						width: 100% !important;
					} 
					#mpprecipe-container-$recipe->id .mpp-topright {
						display:none !important;
					}
				</style>
				";


			}

			// Open mpp-topright panel if image
			if ($recipe->recipe_image != null || $recipe->summary != null)
				$output .= '<div class="mpp-topleft">';
	
			if ( $recipe->author ) {		
				/* AUTHOR LINK */		
				$output .= "<a href='#' itemprop='author' target='_blank' class='mpp-recipe-author'>$recipe->author</a>";		
				/* END AUTHOR LINK */		
			}

			 //!! yield and nutrition
			if ($recipe->yield != null and !$recipe->serving_size) {
				$output .= '<p id="mpprecipe-yield">';
				if (strcmp(get_option('mpprecipe_yield_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_yield_label') . ' ';
				}
				$output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
			}
		

			if ($recipe->serving_size != null ) 
			{
				$output .= '<div id="mpprecipe-nutrition" itemprop="nutrition" itemscope itemtype="http://schema.org/NutritionInformation">';
				if ($recipe->serving_size != null) {
					$output .= '<p id="mpprecipe-serving-size">';
					if (strcmp(get_option('mpprecipe_serving_size_label_hide'), 'Hide') != 0) {
						$output .= get_option('mpprecipe_serving_size_label') . ' ';
					}
					$output .= '<span itemprop="servingSize">' . $recipe->serving_size . '</span></p>';
				}
				$output .= '</div>';
			}

			// add the title and close the item class
			$hide_tag = '';
			if (strcmp(get_option('recipe_title_hide'), 'Hide') == 0)
				$hide_tag = ' texthide';
			$output .= '<div id="mpprecipe-title" itemprop="name" class="h-1' . $hide_tag . '" >' . $recipe->recipe_title . '</div>';


			
			
			if ($recipe->summary != null) {
					$output .= '<div id="mpprecipe-summary" itemprop="description">';
					$output .= mpprecipe_break( '<p class="summary">', mpprecipe_richify_item($recipe->summary, 'summary'), '</p>' );
					$output .= '</div>';
			}
			
			
			// open the zlmeta and fl-l container divs
			$output .= '<div class="fl-l">';

			if ($recipe->rating != 0) {
				$output .= '<p id="mpprecipe-rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
				if (strcmp(get_option('mpprecipe_rating_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_rating_label') . ' ';
				}
				$output .= '<span class="rating rating-' . $recipe->rating . '"><span itemprop="ratingValue">' . $recipe->rating . '</span><span itemprop="reviewCount" style="display: none;">1</span></span>
			   </p>';
			}

			// recipe timing
			if ($recipe->prep_time != null) {
				$prep_time = mpprecipe_format_duration($recipe->prep_time);

				$output .= '<p id="mpprecipe-prep-time">';
				$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span>';

				if (strcmp(get_option('mpprecipe_prep_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_prep_time_label') . ' ';
				}
				$output .= '</p>';
			}
			if ($recipe->cook_time != null) {
				$cook_time = mpprecipe_format_duration($recipe->cook_time);

				$output .= '<p id="mpprecipe-cook-time">';
				$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span>';

				if (strcmp(get_option('mpprecipe_cook_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_cook_time_label') . ' ';
				}
				$output .= '</p>';
			}


			$total_time         = null;
			$total_time_content = null;

			if ($recipe->total_time != null)
			{
				$total_time         = mpprecipe_format_duration($recipe->total_time);
				$total_time_content = $recipe->total_time;
			}
			elseif( ($recipe->prep_time || $recipe->cook_time ) and class_exists( 'DateInterval' ) and MPPRECIPE_AUTO_HANDLE_TOTALTIME )
			{ 
				$t1 = new DateTime();
				$t2 = new DateTime();

				if( $recipe->prep_time ) $t1->add( new DateInterval($recipe->prep_time));
				if( $recipe->cook_time ) $t1->add( new DateInterval($recipe->cook_time));

				$ti = $t2->diff($t1);
				$total_time_content = $ti->format('P%yY%mM%dDT%hH%iM%sS');
			}

			if( $total_time_content )
			{
				$total_time = mpprecipe_format_duration($total_time_content);
				$output .= '<p id="mpprecipe-total-time">';
				$output .= '<span itemprop="totalTime" content="' . $total_time_content . '">' . $total_time . '</span>';

				if (strcmp(get_option('mpprecipe_total_time_label_hide'), 'Hide') != 0) 
					$output .= get_option('mpprecipe_total_time_label') . ' ';

				$output .= '</p>';
			}
			
			
			if("mpprecipe-design2" == get_option('mpprecipe_stylesheet') or "mpprecipe-design4" == get_option('mpprecipe_stylesheet') or "mpprecipe-design7" == get_option('mpprecipe_stylesheet') or "mpprecipe-design8" == get_option('mpprecipe_stylesheet')) {
				// Add Print and Save Button
				$output .= mpp_buttons( $recipe->recipe_id );
		
				// add the MealPlannerPro recipe button
				if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
					$output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

				}
			}
			
		
			
			//!! close mpp-topright if there is an image
			if ($recipe->recipe_image != null || $recipe->summary != null) {
				$output .= '</div>';
			}
					$output .= '<div class="zlclear"></div></div>';


			//!! create image container
			$output .= '<div class="mpp-topright">';
			
			if("mpprecipe-design2" == get_option('mpprecipe_stylesheet') or "mpprecipe-design4" == get_option('mpprecipe_stylesheet') or "mpprecipe-design7" == get_option('mpprecipe_stylesheet') or "mpprecipe-design8" == get_option('mpprecipe_stylesheet')) {
				if ($recipe->recipe_image != null )
				{
					$class  = 'mpp-toprightimage';
					$style  = "background:url($recipe->recipe_image);background-size:cover;";

					if (strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0)
						$class .= ' hide-card';

					if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
						$class .= ' hide-print';

					//$output .= "<div class='$class' style='$style' ></div>";
	
					$output .= "<img class='photo' itemprop='image' src='$recipe->recipe_image' title='$recipe->recipe_title' alt='$recipe->recipe_title' />";


					// Marked up and hidden image for Schema/Microformat compliance.
					//$output .= "<img style='display:none' class='photo' itemprop='image' src='$recipe->recipe_image' title='$recipe->recipe_title' alt='$recipe->recipe_title' />";
				}
	
			}	
			
			if("mpprecipe-design3" == get_option('mpprecipe_stylesheet') or "mpprecipe-design5" == get_option('mpprecipe_stylesheet')) {
				if ($recipe->recipe_image != null )
				{
					$class  = 'mpp-toprightimage';
					$style  = "background:url($recipe->recipe_image);background-size:cover;";

					if (strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0)
						$class .= ' hide-card';

					if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
						$class .= ' hide-print';

					$output .= "<div class='$class' style='$style' ></div>";
	
					//$output .= "<img class='photo' itemprop='image' src='$recipe->recipe_image' title='$recipe->recipe_title' alt='$recipe->recipe_title' />";


					// Marked up and hidden image for Schema/Microformat compliance.
					$output .= "<img style='display:none' class='photo' itemprop='image' src='$recipe->recipe_image' title='$recipe->recipe_title' alt='$recipe->recipe_title' />";
				}
			
				// Add Print and Save Button
				$output .= mpp_buttons( $recipe->recipe_id );
		
				// add the MealPlannerPro recipe button
				if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
					$output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

				}
			}
			
			// close topright
			$output .= '</div>';

			//!! close the containers
			$output .= '<div class="zlclear"></div></div>';

			
		
			
	
	} elseif("mpprecipe-design6" == get_option('mpprecipe_stylesheet') or "mpprecipe-design9" == get_option('mpprecipe_stylesheet')) {
	
			// Output main recipe div with border style
			$style_tag = '';
			$border_style = get_option('mpprecipe_outer_border_style');
			if ($border_style != null)
				$style_tag = 'style="border: ' . $border_style . ';"';
			$output .= '
			<div id="mpprecipe-container-' . $recipe->recipe_id . '" class="mpprecipe-container-border" ' . $style_tag . '>
			<div itemscope itemtype="http://schema.org/Recipe" id="mpprecipe-container" class="serif mpprecipe">
			  <div id="mpprecipe-innerdiv">
				<div class="item mpp-top">';

			$image_hide = strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0;	

			//!! Adjust to full width if no image

			if (!$recipe->recipe_image or $image_hide ) {


				$output .= "<style>
					#mpprecipe-container-$recipe->id .mpp-topleft {
						width: 100% !important;
					} 
					#mpprecipe-container-$recipe->id .mpp-topright {
						display:none !important;
					}
				</style>
				";


			}

			// Open mpp-topright panel if image
			if ($recipe->recipe_image != null || $recipe->summary != null)
				$output .= '<div class="mpp-topleft">';
				
		

			if ($recipe->serving_size != null ) 
			{
				$output .= '<div id="mpprecipe-nutrition" itemprop="nutrition" itemscope itemtype="http://schema.org/NutritionInformation">';
				if ($recipe->serving_size != null) {
					$output .= '<p id="mpprecipe-serving-size">';
					if (strcmp(get_option('mpprecipe_serving_size_label_hide'), 'Hide') != 0) {
						$output .= get_option('mpprecipe_serving_size_label') . ' ';
					}
					$output .= '<span itemprop="servingSize">' . $recipe->serving_size . '</span></p>';
				}
				$output .= '</div>';
			}

			// add the title and close the item class
			$hide_tag = '';
			if (strcmp(get_option('recipe_title_hide'), 'Hide') == 0)
				$hide_tag = ' texthide';
			$output .= '<div id="mpprecipe-title" itemprop="name" class="h-1' . $hide_tag . '" >' . $recipe->recipe_title . '</div>';

			$output .= "<hr class='specialhr' />";
 			
 			if ( $recipe->author ) {		
				/* AUTHOR LINK */		
				$output .= "<a href='#' itemprop='author' target='_blank' class='mpp-recipe-author'>$recipe->author</a>";		
				/* END AUTHOR LINK */		
			}			
 			
 			//!! yield and nutrition
			if ($recipe->yield != null and !$recipe->serving_size) {
				$output .= '<p id="mpprecipe-yield">';
				if (strcmp(get_option('mpprecipe_yield_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_yield_label') . ' ';
				}
				$output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
			}
			
			
			
			if ($recipe->summary != null) {
					$output .= '<div id="mpprecipe-summary" itemprop="description">';
					$output .= mpprecipe_break( '<p class="summary">', mpprecipe_richify_item($recipe->summary, 'summary'), '</p>' );
					$output .= '</div>';
			}
			
			
			// open the zlmeta and fl-l container divs
			$output .= '<div class="fl-l">';

			if ($recipe->rating != 0) {
				$output .= '<p id="mpprecipe-rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
				if (strcmp(get_option('mpprecipe_rating_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_rating_label') . ' ';
				}
				$output .= '<span class="rating rating-' . $recipe->rating . '"><span itemprop="ratingValue">' . $recipe->rating . '</span><span itemprop="reviewCount" style="display: none;">1</span></span>
			   </p>';
			}

			// recipe timing
			if ($recipe->prep_time != null) {
				$prep_time = mpprecipe_format_duration($recipe->prep_time);

				$output .= '<p id="mpprecipe-prep-time">';
				$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span>';

				if (strcmp(get_option('mpprecipe_prep_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_prep_time_label') . ' ';
				}
				$output .= '</p>';
			}
			if ($recipe->cook_time != null) {
				$cook_time = mpprecipe_format_duration($recipe->cook_time);

				$output .= '<p id="mpprecipe-cook-time">';
				$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span>';

				if (strcmp(get_option('mpprecipe_cook_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_cook_time_label') . ' ';
				}
				$output .= '</p>';
			}


			$total_time         = null;
			$total_time_content = null;

			if ($recipe->total_time != null)
			{
				$total_time         = mpprecipe_format_duration($recipe->total_time);
				$total_time_content = $recipe->total_time;
			}
			elseif( ($recipe->prep_time || $recipe->cook_time ) and class_exists( 'DateInterval' ) and MPPRECIPE_AUTO_HANDLE_TOTALTIME )
			{ 
				$t1 = new DateTime();
				$t2 = new DateTime();

				if( $recipe->prep_time ) $t1->add( new DateInterval($recipe->prep_time));
				if( $recipe->cook_time ) $t1->add( new DateInterval($recipe->cook_time));

				$ti = $t2->diff($t1);
				$total_time_content = $ti->format('P%yY%mM%dDT%hH%iM%sS');
			}

			if( $total_time_content )
			{
				$total_time = mpprecipe_format_duration($total_time_content);
				$output .= '<p id="mpprecipe-total-time">';
				$output .= '<span itemprop="totalTime" content="' . $total_time_content . '">' . $total_time . '</span>';

				if (strcmp(get_option('mpprecipe_total_time_label_hide'), 'Hide') != 0) 
					$output .= get_option('mpprecipe_total_time_label') . ' ';

				$output .= '</p>';
			}
			
		
			
			
			//!! close mpp-topright if there is an image
			if ($recipe->recipe_image != null || $recipe->summary != null) {
				$output .= '</div>';
			}
					$output .= '<div class="zlclear"></div></div>';


			//!! create image container
			$output .= '<div class="mpp-topright">';
			
	
			
			if ($recipe->recipe_image != null )
			{
				$class  = 'mpp-toprightimage';
				$style  = "background:url($recipe->recipe_image);background-size:cover; border-radius: 100%;";

				if (strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0)
					$class .= ' hide-card';

				if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
					$class .= ' hide-print';

				$output .= "<div class='$class' style='$style' ></div>";

				//$output .= "<img class='photo' itemprop='image' src='$recipe->recipe_image' title='$recipe->recipe_title' alt='$recipe->recipe_title' />";


				// Marked up and hidden image for Schema/Microformat compliance.
				$output .= "<img style='display:none' class='photo' itemprop='image' src='$recipe->recipe_image' title='$recipe->recipe_title' alt='$recipe->recipe_title' />";
			}
			
			
			// close topright
			$output .= '</div>';

			//!! close the containers
			$output .= '<div class="zlclear"></div></div>';

		
			$output .= "<div class='bottombar'>";		
				
				// Add Print and Save Button
				$output .= mpp_buttons( $recipe->recipe_id );
		
				// add the MealPlannerPro recipe button
				if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
					$output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

				}
			
		
			$output .= "</div>";		

	}else{
	
			// Output main recipe div with border style
			$style_tag = '';
			$border_style = get_option('mpprecipe_outer_border_style');
			if ($border_style != null)
				$style_tag = 'style="border: ' . $border_style . ';"';
			$output .= '
			<div id="mpprecipe-container-' . $recipe->recipe_id . '" class="mpprecipe-container-border" ' . $style_tag . '>
			<div itemscope itemtype="http://schema.org/Recipe" id="mpprecipe-container" class="serif mpprecipe">
			  <div id="mpprecipe-innerdiv">
				<div class="item mpp-top">';

			$image_hide = strcmp(get_option('mpprecipe_image_hide'), 'Hide') == 0;

			//!! Adjust to full width if no image
			if (!$recipe->recipe_image or $image_hide ) {
	
		
				$output .= "<style>
					#mpprecipe-container-$recipe->id .mpp-topright {
						width: 100% !important;
						border-left: solid #cccccc 1px !important;
					} 
					#mpprecipe-container-$recipe->id .mpp-topleft {
						display:none !important;
					}
					#mpprecipe-container-$recipe->id .mpp-topright .fl-l {
						float:none !important;
					}
					#mpprecipe-container-$recipe->id div#mpp-buttons {
						float: none !important;
						margin: 0 auto !important;
						max-width: 284px !important;
					}
				</style>
				";

	
			}
	
			//!! create image container
	
			if ($recipe->recipe_image != null )
			{
				$class  = 'mpp-topleft';
				$style  = "background:url($recipe->recipe_image);background-size:cover;";

				if ($image_hide)
					$class .= ' hide-card';

				if (strcmp(get_option('mpprecipe_image_hide_print'), 'Hide') == 0)
					$class .= ' hide-print';

				$output .= "<div class='$class' style='$style' ></div>";

				// Marked up and hidden image for Schema/Microformat compliance.
				$output .= "<img style='display:none' class='photo' itemprop='image' src='$recipe->recipe_image' title='$recipe->recipe_title' alt='$recipe->recipe_title' />";
			}

			// Open mpp-topright panel if image
			if ($recipe->recipe_image != null || $recipe->summary != null)
				$output .= '<div class="mpp-topright">';
	
			if ( $recipe->author ) {		
				/* AUTHOR LINK */		
				$output .= "<a href='#' itemprop='author' target='_blank' class='mpp-recipe-author'>$recipe->author</a>";		
				/* END AUTHOR LINK */		
			}
			
			 //!! yield and nutrition
			if ($recipe->yield != null and !$recipe->serving_size) {
				$output .= '<p id="mpprecipe-yield">';
				if (strcmp(get_option('mpprecipe_yield_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_yield_label') . ' ';
				}
				$output .= '<span itemprop="recipeYield">' . $recipe->yield . '</span></p>';
			}

			
			
			if ($recipe->serving_size != null ) 
			{
				$output .= '<div id="mpprecipe-nutrition" itemprop="nutrition" itemscope itemtype="http://schema.org/NutritionInformation">';
				if ($recipe->serving_size != null) {
					$output .= '<p id="mpprecipe-serving-size">';
					if (strcmp(get_option('mpprecipe_serving_size_label_hide'), 'Hide') != 0) {
						$output .= get_option('mpprecipe_serving_size_label') . ' ';
					}
					$output .= '<span itemprop="servingSize">' . $recipe->serving_size . '</span></p>';
				}
				$output .= '</div>';
			}
	
			// add the title and close the item class
			$hide_tag = '';
			if (strcmp(get_option('recipe_title_hide'), 'Hide') == 0)
				$hide_tag = ' texthide';
			$output .= '<div id="mpprecipe-title" itemprop="name" class="h-1' . $hide_tag . '" >' . $recipe->recipe_title . '</div>';
	
			if ($recipe->summary != null) {
				$output .= '<div id="mpprecipe-summary" itemprop="description">';
				$output .= mpprecipe_break( '<p class="summary">', mpprecipe_richify_item($recipe->summary, 'summary'), '</p>' );
				$output .= '</div>';
			}

		
			// open the zlmeta and fl-l container divs
			$output .= '<div class="fl-l">';

			if ($recipe->rating != 0) {
				$output .= '<p id="mpprecipe-rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
				if (strcmp(get_option('mpprecipe_rating_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_rating_label') . ' ';
				}
				$output .= '<span class="rating rating-' . $recipe->rating . '"><span itemprop="ratingValue">' . $recipe->rating . '</span><span itemprop="reviewCount" style="display: none;">1</span></span>
			   </p>';
			}

			// recipe timing
			if ($recipe->prep_time != null) {
				$prep_time = mpprecipe_format_duration($recipe->prep_time);

				$output .= '<p id="mpprecipe-prep-time">';
				$output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span>';

				if (strcmp(get_option('mpprecipe_prep_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_prep_time_label') . ' ';
				}
				$output .= '</p>';
			}
			if ($recipe->cook_time != null) {
				$cook_time = mpprecipe_format_duration($recipe->cook_time);

				$output .= '<p id="mpprecipe-cook-time">';
				$output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span>';
		
				if (strcmp(get_option('mpprecipe_cook_time_label_hide'), 'Hide') != 0) {
					$output .= get_option('mpprecipe_cook_time_label') . ' ';
				}
				$output .= '</p>';
			}


			$total_time         = null;
			$total_time_content = null;

			if ($recipe->total_time != null)
			{
				$total_time         = mpprecipe_format_duration($recipe->total_time);
				$total_time_content = $recipe->total_time;
			}
			elseif( ($recipe->prep_time || $recipe->cook_time ) and class_exists( 'DateInterval' ) and MPPRECIPE_AUTO_HANDLE_TOTALTIME )
			{ 
				$t1 = new DateTime();
				$t2 = new DateTime();

				if( $recipe->prep_time ) $t1->add( new DateInterval($recipe->prep_time));
				if( $recipe->cook_time ) $t1->add( new DateInterval($recipe->cook_time));

				$ti = $t2->diff($t1);
				$total_time_content = $ti->format('P%yY%mM%dDT%hH%iM%sS');
			}

			if( $total_time_content )
			{
				$total_time = mpprecipe_format_duration($total_time_content);
				$output .= '<p id="mpprecipe-total-time">';
				$output .= '<span itemprop="totalTime" content="' . $total_time_content . '">' . $total_time . '</span>';

				if (strcmp(get_option('mpprecipe_total_time_label_hide'), 'Hide') != 0) 
					$output .= get_option('mpprecipe_total_time_label') . ' ';

				$output .= '</p>';
			}

		   // Add Print and Save Button
			$output .= mpp_buttons( $recipe->recipe_id );

			// add the MealPlannerPro recipe button
			if (strcmp(get_option('mealplannerpro_recipe_button_hide'), 'Hide') != 0) {
				$output .= '<div id="mpp-recipe-link-' . $recipe->recipe_id . '" class="mpp-recipe-link fl-r mpp-rmvd"></div>';

			}
	 
			//!! close mpp-topright if there is an image
			if ($recipe->recipe_image != null || $recipe->summary != null) {
				$output .= '</div>';
			}
					$output .= '<div class="zlclear"></div>';

			//!! close the containers
			$output .= '</div><div class="zlclear"></div></div>';
	
	}
	

    $ingredient_type= '';
    $ingredient_tag = '';
    $ingredient_class = '';
    $ingredient_list_type_option = get_option('mpprecipe_ingredient_list_type');
    if (strcmp($ingredient_list_type_option, 'ul') == 0 || strcmp($ingredient_list_type_option, 'ol') == 0) {
        $ingredient_type = $ingredient_list_type_option;
        $ingredient_tag = 'li';
    } else if (strcmp($ingredient_list_type_option, 'p') == 0 || strcmp($ingredient_list_type_option, 'div') == 0) {
        $ingredient_type = 'span';
        $ingredient_tag = $ingredient_list_type_option;
    }

    if (strcmp(get_option('mpprecipe_ingredient_label_hide'), 'Hide') != 0) {

          /* START BUTTON CHANGES */
          $subdomain = get_option('mpprecipe_subdomain');

          if( $subdomain and get_option('mpprecipe_personalizedplugin') )
          {
              $output .= "
                <center><div id='mpp-buttons-2'>
                  <a href='http://$subdomain.mealplannerpro.com/recipe/recipeBox' class='myrecipe-button mpp-button'>My Recipes</a>
                  <a href='http://$subdomain.mealplannerpro.com/grocery-list' class='mylist-button mpp-button'>My Lists</a>
                  <a href='http://$subdomain.mealplannerpro.com/meal-planning-calendar' class='mycal-button mpp-button'>My Calendar</a>
              </div></center>";
          }
          /* END BUTTON CHANGES */

        $output .= '<p id="mpprecipe-ingredients" class="h-4 strong">' . get_option('mpprecipe_ingredient_label') . '</p>';
    }

    $output .= '<' . $ingredient_type . ' id="mpprecipe-ingredients-list">';
    $i = 0;
    $ingredients = explode("\n", $recipe->ingredients);
    foreach ($ingredients as $ingredient) {
		$output .= mpprecipe_format_item($ingredient, $ingredient_tag, 'ingredient', 'ingredients', 'mpprecipe-ingredient-', $i);
        $i++;
    }

    $output .= '</' . $ingredient_type . '>';

	// add the instructions
    if ($recipe->instructions != null) {

        $instruction_type= '';
        $instruction_tag = '';
        $instruction_list_type_option = get_option('mpprecipe_instruction_list_type');
        if (strcmp($instruction_list_type_option, 'ul') == 0 || strcmp($instruction_list_type_option, 'ol') == 0) {
            $instruction_type = $instruction_list_type_option;
            $instruction_tag = 'li';
        } else if (strcmp($instruction_list_type_option, 'p') == 0 || strcmp($instruction_list_type_option, 'div') == 0) {
            $instruction_type = 'span';
            $instruction_tag = $instruction_list_type_option;
        }

        $instructions = explode("\n", $recipe->instructions);
        if (strcmp(get_option('mpprecipe_instruction_label_hide'), 'Hide') != 0) {
            $output .= '<p id="mpprecipe-instructions" class="h-4 strong">' . get_option('mpprecipe_instruction_label') . '</p>';
        }
        $output .= '<' . $instruction_type . ' id="mpprecipe-instructions-list" class="instructions">';
        $j = 0;
        foreach ($instructions as $instruction) {
            if (strlen($instruction) > 1) {
            	$output .= mpprecipe_format_item($instruction, $instruction_tag, 'instruction', 'recipeInstructions', 'mpprecipe-instruction-', $j);
                $j++;
            }
        }
        $output .= '</' . $instruction_type . '>';
    }


    if ( $recipe->cuisine || $recipe->type ) {
        /* TAGS */
        $output .= "<div class='recipe-bottomtags'>";

            /*
            if ( $recipe->course )
                $output .= "<strong>Course:</strong> $recipe->course <span>|</span>";
            */

            if ( $recipe->cuisine )
                $output .= "<strong>Cuisine:</strong> $recipe->cuisine <span>|</span> ";

            if ( $recipe->type )
                $output .= "<strong>Recipe Type:</strong> $recipe->type";

        $output .= "</div>";
        /* END TAGS */
    }

    //!! add notes section
    if ($recipe->notes != null) {
        if (strcmp(get_option('mpprecipe_notes_label_hide'), 'Hide') != 0) {
            $output .= '<p id="mpprecipe-notes" class="h-4 strong">' . get_option('mpprecipe_notes_label') . '</p>';
        }

		$output .= '<div id="mpprecipe-notes-list">';
		$output .= mpprecipe_break( '<p class="notes">', mpprecipe_richify_item($recipe->notes, 'notes'), '</p>' );
		$output .= '</div>';

	}

	// MealPlannerPro version
    $output .= '<div class="mealplannerpro-recipe-plugin" style="display: none;">' . MPPRECIPE_VERSION_NUM . '</div>';

    // Add permalink for printed output before closing the innerdiv
    if (strcmp(get_option('mpprecipe_printed_permalink_hide'), 'Hide') != 0) {
		$output .= '<a id="mpp-printed-permalink" href="' . $permalink . '"title="Permalink to Recipe">' . $permalink . '</a>';
	}

    $output .= '</div>';

    // Add copyright statement for printed output (outside the dotted print line)
    $printed_copyright_statement = get_option('mpprecipe_printed_copyright_statement');
    if (strlen($printed_copyright_statement) > 0) {
		$output .= '<div id="mpp-printed-copyright-statement" itemprop="copyrightHolder">' . $printed_copyright_statement . '</div>';
	}

	$output .= '</div></div>';

    return $output;
}

function mpp_save_recipe_js( $subdomain = null )
{
    if( $subdomain and get_option('mpprecipe_personalizedplugin') )
        return "window.open('http://${subdomain}mealplannerpro.com/clipper/direct?url=' + window.location.href); return false;";
    else
        return "javascript:(function(){var host='http://mealplannerpro.com/';var s=document.createElement('script');s.type= 'text/javascript';try{if (!document.body) throw (0);s.src=host + '/javascripts/savebutton.js?date='+(new Date().getTime());document.body.appendChild(s);}catch (e){alert('Please try again after the page has finished loading.');}})();";
}

/* 
 * Add Mealplannerpro.com buttons.
 */
function mpp_buttons( $recipe_id )
{
    $subdomain = get_option('mpprecipe_subdomain');
    if( $subdomain ) 
        $subdomain .= '.';

    $dir = MPPRECIPE_PLUGIN_DIRECTORY;

    $custom_print_image = get_option('mpprecipe_custom_print_image');
    $button_type  = 'butn-link';
    $button_image = "";
    if (strlen($custom_print_image) > 0) {
        $button_type  = 'print-link';
        $button_image = '<img src="' . $custom_print_image . '">';
    }

    $hide = "style = 'display:none;'";
    if (strcmp(get_option('mpprecipe_print_link_hide'), 'Hide') != 0) 
        $hide = '';

    return "
        <div id='mpp-buttons'>

  			
            <div
               class = 'save-button mpp-button'
               title = 'Save Recipe to Mealplannerpro.com'
               alt   = 'Save Recipe to Mealplannerpro.com'
               href  = 'http://${subdomain}mealplannerpro.com/clipper/direct'
               onclick=\"" . mpp_save_recipe_js( $subdomain ) . "\"
            ><img src='" . $dir . "plus.png' style='margin-top:-1px;' /> Save Recipe
            </div>

          <div 
                class   = '$button_type mpp-button' 
                title   = 'Print this recipe'
                onclick = 'zlrPrint( \"mpprecipe-container-$recipe_id\", \"$dir\" ); return false'
                $hide
                >
                Print Recipe
                $button_image
            </div>

        </div>

        <style> 
            div#mpp-buttons { float:right; margin-top: 10px;  }
            .mpp-button  { display:inline-block; }


        </style>
        ";
}



/**
 *************************************************************
 * Conversion functionality
 *************************************************************
 */

/**
 * Iterates through the Ziplist recipe table, copying every Ziplist recipe to 
 * the Mealplanner pro recipe table, then updates the Wordpress posts to use Mealplanner pro 
 * placemarkers in-place of Ziplist's.
 */
function mpp_convert_js()
{
    return "<script type='text/javascript'>

            convert_entries = function( vendor, revert )
            {
                revert = revert || false

                var lvendor = vendor.toLowerCase()
                var c = confirm( 'Click OK to begin converting your recipes. Please ensure you have a backup of your database or posts before continuing.' )

                if (!c)
                    return

                var action = 'convert'

                if (revert)
                    action = 'revert'

                var data = '?action=' + action + '_' + lvendor + '_entries'

                var r   = new XMLHttpRequest()
                r.open( 'GET', ajaxurl+data, true )

                var cid = action + '_' + lvendor + '_entries_container'

                document.getElementById(cid).innerHTML = 'Converting recipes. This can take a few minutes, please do not leave the page.'
                window.onbeforeunload = function () { return 'Recipes are still being converted, if you leave this page you will not know if it was successful.' };

                r.onreadystatechange = function() 
                {
                    if( r.readyState == 4 && r.status == 200 )
                        document.getElementById(cid).innerHTML = r.responseText;

                    window.onbeforeunload = null
                }
                r.send()
            }
        </script>";
}

function mpp_convert_ziplist_like_entries( $table, $name )
{
    global $wpdb;

    $lname     = strtolower( $name );
    $zl_table  = $wpdb->prefix.$table;
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table  = $wpdb->prefix.'posts';

    # Assume placemarker = tablename wth hyphens for underscores and non-plural. 
    # e.g. amd_zlrecipe_recipes -> amd-zlrecipe-recipe:
    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s"); 
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";

    $zlrecipes = $wpdb->get_results("
        SELECT *
        FROM $wp_table p 
        LEFT JOIN $zl_table z
            ON  p.ID    = z.post_id 
        WHERE 
            p.post_content REGEXP '$zl_placemarker_regex_general'
            AND p.post_status = 'publish'
    ");


    if( empty($zlrecipes) )
    {
        print "No $name recipes to convert.";
        die();
    }
        
    $count  = 0;
    $errors = array();
    foreach( $zlrecipes as $zlrecipe )
    {
        $zl_placemarker = "[$placemarker_name:$zlrecipe->recipe_id]";
        $data = array(
            'post_id'       => $zlrecipe->post_id,      'recipe_title'  => $zlrecipe->recipe_title,
            'recipe_image'  => $zlrecipe->recipe_image, 'summary'       => $zlrecipe->summary,
            'rating'        => $zlrecipe->rating,       'prep_time'     => $zlrecipe->prep_time,
            'cook_time'     => $zlrecipe->cook_time,    'total_time'    => $zlrecipe->total_time,
            'serving_size'  => $zlrecipe->serving_size, 'ingredients'   => $zlrecipe->ingredients,
            'instructions'  => $zlrecipe->instructions, 'notes'         => $zlrecipe->notes,
            'created_at'    => $zlrecipe->created_at,   'yield'         => $zlrecipe->yield,
            'calories'      => $zlrecipe->calories,     'fat'           => $zlrecipe->fat,
            'original'      => $zlrecipe->post_content, 'original_type' => $lname,
            'original_excerpt' => $zl_placemarker
        );

        $success = $wpdb->insert( $mpp_table, $data );

        if (!$success )
        {
            $errors[] = array( $zlrecipe->post_id, $zlrecipe->recipe_id );
            continue;
        }

        $mpp_recipe_id = $wpdb->insert_id;
        $mpp_placemarker = "[mpprecipe-recipe:$mpp_recipe_id]";

        $mpp_post = str_replace( $zl_placemarker, $mpp_placemarker, $zlrecipe->post_content );

        $wpdb->update( 
            $wp_table, 
            array( 'post_content' => $mpp_post ), 
            array( 'ID' => $zlrecipe->post_id )
        );
        $count += 1;
    }

    if( !empty( $errors ) )
    {
        print "Converted with some errors. <br/>";
        print "Could not convert ";
        print "<ul>";
        foreach( $errors as $pair )
            print "<li>recipe with title '$pair[1]' from Post titlted '$pair[0]'</li>";
        print "</ul>";
    }
    else
        print "Converted $count $name recipe(s) into Mealplanner Pro recipes!";

    die();
}
function mpp_convert_ziplist_entries()
{
    mpp_convert_ziplist_like_entries( 'amd_zlrecipe_recipes', 'Ziplist' );
}
function mpp_convert_yummly_entries()
{
    mpp_convert_ziplist_like_entries( 'amd_yrecipe_recipes', 'Yummly' );
}
function mpp_revert_yummly_entries()
{
    mpp_revert_ziplist_like_entries( 'amd_yrecipe_recipes', 'Yummly' );
}
function mpp_revert_ziplist_entries()
{
    mpp_revert_ziplist_like_entries( 'amd_zlrecipe_recipes', 'Ziplist' );
}

function mpp_restore()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table    = $wpdb->prefix.'posts';

    $count = 0;
    $mpps         = $wpdb->get_results(
        "SELECT * 
        FROM $wp_table p 
        JOIN $mpp_table m ON p.ID = m.post_id 
        WHERE m.original IS NOT NULL
        ORDER BY m.recipe_id DESC
        "
    );
    $processed_ids = array();

    foreach( $mpps as $mpp )
    {
        if( in_array( $mpp->ID, $processed_ids ) )
            continue;

        $wpdb->update( 
            $wp_table, 
            array( 'post_content' => $mpp->original ), 
            array( 'ID' => $mpp->ID )
        );
        $processed_ids[] = $mpp->ID;
        $count += 1;
    }
    print "Restored $count posts to pre-conversion state.";
    die();
}
function mpp_revert_easyrecipe_entries()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table    = $wpdb->prefix.'posts';

    $count = 0;
    # If original no longer present but stored in mpp record, assumed deleted.
    $mpps         = $wpdb->get_results(
        "SELECT * 
        FROM $wp_table p 
        JOIN $mpp_table m ON p.ID = m.post_id 
        WHERE 
            p.post_content NOT REGEXP '<div class=\"easyrecipe[ \"]'
            AND 
            m.original IS NOT NULL
            AND 
            m.original_type = 'easyrecipe'
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");

    $processed_ids = array();
    foreach( $mpps as $mpp )
    {
        # Match easyrecipe content within post.
        $matches = array();
        $pattern = mpp_get_pattern('mpp');

        preg_match( $pattern, $mpp->post_content, $matches );

        if( empty( $matches ) )
            continue;

        # Support for anomalous version
        if( !$mpp->original_excerpt )
        {
            # if consists of excerpt only, if entire post
            if( strpos( $mpp->original, '<div class="easyrecipe' ) == 0 )
                $original_excerpt = $mpp->original;
            else
                $original_excerpt = mpp_extract_easyrecipe( 'easyrecipe' );
        }
        else
            $original_excerpt = $mpp->original_excerpt;

        $old_post = preg_replace( "$pattern", "$original_excerpt", $mpp->post_content );

        if( in_array( $mpp->ID, $processed_ids ) )
            continue;

        $wpdb->update( 
            $wp_table, 
            array( 'post_content' => $old_post ), 
            array( 'ID' => $mpp->ID )
        );
        $processed_ids[] = $mpp->ID;
        $count += 1;
    }
    print "Converted $count Meal Planner Pro recipe(s) into EasyRecipe recipes!";
    die();
}
//Will only return single match
function mpp_get_pattern( $type )
{
    if ($type == 'easyrecipe')
       return '#<div class="easyrecipe ?.*?".*<div class="endeasyrecipe".*?</div>\s*</div>#s';
    elseif ($type == 'mpp')
        return '#\[mpprecipe-recipe:\d+\]#s';
}

function mpp_extract_easyrecipe( $post )
{
    $pattern = mpp_get_pattern('easyrecipe');
    preg_match( "$pattern", $post, $matches );

    if( empty($matches) )
        return False;
    else
        return $matches[0];
}
function mpp_convert_easyrecipe_entries()
{
    global $wpdb;

    $wp_table  = $wpdb->prefix.'posts';
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $count     = 0;

    $easyrecipes = $wpdb->get_results("
        SELECT * FROM $wp_table p
        WHERE 
            p.post_content REGEXP '<div class=\"easyrecipe[ \"]'
            AND p.post_status = 'publish'
    ");

    $originals = array();
    $excerpts  = array();
    foreach( $easyrecipes as $easyrecipe )
    {
        # Match easyrecipe content within post.
        $easyrecipe_excerpt = mpp_extract_easyrecipe( $easyrecipe->post_content );

        if( !$easyrecipe_excerpt )
            continue;

        $originals[ $easyrecipe->ID ] = $easyrecipe->post_content;
        $excerpts[  $easyrecipe->ID ] = $easyrecipe_excerpt;
    }

    if( !empty($excerpts) )
    {
        $html_batch_json  = json_encode( $excerpts );
        $easyrecipes_conv = mpp_convert_easyrecipe_call( $excerpts );

        foreach( $easyrecipes_conv as $id => $recipe )
        {
            $data = array(
                'post_id'       => $id,                   'recipe_title'  => $recipe->name,
                'recipe_image'  => $recipe->image,        'summary'       => $recipe->summary,
                'rating'        => $recipe->rating,       'prep_time'     => $recipe->prepTime,
                'cook_time'     => $recipe->cookTime,     'total_time'    => $recipe->totalTime,
                'serving_size'  => $recipe->serving_size, 'ingredients'   => $recipe->ingredients,
                'instructions'  => $recipe->instructions, 'notes'         => $recipe->note,
                'created_at'    => null,                  'yield'         => $recipe->recipeYield,
                'calories'      => $recipe->calories,     'fat'           => $recipe->fat,
                'author'        => $recipe->author,       'cuisine'       => $recipe->cuisine,
                'type'          => $recipe->type,

                'original'          => $originals[ $id ],
                'original_excerpt'  => $excerpts[ $id ],
                'original_type'     => 'easyrecipe'
            );

            $success = $wpdb->insert( $mpp_table, $data );

            if( $success )
            {
                $pattern        = mpp_get_pattern('easyrecipe');
                $converted_post = preg_replace( "$pattern", "[mpprecipe-recipe:$wpdb->insert_id]", $originals[$id] );
                $wpdb->update(
                    $wp_table, 
                    array( 'post_content' => $converted_post ), 
                    array( 'ID' => $id )
                );
                $count += 1;
            }
        }
    }

    print "Converted $count EasyRecipe recipe(s) into Mealplanner Pro recipes!";
    die();
}

// Serialize and batch convert
function mpp_convert_easyrecipe_call( $excerpts )
{
    $url  = 'http://mealplannerpro.com/api/clipper/erparsebatch';
    $html_batch_json = json_encode( $excerpts );
    $data = array('html' => $html_batch_json );

    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ),
    );

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode( $response );
}

add_action( 'wp_ajax_convert_easyrecipe_entries', 'mpp_convert_easyrecipe_entries' );
function mpp_convert_easyrecipe_entries_form()
{
    global $wpdb;

    $wp_table    = $wpdb->prefix.'posts';
    $easyrecipes = $wpdb->get_var("
        SELECT count(*) as count FROM $wp_table p 
        WHERE 
        p.post_content REGEXP '<div class=\"easyrecipe[ \"]'
        AND p.post_status = 'publish'
    ");

    if ( $easyrecipes == 0 )
        return;

    return ("
        <div id='convert_easyrecipe_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> EasyRecipe Data Detected </h4>
            <p>
                Found $easyrecipes recipe(s).
                Press this button if you wish to convert all your existing EasyRecipe recipes to Mealplanner Pro recipes. 
            </p>
            <button onclick='convert_entries(\"EasyRecipe\")'>Convert EasyRecipe Recipes</button>
            <p>
                The content of all your posts will be the same except Mealplanner Pro will
                be used instead of EasyRecipe for both display and editing of existing recipes created through the EasyRecipe plugin.
            </p>
        </div>
    ");
}

// Convert ziplist derivatives to mpp
function mpp_convert_ziplist_like_entries_form( $table, $name )
{
    global $wpdb;

    $lname = strtolower( $name );

    $zl_table  = $wpdb->prefix.$table;
    $mpp_table = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table  = $wpdb->prefix.'posts';

    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s"); 
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";

    # Select all recipes with placemarker match, but only published recipes.
    $zlrecipes         = $wpdb->get_var(
        "SELECT count( distinct p.ID)
        FROM $wp_table p 
        WHERE 
            p.post_content REGEXP '$zl_placemarker_regex_general'
            AND p.post_status = 'publish'
    ");

    if ( $zlrecipes == 0 )
        return;

    return ("
        <div id='convert_${lname}_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> $name Data Detected </h4>
            <p>
                Found $zlrecipes $name recipes.
                Press this button if you wish to convert all your existing $name recipes to Mealplanner Pro recipes. 
            </p>
            <button onclick='convert_entries(\"$name\")'>Convert $name Recipes</button>
            <p>
                The content of all your posts will be the same except Mealplanner Pro will
                be used instead of $name for both display and editing of existing recipes created through the $name plugin.
            </p>
        </div>
    ");
}

// Convert ziplist to mpp
add_action( 'wp_ajax_convert_ziplist_entries', 'mpp_convert_ziplist_entries' );
function mpp_convert_ziplist_entries_form()
{
    return mpp_convert_ziplist_like_entries_form( 'amd_zlrecipe_recipes', 'Ziplist' );
}
// Convert yummly to mpp
add_action( 'wp_ajax_convert_yummly_entries', 'mpp_convert_yummly_entries' );
function mpp_convert_yummly_entries_form()
{
    return mpp_convert_ziplist_like_entries_form( 'amd_yrecipe_recipes', 'Yummly' );
}

// revert yummly
add_action( 'wp_ajax_revert_yummly_entries', 'mpp_revert_yummly_entries' );
function mpp_revert_yummly_entries_form()
{
    return mpp_revert_ziplist_like_form( 'amd_yrecipe_recipes', 'Yummly' );
}
// revert ziplist
add_action( 'wp_ajax_revert_ziplist_entries', 'mpp_revert_ziplist_entries' );
function mpp_revert_ziplist_entries_form()
{
    return mpp_revert_ziplist_like_form( 'amd_zlrecipe_recipes', 'Ziplist' );
}

// revert easyrecipe
add_action( 'wp_ajax_revert_easyrecipe_entries', 'mpp_revert_easyrecipe_entries' );
function mpp_revert_easyrecipe_entries_form()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';

    $wp_table    = $wpdb->prefix.'posts';
    # If original no longer present but stored in mpp record, assumed deleted.
    $mpp         = $wpdb->get_var(
        "SELECT count( distinct p.ID)
        FROM $wp_table p 
        JOIN $mpp_table m ON p.ID = m.post_id 
        WHERE 
            p.post_content NOT REGEXP '<div class=\"easyrecipe[ \"]'
            AND 
            m.original IS NOT NULL
            AND 
            m.original_type = 'easyrecipe'
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");

    if ( $mpp == 0 )
        return;

    return ("
        <div id='revert_easyrecipe_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> Convert back from Meal Planner Pro to EasyRecipe </h4>
            <p>
                Found $mpp recipe(s).
                Press this button if you wish to reverse Meal Planner Pro recipes converted from EasyRecipe back to EasyRecipe recipes. 
            </p>
            <button onclick='convert_entries(\"EasyRecipe\", true)'>Convert Meal Planner Pro Recipes</button>
        </div>
    ");
}

function mpp_revert_ziplist_like_entries( $table, $name )
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';
    $wp_table    = $wpdb->prefix.'posts';
    $lname       = strtolower($name);

    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s"); 
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";

    # Find mpp recipes converted from named third party (original !null), where original post no longer contains third party placemarker (! placemarker)
    $mpps         = $wpdb->get_results(
        "SELECT *
        FROM $wp_table p 
        JOIN $mpp_table m ON p.ID = m.post_id 
        WHERE 
            p.post_content NOT REGEXP '$zl_placemarker_regex_general'
            AND 
            m.original IS NOT NULL
            AND 
            m.original_type = '$lname'
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");

    $count = 0;
    $processed_ids = array();
    foreach( $mpps as $mpp )
    {
        $matches = array();
        $pattern = mpp_get_pattern('mpp');

        preg_match( $pattern, $mpp->post_content, $matches );

        if( empty( $matches ) )
            continue;

        $zl_placemarker  = $mpp->original_excerpt;
        $mpp_placemarker = "[mpprecipe-recipe:$mpp->recipe_id]";

        $old_post        = str_replace( $mpp_placemarker, $zl_placemarker, $mpp->post_content );

        if( in_array( $mpp->ID, $processed_ids ) )
            continue;

        $wpdb->update( 
            $wp_table, 
            array( 'post_content' => $old_post ), 
            array( 'ID' => $mpp->ID )
        );
        $processed_ids[] = $mpp->ID;
        $count += 1;
    }
    print "Converted $count Meal Planner Pro recipe(s) into $name recipes!";
    die();
}

function mpp_revert_ziplist_like_form( $table, $name )
{
    global $wpdb;
    $lname       = strtolower($name);
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';

    $wp_table    = $wpdb->prefix.'posts';

    $placemarker_name     = trim(str_replace( '_', '-', $table ),"s"); 
    $zl_placemarker_regex_general = "$placemarker_name:[0-9]+";

    $mpp       = $wpdb->get_var(
        "SELECT count( distinct p.ID )
        FROM $wp_table p 
        JOIN $mpp_table m ON p.ID = m.post_id 
        WHERE 
            p.post_content NOT REGEXP '$zl_placemarker_regex_general'
            AND 
            m.original IS NOT NULL
            AND 
            m.original_type = '$lname'
            AND p.post_status = 'publish'
        ORDER BY m.recipe_id DESC
    ");

    if ( $mpp == 0 )
        return;

    return ("
        <div id='revert_${lname}_entries_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> Convert back from Meal Planner Pro to $name </h4>
            <p>
                Found $mpp recipe(s).
                Press this button if you wish to reverse Meal Planner Pro recipes converted from $name back to $name recipes. 
            </p>
            <button onclick='convert_entries(\"$lname\", true)'>Convert Meal Planner Pro Recipes</button>
        </div>
    ");
}

// Restore all converted posts
add_action( 'wp_ajax_mpp_restore', 'mpp_restore' );
function mpp_restore_form()
{
    global $wpdb;
    $mpp_table   = $wpdb->prefix.'mpprecipe_recipes';

    $wp_table    = $wpdb->prefix.'posts';
    # If original no longer present but stored in mpp record, assumed deleted.
    $mpp         = $wpdb->get_results(
        "SELECT * 
        FROM $wp_table p 
        JOIN $mpp_table m ON p.ID = m.post_id 
        WHERE 
            m.original IS NOT NULL
            LIMIT 1"
    );

    if ( !$mpp )
        return;

    return ("
        <div id='mpp_restore_container' style='padding: 15px; background: #ddd; border: 1px dashed #ccc; width: 50%;'>
            <h4> Revert Conversions </h4>
            <p>
                Press this button to undo changes that occured after converting to Meal Planner Pro.
            </p>
            <button onclick='mpp_restore()'>Restore Posts</button>
        </div>
        <script>
            mpp_restore = function()
            {
                var c = confirm( 'Click OK to restore converted posts to their pre-conversion state. Please ensure you have a backup of your database or posts before continuing.' )

                if (!c)
                    return

                var data = '?action=mpp_restore'

                var r   = new XMLHttpRequest()
                var cid = 'mpp_restore_container'
                r.open( 'GET', ajaxurl+data, true )

                document.getElementById(cid).innerHTML = 'Restoring posts. This can take a few minutes, please do not leave the page.'
                window.onbeforeunload = function () { return 'Posts are still being restored, if you leave this page you will not receive a success message.' };

                r.onreadystatechange = function() 
                {
                    if( r.readyState == 4 && r.status == 200 )
                        document.getElementById(cid).innerHTML = r.responseText;

                    window.onbeforeunload = null
                }
                r.send()
            }
        </script>
    ");
}
