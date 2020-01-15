<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.acekyd.com
 * @since             1.0.0
 * @package           Display_Medium_Posts
 *
 * @wordpress-plugin
 * Plugin Name:       Display Medium Posts
 * Plugin URI:        https://github.com/acekyd/display-medium-posts
 * Description:       Display Medium Posts is a wordpress plugin that allows users display posts from medium.com on any part of their website.
 * Version:           4.0
 * Author:            AceKYD
 * Author URI:        http://www.acekyd.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       display-medium-posts
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-display-medium-posts-activator.php
 */
function activate_display_medium_posts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-display-medium-posts-activator.php';
	Display_Medium_Posts_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-display-medium-posts-deactivator.php
 */
function deactivate_display_medium_posts() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-display-medium-posts-deactivator.php';
	Display_Medium_Posts_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_display_medium_posts' );
register_deactivation_hook( __FILE__, 'deactivate_display_medium_posts' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-display-medium-posts.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_display_medium_posts() {

	$plugin = new Display_Medium_Posts();
	$plugin->run();

}
run_display_medium_posts();

    // Example 1 : WP Shortcode to display form on any page or post.
    function posts_display($atts){
    	ob_start();
    	 $a = shortcode_atts(array('handle'=>'-1', 'default_image'=>'//i.imgur.com/p4juyuT.png', 'display' => 3, 'offset' => 0, 'total' => 10, 'list' => false, 'publication' => false, 'title_tag' => 'p', 'tag' => false, 'date_format' => 'M d, Y'), $atts);
        // No ID value
        if(strcmp($a['handle'], '-1') == 0){
                return "";
        }
        $handle=$a['handle'];
        $default_image = $a['default_image'];
        $display = $a['display'];
        $offset = $a['offset'];
        $total = $a['total'];
        $list = $a['list'] =='false' ? false: $a['list'];
		$publication = $a['publication'] =='false' ? false: $a['publication'];
		$title_tag = $a['title_tag'];

		$content = null;

		$medium_url = "https://medium.com/feed/" . $handle;

		try {
			$ch = curl_init();

			if (false === $ch)
				throw new Exception('failed to initialize');

			curl_setopt($ch, CURLOPT_URL, $medium_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect: 100-continue'));

			$content = curl_exec($ch);

			if (false === $content)
				throw new Exception(curl_error($ch), curl_errno($ch));

		// ...process $content now
		} catch (Exception $e) {
			trigger_error(
				sprintf(
					'Curl failed with error #%d: %s',
					$e->getCode(),
					$e->getMessage()
				),
				E_USER_ERROR
			);
		}

        $xml = simplexml_load_string($content);
        $json = json_encode($xml);
        $items = array();
        $count = 0;

        foreach ($xml->channel->item as $item) {
            $items[$count]['title'] = $item->title;
            $items[$count]['url'] = $item->link;
            $items[$count]['date'] = $item->pubDate;

            $article = $item->children('content', true)->encoded;
            $doc = new DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors( true );
            $doc->loadHTML('<meta charset="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'.$article);
            $root = $doc->documentElement;
            libxml_clear_errors();
            $figure = $root->getElementsByTagName('figure')->item(0);
            $items[$count]['image'] = $figure->getElementsByTagName('img')->item(0)->getAttribute("src");
            $figure->parentNode->removeChild($figure);

            $items[$count]['subtitle'] = mb_substr(strip_tags($doc->saveHTML($doc->documentElement)), 0, 300).'...';

            $count++;
        }
        if($offset)
        {
            $items = array_slice($items, $offset);
        }

        if(count($items) > $total)
        {
            $items = array_slice($items, 0, $total);
        }
    	?>
        <ul class="sc_article  grid" style="height: auto">
            <?php foreach($items as $item) { ?>
                <a href="<?php echo $item['url']; ?>" target="_blank" class="clearfix">
                    <li>
                        <figure class="post_list_thumb">
                            <?php
                            if($list)
                            {
                                echo '<img src="'.$item['image'].'" class="display-medium-img">';
                            }
                            else
                            {
                                echo '<div data-src="'.$item['image'].'" class="lazyOwl medium-image"></div>';
                            }
                            ?>
                        </figure>
                        <div class="meta">
                            <span style="background:#3842BC; line-height: 3em" class="sc_article_cat">Blockchain Engineer Blog</span>
                            <div class="sc_article_title">
                                <<?php echo $title_tag; ?> class="display-medium-title details-title"><?php echo $item['title']; ?></<?php echo $title_tag; ?>>
                            </div>
                            <div style="font-size: 0.8em">
                                <?php echo $item['subtitle']; ?>
                            </div>
                            <div class="sc_article_date">
                                <?php echo "<span class='display-medium-date'>".$item['date']."</span>"; ?>
                            </div>
                        </div>
                    </li>
                </a>

            <?php } ?>
        </ul>
		<?php
	  		if(empty($items)) echo "<div class='display-medium-no-post'>No posts found!</div>";
	  	?>
		<script type="text/javascript">
				function initializeOwl(count) {
					 jQuery(".display-medium-owl-carousel").owlCarousel({
					 	items: count,
					    lazyLoad : true,
					  });
				}
		</script>
		<?php
			if(!$list)
			{
				echo '<script>initializeOwl('.$display.');</script>';
			}
		?>
        <?php
        return ob_get_clean();
    }
    add_shortcode('display_medium_posts', 'posts_display');
