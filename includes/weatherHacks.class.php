<?php

class weatherHacks{

private $cityID = 0;
private $cache_lifetime = 3600;

private $url = 'http://weather.livedoor.com/forecast/webservice/json/v1?city=%s';
private $days = array(
    'today',
    'tomorrow',
    'dayaftertomorrow',
);

function __construct($cityID, $day = null)
{
    if (preg_match("/^[0-9]+$/", $cityID)) {
        $this->cityID = $cityID;
    }
}

public function get_data()
{
    $data = get_transient('weatherhacks-'.$this->cityID);
    if ($data) {
        return $data;
    }

    $res = wp_remote_get(sprintf($this->url, $this->cityID));
    if (200 !== intval($res["response"]["code"])) {
        return false;
    }

    $result = json_decode($res['body']);

    $data = array();
    foreach ($result->forecasts as $d) {
        if (isset($d->temperature->max->celsius)) {
            $max = $d->temperature->max->celsius.' &#8451;';
        } else {
            $max = '-';
        }
        if (isset($d->temperature->min->celsius)) {
            $min = $d->temperature->min->celsius.' &#8451;';
        } else {
            $min = '-';
        }
        $data[] = array(
            'title' => $d->dateLabel,
            'img' => $d->image->url,
            'width' => $d->image->width,
            'height' => $d->image->height,
            'weather' => $d->telop,
            'max' => $max,
            'min' => $min,
        );
    }

    $html  = '<div class="weathers">';
    $i     = 0;
    $title = array(
        '今日',
        '明日',
        'あさって',
    );
    $tpl = $this->get_template();
    $style_width = number_format(1 / count($data) * 100, 1, '.', '');
    foreach ($data as $d) {
        $o = $tpl;
        $o = str_replace("%style_width%", $style_width, $o);
        $o = str_replace("%title%", $title[$i], $o);
        $o = str_replace('%img%', $d['img'], $o);
        $o = str_replace('%width%', $d['width'], $o);
        $o = str_replace('%height%', $d['height'], $o);
        $o = str_replace('%weather%', $d['weather'], $o);
        $o = str_replace('%max%', $d['max'], $o);
        $o = str_replace('%min%', $d['min'], $o);
        $html .= $o;
        $i++;
    }
    $html .= "</div>";

    set_transient('weatherhacks-'.$this->cityID, $html, $this->cache_lifetime);
    return $html;
}

private function get_template()
{
    return '<div class="wtr" style="width:%style_width%%">
    <h4 class="wtr-title">%title%</h4>
    <div class="wtr-image">
        <img src="%img%" width="%width%" height="%height%" title="%weather%">
    </div>
    <div class="wtr-content">%weather%</div>
    <div class="wtr-temp">
        <span class="wtr-max">%max%</span>
        /
        <span class="wtr-min">%min%</span>
    </div>
</div>';
}

private function getCelsius($node){
    return $node->getElementsByTagName('celsius')->item(0)->nodeValue;
}

}

// eof
