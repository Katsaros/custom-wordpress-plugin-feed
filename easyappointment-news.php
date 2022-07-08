<?php
/*
Plugin Name: Easyappointment Widget - Changelog
Plugin URI: https://mrwebsite.gr
Description: Essential Widget for EasyAppointment
Version: 1.0
Author: Giannis Katsaros
Author URI: https://www.katsaros.me
Text Domain: easyappointment-news
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'wp_dashboard_setup', 'dw_dashboard_add_widgets' );
function dw_dashboard_add_widgets() {
	wp_add_dashboard_widget( 'dw_dashboard_widget_news', __( 'Αναβαθμίσεις EasyAppointment', 'dw' ), 'dw_dashboard_widget_news_handler', 'dw_dashboard_widget_news_config_handler' );
}

function dw_dashboard_widget_news_handler() {
	$options = wp_parse_args( get_option( 'dw_dashboard_widget_news' ), dw_dashboard_widget_news_config_defaults() );
    //https://mrwebsite.gr/category/easyappointment/feed/
	$feeds = array(
		array(
			'url'          => 'http://mrwebsite.gr/category/easyappointment/feed',
			'items'        => $options['items'],
			'show_summary' => 1,
			'show_author'  => 0,
			'show_date'    => 0,
		),
	);

	wp_dashboard_primary_output( 'dw_dashboard_widget_news', $feeds );
}

function dw_dashboard_widget_news_config_defaults() {
	return array(
		'items' => 5,
	);
}

function dw_dashboard_widget_news_config_handler() {
	$options = wp_parse_args( get_option( 'dw_dashboard_widget_news' ), dw_dashboard_widget_news_config_defaults() );

	if ( isset( $_POST['submit'] ) ) {
		if ( isset( $_POST['rss_items'] ) && intval( $_POST['rss_items'] ) > 0 ) {
			$options['items'] = intval( $_POST['rss_items'] );
		}

		update_option( 'dw_dashboard_widget_news', $options );
	}

    ?>
	<p>
		<label><?php _e( 'Number of RSS articles:', 'dw' ); ?>
			<input type="text" name="rss_items" value="<?php echo esc_attr( $options['items'] ); ?>" />
		</label>
	</p>
	<?php
}

add_action( 'admin_enqueue_scripts', 'dw_scripts' );
function dw_scripts( $hook ) {
	$screen = get_current_screen();
	if ( 'dashboard' === $screen->id ) {
		wp_enqueue_script( 'dw_script', plugin_dir_url( __FILE__ ) . 'path/to/script.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'dw_style', plugin_dir_url( __FILE__ ) . 'style.css', array(), '1.0' );
	}
}

//custom

function wp_dashboard_primary_output2( $widget_id, $feeds ) {
	foreach ( $feeds as $type => $args ) {
		$args['type'] = $type;
		echo '<div class="rss-widget">';
			wp_widget_rss_output2( $args['url'], $args );
		echo '</div>';
	}
}

function wp_widget_rss_output2( $rss, $args = array() ) {
    if ( is_string( $rss ) ) {
        $rss = fetch_feed( $rss );
    } elseif ( is_array( $rss ) && isset( $rss['url'] ) ) {
        $args = $rss;
        $rss  = fetch_feed( $rss['url'] );
    } elseif ( ! is_object( $rss ) ) {
        return;
    }
 
    if ( is_wp_error( $rss ) ) {
        if ( is_admin() || current_user_can( 'manage_options' ) ) {
            echo '<p><strong>' . __( 'RSS Error:' ) . '</strong> ' . $rss->get_error_message() . '</p>';
        }
        return;
    }
 
    $default_args = array(
        'show_author'  => 0,
        'show_date'    => 0,
        'show_summary' => 0,
        'items'        => 0,
    );
    $args         = wp_parse_args( $args, $default_args );
 
    $items = (int) $args['items'];
    if ( $items < 1 || 20 < $items ) {
        $items = 10;
    }
    $show_summary = (int) $args['show_summary'];
    $show_author  = (int) $args['show_author'];
    $show_date    = (int) $args['show_date'];
 
    if ( ! $rss->get_item_quantity() ) {
        echo '<ul><li>' . __( 'An error has occurred, which probably means the feed is down. Try again later.' ) . '</li></ul>';
        $rss->__destruct();
        unset( $rss );
        return;
    }
 
    echo '<ul>';
    foreach ( $rss->get_items( 0, $items ) as $item ) {
        $link = $item->get_link();
        while ( ! empty( $link ) && stristr( $link, 'http' ) !== $link ) {
            $link = substr( $link, 1 );
        }
        $link = "#";//esc_url( strip_tags( $link ) );
 
        $title = esc_html( trim( strip_tags( $item->get_title() ) ) );
        if ( empty( $title ) ) {
            $title = __( 'Untitled' );
        }
 
        $desc = html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) );
        $desc = esc_attr( wp_trim_words( $desc, 55, ' [&hellip;]' ) );

        $summary = '';
        if ( $show_summary ) {
            $summary = $desc;
 
            // Change existing [...] to [&hellip;].
            if ( '[...]' === substr( $summary, -5 ) ) {
                $summary = substr( $summary, 0, -5 ) . '[&hellip;]';
            }
            $summary = substr($summary, 0, -10); //remove [&hellip;] from the end of the string
            $summary = '<div class="rssSummary">' . esc_html( $summary ) . '</div>';
        }
 
        $date = '';
        if ( $show_date ) {
            $date = $item->get_date( 'U' );
 
            if ( $date ) {
                $date = ' <span class="rss-date">' . date_i18n( get_option( 'date_format' ), $date ) . '</span>';
            }
        }
 
        $author = '';
        if ( $show_author ) {
            $author = $item->get_author();
            if ( is_object( $author ) ) {
                $author = $author->get_name();
                $author = ' <cite>' . esc_html( strip_tags( $author ) ) . '</cite>';
            }
        }
 
        if ( '' === $link ) {
            echo "<li>$title{$date}{$summary}{$author}</li>";
        } elseif ( $show_summary ) {
            echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$summary}{$author}</li>";
        } else {
            echo "<li><a class='rsswidget' href='$link'>$title</a>{$date}{$author}</li>";
        }
    }
    echo '</ul>';
    $rss->__destruct();
    unset( $rss );
}
