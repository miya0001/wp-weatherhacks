<?php
/*
Plugin Name: Weather Hacks
Plugin URI: http://firegoby.jp/wp/weatherhacks
Description: ライブドアのWeatherHacksのサイドバーウィジェット
Author: Takayuki Miyauchi
Version: 1.0.2
Author URI: http://firegoby.jp/
*/

require_once(dirname(__FILE__).'/includes/weatherHacks.class.php');

class WeatherHacksWidget extends WP_Widget {

private $forecastmap = 'http://weather.livedoor.com/forecast/rss/primary_area.xml';

function __construct() {
    parent::__construct(false, $name = '天気予報');
}

public function form($instance) {
    // outputs the options form on admin
    if (isset($instance['city']) && intval($instance['city'])) {
        $cityID = intval($instance['city']);
    } else {
        $cityID = 0;
    }
    if (!isset($instance['title']) || !$instance['city']) {
        $instance['title'] = '';
    }
    $pfield = $this->get_field_id('city');
    $pfname = $this->get_field_name('city');
    echo 'タイトル:';
    echo '<p>';
    echo sprintf(
        '<input class="widefat" type="text" id="%s" name="%s" value="%s">',
        $this->get_field_id('title'),
        $this->get_field_name('title'),
        esc_attr($instance['title'])
    );
    echo '</p>';
    echo "どこの都市の天気予報を表示しますか？";
    echo '<p>';
    echo "<select class=\"widefat\" id=\"{$pfield}\" name=\"{$pfname}\">";
    echo "<option value=\"\">選択してください。</option>";
    $dom = new DOMDocument();
    if (@$dom->load($this->forecastmap)) {
        $cities = $dom->getElementsByTagName('city');
        foreach ($cities as $city) {
            $id    = $city->getAttribute('id');
            $title = $city->getAttribute('title');
            if ($cityID == $id) {
                echo "<option value=\"{$id}\" selected=\"selected\">{$title}</option>";
            } else {
                echo "<option value=\"{$id}\">{$title}</option>";
            }
        }
    }
    echo "</select>";
    echo '</p>';
}

public function update($new_instance, $old_instance) {
    // processes widget options to be saved
    return $new_instance;
}

public function widget($args, $instance) {
    extract($args);
    $id = esc_attr($instance['city']);
    echo $before_widget;
    echo $before_title . $instance['title'] . $after_title;
    echo '<div class="weather-block" id="weatherhacks-'.$id.'">';
    echo "</div>";
    echo $after_widget;
    add_action('wp_print_footer_scripts', array(&$this, 'wp_print_footer_scripts'));
}

public function wp_print_footer_scripts()
{
?>
<script type="text/javascript">
/* <![CDATA[ */
<?php
    $url = admin_url('admin-ajax.php');
    $url = add_query_arg("action", "weatherhacks", $url);
    $url = add_query_arg("nonce", wp_create_nonce("weatherhacks"), $url);
?>
var url = '<?php echo $url; ?>';
jQuery(".weather-block").each(function(){
    var obj = jQuery(this);
    var id = jQuery(this).attr("id").substr("weatherhacks-".length);
    var req = url + '&city=' + id;
    jQuery.get(req, function(data){
        obj.html(data);
    });
});
/* ]]> */
</script>
<?php
}

}



new wetherHacks();

class wetherHacks {

function __construct()
{
    add_action('admin_enqueue_scripts', array($this, "admin_enqueue_scripts"));
    add_action('widgets_init', array($this, "widgets_init"));
    add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
    add_action('wp_ajax_weatherhacks', array($this, 'wp_ajax'));
    add_action('wp_ajax_nopriv_weatherhacks', array($this, 'wp_ajax'));
}

public function admin_enqueue_scripts()
{
    wp_enqueue_script('jquery');
}

public function widgets_init()
{
    return register_widget("WeatherHacksWidget");
}

public function wp_enqueue_scripts()
{
    $url = plugins_url('style.css', __FILE__);
    wp_enqueue_style(
        'weatherhacks',
        $url,
        array(),
        filemtime(dirname(__FILE__).'/style.css'),
        'all'
    );
}

public function wp_ajax()
{
    nocache_headers();
    if (wp_verify_nonce($_GET['nonce'], 'weatherhacks')) {
        if (isset($_GET['city']) && preg_match("/^[0-9]+$/", $_GET['city'])) {
            $wh = new weatherHacks($_GET['city']);
            echo $wh->get_data();
        }
    }
    exit;
}

}

// eof
