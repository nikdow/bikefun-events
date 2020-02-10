<?php
/**
 * Plugin Name: Bikefun Events
 * Plugin URI: http://www.cbdweb.net
 * Description: Modified from http://www.noeltock.com/web-design/wordpress/how-to-custom-post-types-for-events-pt-2/
 * Version: 1.0
 * Author: Nik Dow, CBDWeb
 * Author URI: http://www.cbdweb.net
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * https://code.google.com/p/ics-parser/
 */
defined('ABSPATH') or die("No script kiddies please!");

function event_rewrite_tags() {
    global $wp_rewrite;

    add_rewrite_tag( '%bf_events_year%', '([0-9]{4})' );
    add_rewrite_tag( '%bf_events_month%', '([0-9]{2})' );
//    add_rewrite_rule(  '^events/([0-9]{4})/([0-9]{2})/([^/]+)?', 'index.php?post_type=bf_events&pagename=$matches[3]', 'top' );
}
add_action('init', 'event_rewrite_tags');

add_filter('post_type_link', 'event_permalink', 10, 4);

function event_permalink($permalink, $post, $leavename) {
    if ( get_post_type( $post ) === "bf_events" ) {
        $sd = get_post_meta( $post->ID, 'bf_events_startdate', true);
//        $year = date('Y', $sd + get_option( 'gmt_offset' ) * 3600);
//        $month = date('m', $sd + get_option( 'gmt_offset' ) * 3600);
        $startDT = new DateTime();
        
        $startDT->setTimestamp( (int) $sd );
        $startDT->setTimezone( new DateTimeZone ( get_option( 'timezone_string' ) ) );
        $year = $startDT->format( 'Y' );
        $month = $startDT->format ( 'm' );

        $rewritecode = array(
         '%bf_events_year%',
         '%bf_events_month%',
         $leavename? '' : '%postname%',
        );

        $rewritereplace = array(
         $year,
         $month,
         $post->post_name
        );

        $permalink = str_replace($rewritecode, $rewritereplace, $permalink);
    }
    return $permalink;
}

// 1. Custom Post Type Registration (Events)

add_action( 'init', 'create_event_postype' );
 
function create_event_postype() {
 
    $labels = array(
        'name' => _x('Events', 'post type general name'),
        'singular_name' => _x('Event', 'post type singular name'),
        'add_new' => _x('Add New', 'events'),
        'add_new_item' => __('Add New Event'),
        'edit_item' => __('Edit Event'),
        'new_item' => __('New Event'),
        'view_item' => __('View Event'),
        'search_items' => __('Search Events'),
        'not_found' =>  __('No events found'),
        'not_found_in_trash' => __('No events found in Trash'),
        'parent_item_colon' => '',
    );

    $args = array(
        'label' => __('Events'),
        'labels' => $labels,
        'public' => true,
        'can_export' => true,
        'show_ui' => true,
        '_builtin' => false,
        'capability_type' => 'post',
        'menu_icon' => plugins_url( 'img/calendar.gif', __FILE__ ),
        'hierarchical' => false,
        'has_archive' => 'bf_events',
        'rewrite' => array(
            'slug' => 'events/%bf_events_year%/%bf_events_month%',
            'with_front' => false
         ),
        'publicly_queryable' => true,
        'query_var' => true,
        'supports'=> array('title', 'thumbnail', 'editor', 'comments' ) ,
        'show_in_nav_menus' => true,
        'taxonomies' => array( 'tf_eventcategory', 'post_tag')
    );

    register_post_type( 'bf_events', $args);
 
}

/**
 * @param $query WP_Query
 */
function wpd_single_event_queries( $query ){
    if( $query->is_singular()
        && $query->is_main_query()
        && isset( $query->query_vars['bf_events'] ) ){
            $meta_query = array(
                array(
                    'key'     => 'bf_events_year',
                    'value'   => $query->query_vars['bf_events_year'],
                    'compare' => '=',
                    'type'    => 'numeric',
                ),
                array(
                    'key'     => 'bf_events_month',
                    'value'   => $query->query_vars['bf_events_month'],
                    'compare' => '=',
                    'type'    => 'numeric',
                ),
            );
            $query->set( 'meta_query', $meta_query );
    }
}
add_action( 'pre_get_posts', 'wpd_single_event_queries' );
 
