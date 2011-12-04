<?php

class weatherHacks{

private $cityID = 0;
private $cache_lifetime = 3600;

private $url = 'http://weather.livedoor.com/forecast/webservice/rest/v1?city=%d&day=%s';
private $days = array(
    'today',
    'tomorrow',
    'dayaftertomorrow',
);

function __construct($cityID, $day = null)
{
    if (preg_match("/^[0-9]+$/", $cityID)) {
        $this->cityID = $cityID;
    } else {
        throw new Exception('一次細区分に数値を指定してください。', 100);
    }
    if ($day && is_array($day)) {
        $this->days = $day;
    } elseif ($day) {
        $this->days = array($day);
    }
}

public function get_data()
{
    $data = get_transient('weatherhacks-'.$this->cityID);
    if ($data) {
        return $data;
    }

    $data = array();
    foreach ($this->days as $d) {
        $xml = sprintf($this->url, $this->cityID, $d);
        $dom = new DOMDocument();
        $dom->load($xml);

        $image = $dom->getElementsByTagName('image')->item(0);
        $temp = $dom->getElementsByTagName('temperature')->item(0);

        $title = $dom->getElementsByTagName('title')->item(0)->nodeValue;
        $desc = $dom->getElementsByTagName('description')->item(0)->nodeValue;
        $date = $dom->getElementsByTagName('forecastdate')->item(0)->nodeValue;
        $pdate = $dom->getElementsByTagName('publictime')->item(0)->nodeValue;
        $weather = $image->getElementsByTagName('title')->item(0)->nodeValue;
        $link = $image->getElementsByTagName('link')->item(0)->nodeValue;
        $img = $image->getElementsByTagName('url')->item(0)->nodeValue;
        $width = $image->getElementsByTagName('width')->item(0)->nodeValue;
        $height = $image->getElementsByTagName('height')->item(0)->nodeValue;
        $max = $this->getCelsius($temp->getElementsByTagName('max')->item(0));
        $min = $this->getCelsius($temp->getElementsByTagName('min')->item(0));

        $pp = $dom->getElementsByTagName('pinpoint')->item(0);
        $loc = $pp->getElementsByTagName('location');
        $pinpoints = array();
        foreach ($loc as $lo) {
            $ttl = $lo->getElementsByTagName('title')->item(0)->nodeValue;
            $link = $lo->getElementsByTagName('link')->item(0)->nodeValue;
            $pdate = $lo->getElementsByTagName('publictime')->item(0)->nodeValue;
            $pinpoints[] = array(
                'title' => $ttl,
                'link' => $link,
                'pubdate' => $pdate,
            );
        }

        $data[$d] = array(
            'title' => $title,
            'desc' => $desc,
            'date' => $date,
            'pubdate' => $pdate,
            'weather' => $weather,
            'link' => $link,
            'img' => $img,
            'width' => $width,
            'height' => $height,
            'max' => $max,
            'min' => $min,
            'pinpoint' => $pinpoints,
        );
    }

    $html  = '';
    $i     = 0;
    $title = array(
        '今日',
        '明日',
        'あさって',
    );
    $tpl = $this->get_template();
    foreach ($data as $d) {
        $o = $tpl;
        $o = str_replace("%title%", $title[$i], $o);
        $o = str_replace('%img%', $d['img'], $o);
        $o = str_replace('%width%', $d['width'], $o);
        $o = str_replace('%height%', $d['height'], $o);
        $o = str_replace('%weather%', $d['weather'], $o);
        if ($d['max']) {
            $o = str_replace('%max%', $d['max'], $o);
        } else {
            $o = str_replace('%max%', '-', $o);
        }
        if ($d['min']) {
            $o = str_replace('%min%', $d['min'], $o);
        } else {
            $o = str_replace('%min%', '-', $o);
        }
        $html .= $o;
        $i++;
    }
    set_transient('weatherhacks-'.$this->cityID, $html, $this->cache_lifetime);
    return $html;
}

private function get_template()
{
    return '<div class="wtr">
    <div class="wtr-title">%title%</div>
    <div class="wtr-image">
        <img src="%img%" width="%width%" height="%height%" title="%weather%">
    </div>
    <div class="wtr-content">%weather%</div>
    <div class="wtr-temp">
        <span class="wtr-max">%max%&#8451;</span>
        / 
        <span class="wtr-min">%min%&#8451;</span>
    </div>
</div>';
}

private function getCelsius($node){
    return $node->getElementsByTagName('celsius')->item(0)->nodeValue;
}

}

// eof