function create_eventcategory_taxonomy() {
 
$labels = array(
    'name' => _x( 'Categories', 'taxonomy general name' ),
    'singular_name' => _x( 'Category', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Categories' ),
    'popular_items' => __( 'Popular Categories' ),
    'all_items' => __( 'All Categories' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Category' ),
    'update_item' => __( 'Update Category' ),
    'add_new_item' => __( 'Add New Category' ),
    'new_item_name' => __( 'New Category Name' ),
    'separate_items_with_commas' => __( 'Separate categories with commas' ),
    'add_or_remove_items' => __( 'Add or remove categories' ),
    'choose_from_most_used' => __( 'Choose from the most used categories' ),
);
 
register_taxonomy('bf_eventcategory','bf_events', array(
    'label' => __('Event Category'),
    'labels' => $labels,
    'hierarchical' => true,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'events',
        'with_front'=>false),
));
}
 
add_action( 'init', 'create_eventcategory_taxonomy', 0 );

// 3. Show Columns

add_filter ("manage_edit-bf_events_columns", "bf_events_edit_columns");
add_action ("manage_posts_custom_column", "bf_events_custom_columns");
/**
 * @param $query WP_Query
 *
 * Sort posts in wp_list_table by column in ascending or descending order.
 * http://wordpress.stackexchange.com/questions/66455/how-to-change-order-of-posts-in-admin
 */
function custom_post_order($query){
    if(!is_admin()) return;
    /* The current post type. */
    $post_type = $query->get('post_type');
    /* Check post types. */
    if($post_type==="bf_events"){
        /* Post Column: e.g. title */
        if($query->get('orderby') == ''){
            $query->set('meta_key', 'bf_events_startdate');
            $query->set('orderby', 'meta_value');
        }
        /* Post Order: ASC / DESC */
//        if($query->get('order') == ''){
            $query->set('order', 'DESC');
//        }
    }
}
if(is_admin()){
    add_action('pre_get_posts', 'custom_post_order');
}

function bf_events_edit_columns($columns) {
 
$columns = array(
    "cb" => "<input type=\"checkbox\" />",
    "bf_col_ev_date" => "Dates",
    "bf_col_ev_times" => "Times",
//    "tf_col_ev_thumb" => "Thumbnail",
    "title" => "Event",
    "date" => __( 'Date' ),
    );
return $columns;
}
 
function bf_events_custom_columns($column)
{
global $post;
$custom = get_post_custom();

$class = "class=\"$column column-$column\"";
$style = '';
$attributes = "$class$style";

switch ($column)
{
case "bf_col_ev_cat":
    // - show taxonomy terms -
    $eventcats = get_the_terms($post->ID, "tf_eventcategory");
    $eventcats_html = array();
    if ($eventcats) {
    foreach ($eventcats as $eventcat)
    array_push($eventcats_html, $eventcat->name);
    echo implode($eventcats_html, ", ");
    } else {
    _e('None', 'themeforce');;
    }
break;
case "bf_col_ev_date":
    // - show dates -
    $startd = $custom["bf_events_startdate"][0];
    if( isset ($custom["bf_events_enddate"])) {
        $endd = $custom["bf_events_enddate"][0];
        $endDT = new DateTime();
        $endDT->setTimestamp( $endd );
        $endDT->setTimezone( new DateTimeZone ( get_option( 'timezone_string' ) ) );
        $endout = $endDT->format( "D, M j, Y" );
        $enddate = $endDT->format( "j F Y" ) ;
    } else {
        $enddate = "";
    }
    
    $startDT = new DateTime();
    $startDT->setTimestamp ( $startd );
    $startDT->setTimezone( new DateTimeZone ( get_option( 'timezone_string' ) ) );
    $startdate = $startDT->format( "D, j F Y" );
    
    echo $startdate . '<br /><em>' . $enddate . '</em>';
break;
case "bf_col_ev_times":
    // - show times -
    $startt = $custom["bf_events_startdate"][0];
    $time_format = get_option('time_format');
    if( isset ($custom["bf_events_enddate"])) {
        $endt = $custom["bf_events_enddate"][0];
        $endDT = new DateTime();
        $endDT->setTimestamp( $endt );
        $endDT->setTimezone( new DateTimeZone ( get_option( 'timezone_string' ) ) );
        $endtime = $endDT->format( $time_format );
    } else {
        $endtime = "";
    }
    $startDT = new DateTime();
    $startDT->setTimestamp( $startt );
    $startDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
    $starttime = $startDT->format( $time_format );
    
    echo $starttime . ' - ' .$endtime;
break;
case 'date':
    if ( '0000-00-00 00:00:00' == $post->post_date ) {
            $t_time = $h_time = __( 'Unpublished' );
            $time_diff = 0;
    } else {
            $t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
            $m_time = $post->post_date;
            $time = get_post_time( 'G', true, $post );

            $time_diff = time() - $time;

            if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
                    $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
            else
                    $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
    }

    echo '<td ' . $attributes . '>';
    if ( 'excerpt' == $mode ) {

            /**
             * Filter the published time of the post.
             *
             * If $mode equals 'excerpt', the published time and date are both displayed.
             * If $mode equals 'list' (default), the publish date is displayed, with the
             * time and date together available as an abbreviation definition.
             *
             * @since 2.5.1
             *
             * @param array   $t_time      The published time.
             * @param WP_Post $post        Post object.
             * @param string  $column_name The column name.
             * @param string  $mode        The list display mode ('excerpt' or 'list').
             */
            echo apply_filters( 'post_date_column_time', $t_time, $post, $column_name, $mode );
    } else {

            /** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
            echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, $mode ) . '</abbr>';
    }
    echo '<br />';
    if ( 'publish' == $post->post_status ) {
            _e( 'Published' );
    } elseif ( 'future' == $post->post_status ) {
            if ( $time_diff > 0 )
                    echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
            else
                    _e( 'Scheduled' );
    } else {
            _e( 'Last Modified' );
    }
    echo '</td>';
break;
case "bf_col_ev_thumb":
    // - show thumb -
    $post_image_id = get_post_thumbnail_id(get_the_ID());
    if ($post_image_id) {
    $thumbnail = wp_get_attachment_image_src( $post_image_id, 'post-thumbnail', false);
    if ($thumbnail) (string)$thumbnail = $thumbnail[0];
    echo '<img src="';
    echo bloginfo('template_url');
    echo '/timthumb/timthumb.php?src=';
    echo $thumbnail;
    echo '&h=60&w=60&zc=1" alt="" />';
}
break;
}
}

// 4. Show Meta-Box
 
add_action( 'admin_init', 'bf_events_create' );
 
function bf_events_create() {
    add_meta_box('bf_events_meta', 'Events', 'bf_events_meta', 'bf_events');
}
 
function bf_events_meta () {
 
    // - grab data -

    global $post;
    $custom = get_post_custom($post->ID);
    $meta_sd = $custom["bf_events_startdate"][0];
    if ($custom["bf_events_startdate"][0] == null) $meta_sd = time();

    $startDT = new DateTime();
    $startDT->setTimestamp( $meta_sd );
    $startDT->setTimezone( new DateTimeZone ( get_option( 'timezone_string' ) ) );
    if ( isset ( $custom["bf_events_enddate"] ) ) {
        $meta_ed = $custom["bf_events_enddate"][0];
    } else {
        $meta_ed = $meta_sd;
    }
    $endDT = new DateTime();
    $endDT->setTimestamp( $meta_ed  );
    $endDT->setTimezone( new DateTimeZone ( get_option( 'timezone_string' ) ) );
    $meta_email = $custom["bf_events_email"][0];
    $meta_place = $custom["bf_events_place"][0];
    $meta_url = $custom["bf_events_url"][0];
    $meta_campaign_iCalNative = $custom["iCalNative"][0];
    $meta_campaign_iCalEmbed = $custom["iCalEmbed"][0];
    $meta_campaign_embedRef = get_post_meta('embedRefs', $post->ID, true );
    $meta_pending_description = $custom["bf_pending_description"][0];

    // - grab wp time format -

//    $date_format = get_option('date_format'); // Not required in this code
    $time_format = get_option('time_format');

    // - populate today if empty, 00:00 for time -

  
    // - convert to pretty formats -

    $clean_sd = $startDT->format( "D, d M Y" );
    $clean_st = $startDT->format( $time_format );
    if ( $meta_ed ) {
        $clean_ed = $endDT->format( "D, d M Y" );
        $clean_et = $endDT->format( $time_format );
    } else {
        $clean_ed = "";
        $clean_et = "";
    }

    // - security -

    echo '<input type="hidden" name="bf-events-nonce" id="bf-events-nonce" value="' .
    wp_create_nonce( 'tf-events-nonce' ) . '" />';

    // - output -

    ?>
    <div class="bf-meta">
    <ul>
        <?php if( $meta_pending_description ) {?>
            <li class='tall'><label>Revised description awaiting approval</label>
                <?php wp_editor( $meta_pending_description, "editcontent", array("media_buttons"=>false, "textarea_name"=>"bf_pending_description" ) ); ?></li>
            </li>
        <?php } ?>
        <li><label>Start Date</label><input name="bf_events_startdate" class="bfdate" value="<?php echo $clean_sd; ?>" /></li>
        <li><label>Start Time</label><input name="bf_events_starttime" value="<?php echo $clean_st; ?>" /></li>
        <li><label>End Date</label><input name="bf_events_enddate" class="bfdate" value="<?php echo $clean_ed; ?>" /></li>
        <li><label>End Time</label><input name="bf_events_endtime" value="<?php echo $clean_et; ?>" /></li>
        <li><label>Your Email</label><input type="email" name="bf_events_email" value="<?php echo $meta_email; ?>" /><em>(not for publication)</em></li>
        <li><label>Meeting Place</label><input class="wide" name="bf_events_place" value="<?php echo $meta_place; ?>" /></li>
        <li><label>Web Page</label><input class="wide" type="url" name="bf_events_url" value="<?php echo $meta_url; ?>" /><em>(if any)</em></li>
        <li><label><b>iCal downloads</b></label></li>
        <li><label>from this site</label><?=$meta_campaign_iCalNative?></li>
        <li><label>via embedding</label><?=$meta_campaign_iCalEmbed?></li>
        <li><label>embed refs</label><?=implode(', ', $meta_campaign_embedRef);?></li>
        <li><?=print_r($meta_campaign_embedRef)?></li>
    </ul>
    </div>
    <?php
}

// 5. Save Data
 
add_action ('save_post', 'save_bf_events');
 
function save_bf_events(){
 
    global $post;

    // - still require nonce

    if (  isset( $_POST['bf-events-nonce'] ) && !wp_verify_nonce( $_POST['bf-events-nonce'], 'tf-events-nonce' )) {
        return $post->ID;
    }

    if ( !current_user_can( 'edit_posts' ) )
        return $post->ID;

    // - convert back to unix & update post
    $dateformat = 'Y-m-d H:i:s';
    if(!isset($_POST["bf_events_startdate"])):
        return $post;
    endif;
    $updatestartd = strtotime ( $_POST["bf_events_startdate"] . $_POST["bf_events_starttime"] ); // convert to timestamp, free text format
    $formattedST = date( $dateformat, $updatestartd ); // standardised text format
    $startDT = DateTime::createFromFormat( $dateformat, $formattedST, new DateTimeZone( get_option( 'timezone_string' ) ) );
    update_post_meta($post->ID, "bf_events_startdate", $startDT->getTimestamp() ); // timestamp is UMT
    update_post_meta($post->ID, "bf_events_year", $startDT->format("Y" ) );
    update_post_meta($post->ID, "bf_events_month", $startDT->format("m" ) );

    if( ! isset( $_POST[ "bf_events_enddate" ] ) ) :
        return $post;
    endif;
    
    $updateendd = strtotime ( $_POST["bf_events_enddate"] . $_POST["bf_events_endtime"] );
    $formattedET = date ( $dateformat, $updateendd );
    $endDT = DateTime::createFromFormat($dateformat, $formattedET, new DateTimeZone( get_option( 'timezone_string' ) ) );
    update_post_meta($post->ID, "bf_events_enddate", $endDT->getTimestamp() );
    if ( isset( $_POST[ "bf_pending_description"] ) ) {
        if ( $_POST[ "bf_pending_description" ] == "" ) {
            delete_post_meta($post->ID, 'bf_pending_description' );
        } else {
            update_post_meta( $post->ID, "bf_pending_description", $_POST[ "bf_pending_description"] );
        }
    }
    
    update_post_meta($post->ID, "bf_events_email", $_POST["bf_events_email"] );
    update_post_meta($post->ID, "bf_events_place", $_POST["bf_events_place"] );
    update_post_meta($post->ID, "bf_events_url", $_POST["bf_events_url"] );
    
}

// 6. Customize Update Messages
 
add_filter('post_updated_messages', 'events_updated_messages');
 
function events_updated_messages( $messages ) {
 
  global $post, $post_ID;
 
  $messages['bf_events'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Event updated. <a href="%s">View item</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Event updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Event restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Event published. <a href="%s">View event</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Event saved.'),
    8 => sprintf( __('Event submitted. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Event scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview event</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Event draft updated. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
 
  return $messages;
}

// 7. JS Datepicker UI

function events_styles() {
    global $post_type;
    if( 'bf_events' != $post_type )
        return;
    wp_enqueue_style('ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css');
}
 
function events_scripts() {
    global $post_type;
    if( 'bf_events' !== $post_type )
        return;
    wp_enqueue_script('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js', array( 'jquery') );
//    wp_enqueue_script('ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js', array( 'jquery', 'jquery-ui' ) );
    wp_register_script('admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery', 'jquery-ui' ) );
    wp_localize_script('admin', 'stylesheetUri', plugin_dir_url( __FILE__ ) );
    wp_enqueue_script('admin' );
}
 
add_action( 'admin_print_styles-post.php', 'events_styles', 1000 );
add_action( 'admin_print_styles-post-new.php', 'events_styles', 1000 );
 
add_action( 'admin_print_scripts-post.php', 'events_scripts', 1000 );
add_action( 'admin_print_scripts-post-new.php', 'events_scripts', 1000 );

function scripts() {
    wp_register_script('ui', plugins_url( 'js/ui.js', __FILE__ ), array( 'jquery' ) );
    wp_localize_script('ui', 'data', array('ajaxurl'=>admin_url('admin-ajax.php') ) );
    wp_enqueue_script('ui');
    wp_enqueue_style('event-style', plugins_url( 'style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'scripts' );

function add_admin_styles() {
    wp_enqueue_style( 'admin-style', plugins_url( 'css/admin-style.css', __FILE__ ) );
}
add_action('admin_init', 'add_admin_styles' );

/*
 *  Shortcode to display events
 */

/*
 * EVENTS SHORTCODES (CUSTOM POST TYPE)
 * http://www.noeltock.com/web-design/wordpress/how-to-custom-post-types-for-events-pt-2/
 */

// 1) FULL EVENTS
// NB - this code is not used.  Instead, see the included file list-events.php 
// which presents a paginated list of events in a more compact format - ngd - 1/9/2014
//***********************************************************************************

function bf_events_full ( $atts ) {

// - define arguments -
extract(shortcode_atts(array(
    'limit' => '10', // # of events to show
 ), $atts));

// ===== OUTPUT FUNCTION =====

ob_start();

// ===== LOOP: FULL EVENTS SECTION =====

// - hide events that are older than 6am today (because some parties go past your bedtime) -

// $today6am = strtotime('today 6:00') + ( get_option( 'gmt_offset' ) * 3600 );
$now = time() + ( get_option( 'gmt_offset' ) * 3600 ); // gmt_offset changes with DST, so it's correct for "now" but not correct for not-now times.

// - query -
global $wpdb;
$querystr = "
    SELECT *
    FROM $wpdb->posts wposts, $wpdb->postmeta metastart, $wpdb->postmeta metaend
    WHERE (wposts.ID = metastart.post_id AND wposts.ID = metaend.post_id)
    AND (metaend.meta_key = 'bf_events_enddate' AND metaend.meta_value > $now )
    AND metastart.meta_key = 'bf_events_startdate'
    AND wposts.post_type = 'bf_events'
    AND wposts.post_status = 'publish'
    ORDER BY metastart.meta_value ASC LIMIT $limit
 ";

$events = $wpdb->get_results($querystr, OBJECT);

// - declare fresh day -
$daycheck = null;

// - loop -
if ($events):
global $post;
foreach ($events as $post):
setup_postdata($post);

// - custom variables -
$custom = get_post_custom(get_the_ID());
$sd = $custom["bf_events_startdate"][0];
$ed = $custom["bf_events_enddate"][0];
$startDT = new DateTime();
$startDT->

// - determine if it's a new day -
$longdate = date("l, F j, Y", $sd);
if ($daycheck == null) { echo '<h2 class="full-events">' . $longdate . '</h2>'; }
if ($daycheck != $longdate && $daycheck != null) { echo '<h2 class="full-events">' . $longdate . '</h2>'; }

// - local time format -
$time_format = get_option('time_format');
$stime = date($time_format, $sd);
$etime = date($time_format, $ed);

// - output - ?>
<div class="full-events">
    <div class="text">
        <div class="title">
            <div class="eventtext"><?php the_title(); ?></div>
            <div class="time"><?php echo $stime . ' - ' . $etime; ?></div>
            <div class="time">Meet at: <?php echo $custom["bf_events_place"][0]?></div>
            <?php if($custom["bf_events_url"][0] !== "" ) { ?>
            <div class="time"><a href="<?php echo $custom["bf_events_url"][0]?>" target="_blank">Web page</a></div>
            <?php }
            if( current_user_can( 'edit_posts' ) ) {
                echo "<span class='edit-link'>\n";
                echo edit_post_link();
            } ?>
        </div>
    </div>
     <div class="desc"><?php if (strlen($post->post_content) > 150) { echo substr($post->post_content, 0, 150) . '...'; } else { echo $post->post_content; } ?></div>
</div>
<?php

// - fill daycheck with the current day -
$daycheck = $longdate;

endforeach;
else :
endif;


// ===== RETURN: FULL EVENTS SECTION =====

$output = ob_get_contents();
ob_end_clean();
return $output;
}

add_shortcode('bf-events-full', 'bf_events_full'); // You can now call this shortcode with [bf-events-full limit='20']

/*
 * Add event details before the body of the post
 */

function event_details($content) {
    global $post;
    if($post->post_type !== 'bf_events' ) return $content;
    
    $output = "";
    $sd = get_post_meta( $post->ID, 'bf_events_startdate', true);
    $ed = get_post_meta( $post->ID, 'bf_events_enddate', true);
    $time_format = get_option('time_format');
    
    $startDT = new DateTime();
    $startDT->setTimestamp( $sd );
    $startDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
    $stime = $startDT->format( $time_format );
    
    $endDT = new DateTime();
    $endDT->setTimestamp( $ed );
    $endDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
    $etime = $endDT->format( $time_format );
    
    $startout = $startDT->format( "l, F j, Y" );
    if ( $ed ) $endout = $endDT->format( "l, F j, Y" );
    $output .= "<div>Start: " . $startout . " " . $stime . "</div>";
    if ( $ed ) $output .= "<div>Finish: " . ($endout===$startout ? "" : $endout . " ") . $etime . "</div>";
    $output .= "<div>Meet at: " . get_post_meta( $post->ID, 'bf_events_place', true ) . "</div>";
    $url = get_post_meta( $post->ID, 'bf_events_url', true);
    if( $url ) $output .= "<div>More information: <a href='" . $url . "' target='_blank'>" . $url . "</a></div>";
    $postput = "";
    $postput .= "<div><img class='right-margin' src='" . plugins_url( 'img/cal.jpg' , __FILE__ ) . "' border='0'/><a rel='nofollow' href='" . get_site_url() . "?iCal&p=" . $post->ID . "&campaign=iCalNative'>Add to your calendar</a></div>";
    $postput .= "<div id=\"embedCalCode\">" .
        "Want people to put this event in their calendar? <span id=\"embedClick\">Click here</span> for embed code for your website." . 
        "</div>" .
        "<div id=\"embedCode\" class=\"removed\">" .
        "<input name=\"embedCode\" id=\"embedCodeField\" value=\"<a href='" . get_site_url() . "?iCal&p=" . $post->ID . "&campaign=iCalEmbed'>" .
        "<img border='0' src='" . plugins_url( 'img/cal.jpg', __FILE__ ) . "'/> Add to your calendar: " . $post->post_title . "</a>\" />" .
        "</div>";
    $postput .= "<div class='bf_overline'>";
    $postput .= "If you listed this event, we sent you an email with a link that allows you to update it.<br/>";
    $postput .= "If you want to edit this event but you don't have that email handy, <a href='#' onClick='newSecret(\"" . $post->ID . "\")'>click here</a> for a replacement email.<br/>";
    $postput .= "<div id='returnMessage'></div>";
    $now = time();
    if( $ed < $now ) {
        $output .= "<h2>Warning - you are viewing a past event</h2>";
    } else {
        $output .= "<br/>";
    }
    return $output . $content . $postput;
    }
add_filter( 'the_content', 'event_details' );

/*
 *  one line listing of events for home page
 */
require plugin_dir_path( __FILE__ ) . "list-events.php";
/*
 * RSS feed
 */
remove_all_actions( 'do_feed_rss2' );
add_action( 'do_feed_rss2', 'events_feed_rss2', 10, 1 );
function events_feed_rss2( ) {
    $rss_template = plugin_dir_path( __FILE__ ) . 'feed-rss2.php';
    if( get_query_var( 'post_type' ) == 'bf_events' and file_exists( $rss_template ) )
        load_template( $rss_template );
}

function myfeed_request($qv) {
    if (isset($qv['feed'])) {
        $qv['post_type'] = "bf_events";
    }
    return $qv;
}
add_filter('request', 'myfeed_request');
/**
 * Add function to widgets_init that'll load our widget.
 */
add_action( 'widgets_init', 'load_RSS_widget' );

/**
 * Register widget.
 */
function load_RSS_widget() {
	register_widget( 'Events_RSS' );
}

class Events_RSS extends WP_Widget {
    function __construct() {
		$widget_ops = array('classname' => 'widget_event_feed', 'description' => __( 'RSS feed of events.') );
		parent::__construct('RSS', __('RSS'), $widget_ops);
	}

	public function widget( $args, $instance ) {
		extract($args);
                $title = apply_filters( 'widget_title', $instance['title'] );

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		echo $before_widget;
                    
                ?>
                <a href="<?=bloginfo('rss2_url') . "&post_type=bf_events";?>"><?=$title?></a>
                <?php
                
                echo $after_widget;
        }
        
        public function form ( $instance ) {
            if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'New title', 'text_domain' );
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
                <?php
        }
}

/*
 * AJAX call to add a new event
 */
add_action( 'wp_ajax_newEvent', 'bf_newEvent' );
add_action( 'wp_ajax_nopriv_newEvent', 'bf_newEvent' );

add_action('wp_print_scripts','include_jquery_form_plugin');
function include_jquery_form_plugin(){
    global $post;
    if ($post->post_title === "List Your Event") { // only add this on the page that allows the upload
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-form',array('jquery'),false,true ); 
    }
}

function bf_newEvent() {
    /*
     * Is this an edit of an already-submitted event? 
     * can be done via the postmeta bf_events_secret 
     */
    
    $existing = false;
    if ( isset( $_POST['bf_events_secret']) && $_POST['bf_events_secret']!=="" ) {
        $existing = true;
        $bf_events_secret = $_POST["bf_events_secret"];
        $post_id = $_POST["post_id"];
        global $wpdb;
        $query = $wpdb->prepare( "SELECT * FROM " . $wpdb->posts . " p LEFT JOIN " . $wpdb->postmeta . " m ON p.ID=m.post_id" .
                " WHERE m.meta_key='bf_events_secret' AND m.meta_value='%s' AND p.ID='%s'", $bf_events_secret, $post_id );
        $post = $wpdb->get_row( $query, OBJECT );
        if ( ! $post ) {
            echo json_encode( array( 'error'=>'Sorry, something has gone wrong, it appears you were editing an existing event, but we couldn\'t find that event.' ) );
            die;
        }
    }
    
    $title = $_POST['title'];
    $bf_events_email = $_POST['bf_events_email'];
    $bf_events_place = $_POST['bf_events_place'];
    $bf_events_url = $_POST['bf_events_url'];
    if ( $existing ) {
        $bf_pending_description = $_POST['bf_description'];
    } else {
        $bf_description = $_POST['bf_description'];
    }

    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');
   
    $areYouThere = $_POST['areYouThere'];
    if (
        ! isset( $_POST['bf_nonce'] ) 
        || ! wp_verify_nonce( $_POST['bf_nonce'], 'bf_new_event' ) 
    ) {

       echo json_encode( array( 'error'=>'Sorry, your nonce did not verify.' ) );
       die;
    }
    
    if($bf_events_email==="") {
        echo json_encode( array( 'error'=>'Please supply an email address' ) );
        die;
    }
    global $wpdb;
    
    if( $areYouThere !== "y" && ! $existing ) {
        echo json_encode( array('error'=>'Please tick the box to show you are not a robot') );
        die;
    }
        
    if ( $existing ) {
        $new_post = array (
            'ID' => $post_id,
            'post_title' => $title,
        );
        $post_id = wp_update_post( $new_post, true );
        update_post_meta($post_id, 'bf_pending_description', $bf_pending_description );
    } else {
        $post_id = wp_insert_post(array(
                'post_title'=>$title,
                'post_status'=>'draft',
                'post_type'=>'bf_events',
                'ping_status'=>false,
                'post_content'=>$bf_description,
            ),
            true
        );
    }
    if(is_wp_error($post_id)) {
        echo json_encode( array( 'error'=>$post_id->get_error_message() ) );
        die;
    }
    $updatestartd = strtotime ( $_POST["bf_events_startdate"] . $_POST["bf_events_starttime"] ); // unix time integer;
    $dateformat = "Y-m-d H:i:s";
    $formatDT = date( $dateformat, $updatestartd ); // formatted version;
    $startDT = DateTime::createFromFormat($dateformat, $formatDT, new DateTimeZone( get_option( 'timezone_string' ) ) );
    
    update_post_meta($post_id, "bf_events_startdate", $startDT->getTimestamp() );
    update_post_meta($post_id, "bf_events_year", $startDT->format( "m" ) );
    update_post_meta($post_id, "bf_events_month", $startDT->format( "Y" ) );

    if( isset( $_POST[ "bf_events_enddate" ] ) ) :
        $updateendd = strtotime ( $_POST["bf_events_enddate"] . $_POST["bf_events_endtime"] );
        $formatDT = date( $dateformat, $updateendd ); // formatted version;
        $endDT = DateTime::createFromFormat($dateformat, $formatDT, new DateTimeZone( get_option( 'timezone_string' ) ) );
        update_post_meta($post_id, "bf_events_enddate", $endDT->getTimestamp() ); // new event comes here
    endif;
   
    update_post_meta($post_id, "bf_events_email", $bf_events_email );
    update_post_meta($post_id, "bf_events_place", $bf_events_place );
    update_post_meta($post_id, "bf_events_url", $bf_events_url );
     
    if ($_FILES) {
        foreach ($_FILES as $file => $array) {
            
            if ( !isset($_FILES[$file]['error']) || is_array($_FILES[$file]['error']) ) {
                echo json_encode( array('error'=>'Invalid upload parameters' ) );
                die;
            }
            
            switch ($_FILES[$file]['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    echo json_encode( array('error'=>'No file sent.') );
                    die;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    echo json_encode( array('error'=>'Exceeded filesize limit.') );
                    die;
                default:
                    echo json_encode( array('error'=>'Unknown errors.') );
                    die;
            }

            // You should also check filesize here.
            // from http://php.net/manual/en/features.file-upload.php
            if ($_FILES[$file]['size'] > 2000000) {
                echo json_encode( array('error'=>'Exceeded filesize limit 2MB.') );
                die;
            }
            
            // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
            // Check MIME Type by yourself.
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
                $finfo->file($_FILES[$file]['tmp_name']),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'ical' => 'text/calendar'
                ),
                true
            )) {
                echo json_encode( array('error'=>'incorrect file type') );
                die;
            }
            
            // You should name it uniquely.
            // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
            // On this example, obtain safe unique name from its binary data.
/*            if (!move_uploaded_file(
                $_FILES[$file]['tmp_name'],
                sprintf('./uploads/%s.%s',
                    sha1_file($_FILES[$file]['tmp_name']),
                    $ext
                )
            )) {
                echo json_encode( array('error'=>'failed to move uploaded file. tmp_name = ' . $_FILES[$file]['tmp_name'] .
                    ", ext = " . $ext ) );
                die;
            } */
            
            $attach_id = media_handle_upload( $file, $post_id );
            //  attached image becomes thumbnail:
            update_post_meta($post_id,'_thumbnail_id',$attach_id);
        }   
    }
    
    $secret = generateRandomString();
    update_post_meta ( $post_id, "bf_events_secret", $secret );
    /*
     * mail to submitter
     */
    $subject = $existing ? "Your update is now live" : "Thanks for listing with " . get_option('bf-organisation'); // use blog title in subject!
    $headers = array();
    $headers[] = 'From: "' . get_option('bf-organisation') . '" <' . get_option('newsletter-sender-address') . '>';
    $headers[] = "Content-type: text/html";
    $message = "<P>Thanks for " . ($existing ? "updating your " : "") . "listing with " . get_option('bf-organisation') . ". ";
    if ( $existing ) {
        $message .= "Your event has been updated, except for the description field, which requires moderation.";
    } else {
        $message .= "Your event will be visible once a moderator has approved it.</P>";
    }
    $message .= "<P>Below is a " . ($existing ? "new " : "") . "link you can use to edit the event you have listed.</P>";
    $message .= "<P><a href='" . get_site_url() . "/list-your-event/?secret=" . $secret . "'>Click here to edit your event</a></P>";
    $message .= "<P>Note the secret key in the link above only works once, but we send you a new email like this one each time, with a new secret in it.";
    wp_mail( $bf_events_email, $subject, $message, $headers );
    /*
     * mail to moderator
     */
    $subject = $existing ? "request to update existing event description" : "New listing request";
    $headers = array();
    $headers[] = 'From: "' . get_option('bf-organisation') . '" <' . get_option('newsletter-sender-address') . '>';
    $headers[] = "Content-type: text/html";
    $message = $existing ? 
            "A request has been made to update the description of an event." :
            "A new listing has been submitted for your approval.";
    $message .= "<P><a href='" . get_site_url() . "/wp-admin/post.php?post=" . $post_id . "&action=edit' target='_blank'>Click here to approve it</a>.</P>\n";
    $message .= "<P>Event: " . $title . "</P>\n";
    $message .= "<P>Date: " . $startDT->format( "D, M j, Y" ) . "</P>\n";
    $message .= $bf_description;
    $recipient_option = get_option('event-moderator-email');
    wp_mail ( $recipient_option, $subject, $message, $headers );
    
    echo json_encode( array( 'success'=>'Thanks for ' . ($existing ? "updating your " : "") . "listing with " . get_option('bf-organisation') . ". Look for " . ($existing ? "a new " : "an ") . "email from us with a link that allows you to edit your event." ) );
    die();
}
/*
 * used in secret key for email login link
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
/* 
 * Shortcode for event submission form
 */
function register_event_script() {
    wp_register_script('jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js', array( 'jquery') );
    wp_register_style('ui-datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css');
    wp_register_script('event', plugins_url( 'js/event.js' , __FILE__ ), array('jquery', 'jquery-ui' ) );
    wp_register_style('bf-event_styles', plugins_url( 'css/style.css', __FILE__ ) );	
}
function enqueue_event_register_script() {	// support for signup form, which appears on two pages and in a popup
    global $add_event_register_script;
    if( ! $add_event_register_script ) return;
    wp_enqueue_script('jquery-ui');
    wp_enqueue_style('ui-datepicker');
    wp_localize_script('event', 'data', array('stylesheetUri' => plugin_dir_url( __FILE__ ), 'ajaxUrl'=> admin_url('admin-ajax.php') ) );
    wp_enqueue_script('event');
    wp_enqueue_style('bf-event_styles');
}
add_action('init', 'register_event_script' );
add_action( 'wp_footer', 'enqueue_event_register_script' );
function bf_event_register ( $atts ) { 
    global $add_event_register_script;
    $add_event_register_script = true;
    
    $a = shortcode_atts( array(
        'narrow' => '0',
        'popup' => '0',
    ), $atts );
    $narrow = $a['narrow']==='1';
    $popup = $a['popup']==='1';
    $clean_sd = date("D, j F Y");
    $clean_ed = date("D, j F Y");
    
    if ( isset ( $_GET["secret"] ) ) {
        $bf_events_secret = $_GET["secret"];
        global $wpdb;
        $query = $wpdb->prepare( "SELECT * FROM " . $wpdb->posts . " p LEFT JOIN " . $wpdb->postmeta . " m ON p.ID=m.post_id" .
                " WHERE m.meta_key='bf_events_secret' AND m.meta_value='%s'", $bf_events_secret );
        $post = $wpdb->get_row( $query, OBJECT );
        if ( ! $post ) {
            echo "Sorry, the secret in your link is incorrect or outdated.<br/>" .
                    "Remember, the link we send you in an email only works once.";
            exit;
        }
        $post_id = $post->ID;
        $custom = get_post_custom( $post_id );
        $meta_sd = $custom["bf_events_startdate"][0];
        $meta_ed = $custom["bf_events_enddate"][0];
        $startDT = new DateTime();
        $startDT->setTimestamp( $meta_sd );
        $startDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
        $clean_sd = $startDT->format( "D, j F Y" );

        $endDT = new DateTime();
        $endDT->setTimestamp( $meta_ed );
        $endDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
        $clean_ed = $endDT->format( "D, j F Y" );
        $clean_st = $startDT->format( "g:i a" );
        $clean_et = $startDT->format( "g:i a" );
        $meta_email = $custom["bf_events_email"][0];
        $meta_place = $custom["bf_events_place"][0];
        $meta_url = $custom["bf_events_url"][0];
        $bf_pending_description = $custom["bf_pending_description"][0];
        if ( $bf_pending_description ) {
            $content = $bf_pending_description;
        } else {
            $content = $post->post_content;        
        }
    } else {
        $content = $post->post_content;
    }
    
    
    ob_start() ?>

    <form id="register" name="register" method="post" action="#" enctype="multipart/form-data">
        <div class="bf-meta">
            <ul>            
                <li><label>Event title</label><input class='wide' type="text" name="title" id="name" value='<?=$post->post_title?>'></li>
                <li><label>Start Date</label><input name="bf_events_startdate" class="bfdate" value="<?php echo $clean_sd; ?>" /></li>
                <li><label>Start Time</label><input name="bf_events_starttime" value="<?=$clean_st?>" /></li>
                <li><label>End Date</label><input name="bf_events_enddate" class="bfdate" value="<?php echo $clean_ed; ?>" /></li>
                <li><label>End Time</label><input name="bf_events_endtime" value="<?=$clean_et?>" /></li>
                <li><label>Your Email</label><input type="email" id="email" name="bf_events_email" value="<?php echo $meta_email; ?>" /><em>(not for publication)</em></li>
                <li><label>Meeting Place</label><input class="wide" name="bf_events_place" value="<?php echo $meta_place; ?>" /></li>
                <li><label>Web Page</label><input type="url" name="bf_events_url" value="<?php echo $meta_url; ?>" /><em>(if any)</em></li>
                <li><label>Upload image</label><input type="file" name="thumbnail" id="thumbnail"></li>
                <li><label>Description</label></li>
                <li class="tall"><?php wp_editor( $content, "editcontent", array("media_buttons"=>false, "textarea_name"=>"bf_description" ) ); ?></li>
                <?php if( ! $post ) { ?>
                    <li><input id="simpleTuring" name="areYouThere" type="checkbox" value="y" class="inputc"><span class="medfont">Tick this box to show you are not a robot</span></li>
                <?php } ?>
                <li><button type="button" id="saveButton" value="Send">submit</button></li>
                <li><div id="ajax-loading" class="farleft"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></div></li>
                <li><div id="returnMessage"></div></li>
            </ul> 
            <input name="action" value="newEvent" type="hidden">
            <?php if ( $post ) { ?>
                <input name='post_id' value='<?=$post->ID?>' type="hidden" />
                <input name='bf_events_secret' value='<?=$bf_events_secret?>' type='hidden' />
            <?php } ?>
            <?php wp_nonce_field( "bf_new_event", "bf_nonce");?>
        </div>
    </form>
<?php return ob_get_clean();
}
add_shortcode('event', 'bf_event_register' );

add_action( 'wp_ajax_nopriv_sendSecret', 'bf_sendSecret' );

function bf_sendSecret() {
    $post_id = $_POST['post_id'];
    
    $post = get_post( $post_id, 'OBJECT' );
    if ( ! $post ) {
        echo json_encode( array( 'error'=>'Failed to find the post ' . $post_id . ', no email sent' ) );
        die;
    }
    $bf_events_email = get_post_meta( $post_id, 'bf_events_email', true );
    if ( ! $bf_events_email ) {
        echo json_encode ( array( 'error'=>'Couldn\'t find an email address to send to.' ) );
        die;
    }
    $secret = generateRandomString();
    update_post_meta ( $post_id, "bf_events_secret", $secret );
    
    $subject = "New link to edit your " . get_option('bf-organisation') . " event";
    $headers = array();
    $headers[] = 'From: "' . get_option('bf-organisation') . '" <' . get_option('newsletter-sender-address') . '>';
    $headers[] = "Content-type: text/html";
    $message .= "<P>Here is your new link which you can use to edit the event you have listed.</P>";
    $message .= "<P><a href='" . get_site_url() . "/list-your-event/?secret=" . $secret . "'>Click here to edit your event</a></P>";
    $message .= "<P>Note the secret key in the link above only works once, but we send you a new email like this one each time, with a new secret in it.";
    wp_mail( $bf_events_email, $subject, $message, $headers );
}
/*
 * iCal file
 */
if (isset($_REQUEST['iCal'])) {

    add_action('init', 'bf_iCal');

}

function bf_iCal() {
    $post_id = $_REQUEST['p'];
    $campaign = $_REQUEST['campaign'];
    $post = get_post( $post_id, 'OBJECT' );
    $custom = get_post_custom( $post_id );
    $meta_sd = $custom["bf_events_startdate"][0];
    $startDT = new DateTime();
    $startDT->setTimestamp( $meta_sd );
    $startDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
    $meta_ed = $custom["bf_events_enddate"][0];
    $endDT = new DateTime();
    $endDT->setTimestamp( $meta_ed );
    $endDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
    $meta_email = $custom["bf_events_email"][0];
    $meta_place = $custom["bf_events_place"][0];
    $meta_url = $custom["bf_events_url"][0];
    
    if( $campaign ) {
        $before = $custom[$campaign ][0];
        if( ! $before ) $before = 0;
        $before++;
        update_post_meta( $post_id, $campaign, $before ); 
    }
    if ( $campaign === 'iCalEmbed' ) {
        $before = get_post_meta( $post_id, 'embedRefs' ) ;
        if ( ! $before ) $before = array();
        $before[] = $_SERVER['HTTP_REFERER'];
        update_post_meta( $post_id, 'embedRefs', $before );
                
    }
    
    $tf = 'Ymd\THis';
    
    header('Content-type: text/calendar; method="PUBLISH"; component="VEVENT";');
    header('Content-disposition: attachment; filename=bikefun.ics;');
    echo "BEGIN:VCALENDAR\n";
    echo "VERSION:2.0\n";
	echo "PRODID:-//CBDWeb//Bikefun//EN\n";
	echo "METHOD:PUBLISH\n";
	echo "BEGIN:VTIMEZONE\n";
	echo "TZID:" .   get_option('timezone_string') . "\n";
	echo "BEGIN:STANDARD\n";
	echo "DTSTART:19500402T020000\n";
	echo "TZOFFSETFROM:+1100\n";
	echo "TZOFFSETTO:+1000\n";
	echo "RRULE:FREQ=YEARLY;BYMINUTE=0;BYHOUR=2;BYDAY=1SU;BYMONTH=4\n";
	echo "END:STANDARD\n";
	echo "BEGIN:DAYLIGHT\n";
	echo "DTSTART:19501001T020000\n";
	echo "TZOFFSETFROM:+1000\n";
	echo "TZOFFSETTO:+1100\n";
	echo "RRULE:FREQ=YEARLY;BYMINUTE=0;BYHOUR=2;BYDAY=1SU;BYMONTH=10\n";
	echo "END:DAYLIGHT\n";
	echo "END:VTIMEZONE\n";
	echo "BEGIN:VEVENT\n";
        echo 'DTSTART;TZID="' . get_option('timezone_string') . '":' . $startDT->format( $tf ) . "\n";
        echo 'DTEND;TZID="' . get_option('timezone_string') . '":' . $endDT->format( $tf ) . "\n";
        echo 'DTSTAMP:' . date( $tf, time() + get_option( 'gmt_offset' ) * 3600 ) . "\n";
        echo "CLASS:PUBLIC\n";
        echo "SUMMARY:" . $post->post_title . "\n";
        echo "LOCATION:" . $meta_place . "\n";
        $content = str_replace("\n", "\n  ", wpautop ( $post->post_content ) ); // folding as per https://www.ietf.org/rfc/rfc2445.txt
        echo "X-ALT-DESC;FMTTYPE=text/html:" . $content . "\n  <br/>Check here for the latest updates: " . post_permalink( $post_id );
        echo "\n  Link:" . $meta_url . "\n";
        echo "DESCRIPTION:" . str_replace("\n", "\n  ", $post->post_content ); // note line breaks are literal but other HTML still embedded :(
        echo '\n  Check here for the latest updates: ' . post_permalink( $post_id ) . "\n"; // first \n is passed not interpreted because single quotes
        echo "\n  Link:" . $meta_url . "\n";
        $post_thumbnail_id = get_post_thumbnail_id( $post_id );
        if( $post_thumbnail_id ) {
            $thumbnail = wp_get_attachment_image_src( $post_thumbnail_id, "medium" );
            echo "ATTACH:" . $thumbnail[0] . "\n";
        }
        echo "UID:" . $post_id . "\n";
        echo "END:VEVENT\n";
        echo "END:VCALENDAR\n";
    die;
}
/*
 * Options page for Events plugin
 */
add_action( 'admin_menu', 'events_menu' );

/** Step 1. */
function events_menu() {
        add_submenu_page( 'edit.php?post_type=bf_events', 'Event Options', 'Options', 'manage_options', basename(__FILE__), 'event_options' );
}

/** Step 3. */
function event_options() {
//        bf_newsletter_admin_scripts( "bf_newsletter_options" ); // load the admin CSS
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

            // variables for the field and option names 
            $hidden_field_name = 'bf_submit_hidden';
            $options_array = array ( 
                array('opt_name'=>'event-moderator-email', 'data_field_name'=>'event-moderator-email', 
                    'opt_label'=>'Event moderator (comma separated email addresses)', 'field_type'=>'textarea'),
                array('opt_name'=>'bf-organisation', 'data_field_name'=>'bf_organisation', 
                    'opt_label'=>'Organisation name', 'field_type'=>'text'),
            );

            // See if the user has posted us some information
            // If they did, this hidden field will be set to 'Y'
            if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

                foreach ($options_array as $option_array ) {
                    
                    // Read their posted value
                    $opt_val = stripslashes_deep ( $_POST[ $option_array['data_field_name'] ] );

                    // Save the posted value in the database
                    update_option( $option_array ['opt_name'], $opt_val );
                }

                // Put an settings updated message on the screen

                ?>
                <div class="updated"><p><strong><?php _e('settings saved.' ); ?></strong></p></div>
            <?php }

            // Now display the settings editing screen
            ?>
            <div class="wrap">

            <h2>Event Settings</h2>

            <form name="event_options" id="event_options" method="post" action="">
                <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

                <?php 
                foreach ( $options_array as $option_array ) { 
                    // Read in existing option value from database
                    $opt_val = get_option( $option_array[ 'opt_name' ] );
                    ?>
                    <p><?php _e( $option_array[ 'opt_label' ] );
                        if($option_array[ 'field_type' ] === 'textarea' ) { ?>
                            <textarea name="<?php echo $option_array[ 'data_field_name' ]; ?>"><?php echo $opt_val; ?></textarea>
                        <?php } else { ?>
                            <input type="<?=$option_array[ 'field_type' ]?>" name="<?=$option_array[ 'data_field_name' ]?>" value="<?=$opt_val?>"/>
                        <?php } ?>
                    </p>
                <?php } ?>
                <hr />

                <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
                </p>

            </form>
        </div>
    <?php
}
/*
 * Add link to admin menu bar for Events
 */
add_action( 'admin_bar_menu', 'toolbar_event_link', 999 );
function toolbar_event_link( $wp_admin_bar ) {
    $args = array ( 
        'id'=>'bf-events',
        'title'=>'Events',
        'parent'=>'site-name',
        'href'=>get_site_url() . '/wp-admin/edit.php?post_type=bf_events',
    );

    $wp_admin_bar->add_node( $args );
}


/*
 * Added iCal based on 

Plugin Name: iCal for Events Manager
Description: Creates an iCal feed for Events Manager at http://your-web-address/?ical. 
Version: 1.0.5
Author: benjo4u
Author URI: http://benjaminfleischer.com/code/ical-for-events-manager
*/

function iCalFeed()
{
    global $wpdb;

    if (isset($_GET["debug"]))
    {
        define("DEBUG", true);
    }
$getstring = $_GET['ical'];
if(isset($_GET['forceoffset'])) {
$forceoffset = get_option("gmt_offset");
} else {
$forceoffset = "";
}
 if($getstring == 'ics') {
        if(file_exists('icalendar.ics')) {
            header("Content-Type: text/Calendar");
            header("Content-Disposition: inline; filename=icalendar.ics");
        } else { echo 'no icalendar.ics file found'; }
}
    $now = time() + ( get_option( 'gmt_offset' ) * 3600 );
    $querystr = "SELECT metastart.meta_value as start, metaend.meta_value as end, 
                wposts.*, metaplace.meta_value as place
            FROM $wpdb->posts wposts, $wpdb->postmeta metastart, $wpdb->postmeta metaend, $wpdb->postmeta metaplace
            WHERE (wposts.ID = metastart.post_id AND wposts.ID = metaend.post_id and wposts.ID = metaplace.post_id)
            AND (metaend.meta_key = 'bf_events_enddate' AND metaend.meta_value > $now )
            AND metastart.meta_key = 'bf_events_startdate'
            AND metaplace.meta_key = 'bf_events_place'
            AND wposts.post_type = 'bf_events'
            AND wposts.post_status = 'publish'
            ORDER BY metastart.meta_value ASC
         ";

        $posts = $wpdb->get_results($querystr, OBJECT);

#settings
if(isset($_GET['tzlocation'])) { $tzlocation = $_GET['tzlocation']; }
else { $tzlocation = get_option('timezone_string'); }

if(isset($_GET['tzoffset_standard'])) { $tzoffset_standard = $_GET['tzoffset_standard'];}
else { $tzoffset_standard = "+1000"; }

if(isset($_GET['tzname'])) { $tzname = $_GET['tzname']; }
else { $tzname = "CST"; }

if(isset($_GET['tzname_daylight'])) { $tzname_daylight = $_GET['tzname_daylight']; }
else { $tzname_daylight="CDT"; }

if(isset($_GET['tzoffset_daylight'])) { $tzoffset_daylight = $_GET['tzoffset_daylight']; }
else { $tzoffset_daylight = "+1100"; }


    $events = "";
    $space = "    ";
    foreach ($posts as $post)
    {
        $convertDateStart = $post->start;
        $convertDateEnd = $post->end;
        if ($convertDateEnd < $convertDateStart ) {
            $convertDateEnd = $convertDateStart;
        }
    
$printableline = '\\n';
        $startDT = new DateTime();
        $startDT->setTimestamp( $convertDateStart );
        $startDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
        $endDT = new DateTime();
        $endDT->setTimestamp( $convertDateEnd );
        $endDT->setTimezone( new DateTimeZone ( get_option ( 'timezone_string' ) ) );
        $eventStart = $startDT->format( "Ymd\THis" ) . "Z";
        $eventEnd = $endDT->format( "Ymd\THis" ) . "Z";
        $timestamp = date("Ymd\THis", time()) . "Z";
        $summary = $post->eventTitle;
        $description = $post->eventDescription;
       # $description = str_replace(",", "\,", $description);
       # $description = str_replace("\\", "\\\\", $description);
        $description = str_replace("\n", $printableline, strip_tags($description));
       # $description = str_replace("\r", $space, strip_tags($description));
       # $description = str_replace("\t", $space, strip_tags($description));

        $uid = $post->id . "@" . get_bloginfo('home');
        $events .= "BEGIN:VEVENT\n";
        $events .= "DTSTART:" . $eventStart . "\n";
        $events .= "DTEND:" . $eventEnd . "\n";
        $events .= "DTSTAMP:".$timestamp."\n";
        $events .= "CREATED:".$timestamp."\n";
        $events .= "LAST-MODIFIED:".$timestamp."\n";
        $events .= "UID:" . $uid . "\n";
        $events .= "SUMMARY:" . $post->post_title . "\n";
        $content = "Meeting place: " . $post->place . ".\n" . $post->post_content;
        $events .= "DESCRIPTION:" .  preg_replace("/[\n\t\r]/", $printableline, $content ) . "\n";
        $events .= "END:VEVENT\n";
    }

    $blogName = get_bloginfo('name');
    $blogURL = get_bloginfo('home');

    if (!defined('DEBUG'))
    {
        header('Content-type: text/calendar');
        header('Content-Disposition: attachment; filename="iCal-EC.ics"');
    }

    $content = "BEGIN:VCALENDAR\n";
    $content .= "PRODID:-//" . $blogName . "//NONSGML v1.0//EN\n";
    $content .= "VERSION:2.0\n";
    $content .= "CALSCALE:GREGORIAN\n";
    $content .= "METHOD:PUBLISH\n";
    $content .= "X-WR-CALNAME:" . $blogName . "\n";
    $content .= "X-ORIGINAL-URL:" . $blogURL . "\n";
    $content .= "X-WR-CALDESC:Events for " . $blogName . "\n";
    $content .= "X-WR-TIMEZONE:".$tzlocation."\n";
    $content .=  "BEGIN:VTIMEZONE\n";
	$content .=  "TZID:" .   get_option('timezone_string') . "\n";
	$content .=  "BEGIN:STANDARD\n";
	$content .=  "DTSTART:19500402T020000\n";
	$content .=  "TZOFFSETFROM:+1100\n";
	$content .=  "TZOFFSETTO:+1000\n";
	$content .=  "RRULE:FREQ=YEARLY;BYMINUTE=0;BYHOUR=2;BYDAY=1SU;BYMONTH=4\n";
	$content .=  "END:STANDARD\n";
	$content .=  "BEGIN:DAYLIGHT\n";
	$content .=  "DTSTART:19501001T020000\n";
	$content .=  "TZOFFSETFROM:+1000\n";
	$content .=  "TZOFFSETTO:+1100\n";
	$content .=  "RRULE:FREQ=YEARLY;BYMINUTE=0;BYHOUR=2;BYDAY=1SU;BYMONTH=10\n";
	$content .=  "END:DAYLIGHT\n";
	$content .=  "END:VTIMEZONE\n";

    $content .= $events;
    $content .= "END:VCALENDAR";

if($getstring == 'cron' || $getstring == 'rss') {
$myFile = "icalendar.ics";
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $content);
fclose($fh);
if ($getstring == 'cron') {
echo "icalendar.ics created";
} else {
$rsscron = '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">

<channel>
<title>'.$blogName.' cronless icalendar update</title>
<description>iCalendar for Events Manager Cronless Update</description>
<link>'.$blogURL.'</link>
<lastBuildDate>Mon, 28 Aug 2006 11:12:55 -0400 </lastBuildDate>
<pubDate>Tue, 29 Aug 2006 09:00:00 -0400</pubDate>
<item>
<title>You updated your icalendar feed with rss!</title>
<description>icalendar file updated</description>
<link>'.$blogURL.'/?ical</link>
<guid isPermaLink="false"> 1102345</guid>
<pubDate>Tue, 29 Aug 2006 09:00:00 -0400</pubDate>
</item>

</channel>
</rss>';
echo $rsscron;
}
exit;
} else {
    echo $content;
}
    if 
(defined('DEBUG'))
    {
        #echo "\n" . $queryEvents . "\n";    
        #echo $eventStart . "\n";
    }

    exit;
}

if (isset($_GET['ical']))
{
    add_action('init', 'iCalFeed');
}

?>
