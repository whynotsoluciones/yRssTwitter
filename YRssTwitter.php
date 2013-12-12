<?php

/**
 * yRssTwitter widget
 *
 * Parses data from the Rss feed of a twitter timeline a shows it nicely
 *
 * @filesource
 * @copyright    Copyright 2012 Why Not Soluciones, S.L. - All Rights Reserved
 * @package      yRssTwitter
 * @license      http://opensource.org/licenses/BSD-3-Clause The BSD 3-Clause License
 */
class YRssTwitter extends CWidget {

    /**
     * @var Parameters for yRssTwitter
     */
    public $feed;
    public $display = true;
    public $displayName = 'Why Not Soluciones';
    public $twitterUser = 'ynotsoluciones';
    public $locales = array("es_ES.UTF-8", "es_ES@euro", "es_ES", "esp");
    public $timeNames = array('segundo', 'minuto', 'hora', 'día', 'semana', 'mes', 'año', 'decada');
    public $tweetsToDisplay = 5;
    public $twitterLogo = 'dark';
    public $minscript = true;
    public $color = '#361d27';
    public $linkColor = '#361d27';
    public $linkHoverColor = '#FF6319';
    public $twitterActions = array('reply' => 'responder', 'favorite' => 'favorito');
    public $cachetime = 2; // Tiempo en horas

    /**
     * @var string Path of CSS file and image file to use
     */
    public $cssFile;
    public $imageFile;

    function timeFromPublish($tm, $rcs = 0) {
        $cur_tm = time();
        $dif = $cur_tm - $tm;
        $lngh = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);

        for ($v = sizeof($lngh) - 1; ($v >= 0) && (($no = $dif / $lngh[$v]) <= 1); $v--)
            ;

        if ($v < 0) {
            $v = 0;
            $_tm = $cur_tm - ($dif % $lngh[$v]);
        }

        $no = floor($no);
        $useName = $this->timeNames[$v];
        if ($no != 1) {
            if ($useName == "mes") {
                $useName .= 'es';
            } else {
                $useName .= 's';
            }
        }

        $x = sprintf("%d %s ", $no, $useName);
        if (($rcs == 1) && ($v >= 1) && (($cur_tm - $_tm) > 0)) {
            $x .= time_ago($_tm);
        }

        return $x;
    }

    private function parse_twitter($t, $username) {
        // link URLs
        $t = " " . preg_replace("/(([[:alnum:]]+:\/\/)|www\.)([^[:space:]]*)" .
                        "([[:alnum:]#?\/&=])/i", "<a href=\"\\1\\3\\4\" target=\"_blank\">" .
                        "\\1\\3\\4</a>", $t);

        // link mailtos
        $t = preg_replace("/(([a-z0-9_]|\\-|\\.)+@([^[:space:]]*)" .
                "([[:alnum:]-]))/i", "<a href=\"mailto:\\1\">\\1</a>", $t);

        //link twitter users
        $t = preg_replace("/ +@([a-z0-9_]*) ?/i", " <a href=\"http://twitter.com/\\1\" target=\"_blank\">@\\1</a> ", $t);

        //link twitter arguments
        $t = preg_replace("/ +#([a-z0-9_]*) ?/i", " <a href=\"http://twitter.com/search?q=%23\\1\" target=\"_blank\">#\\1</a> ", $t);

        // truncates long urls that can cause display problems (optional)
        $t = preg_replace("/>(([[:alnum:]]+:\/\/)|www\.)([^[:space:]]" .
                "{30,40})([^[:space:]]*)([^[:space:]]{10,20})([[:alnum:]#?\/&=])" .
                "</", ">\\3...\\5\\6<", $t);

        $t = str_replace($username . ": ", '<a href="http://twitter.com/#!/' . $username . '">' . $username . '</a>:', $t);

        return trim($t);
    }

    function buildBaseString($baseURI, $method, $params) {
        $r = array();
        ksort($params);
        foreach ($params as $key => $value) {
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }

    function buildAuthorizationHeader($oauth) {
        $r = 'Authorization: OAuth ';
        $values = array();
        foreach ($oauth as $key => $value)
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        $r .= implode(', ', $values);
        return $r;
    }

    function getTwitterJSON_V1_1() {
        
        $url = "https://api.twitter.com/1.1/statuses/user_timeline.json";

        //TODO: add config params for this values
        $oauth_access_token = "243581526-PYL8xcVQO5yvH5JhkQEq6HWGoK91LGIvrZhIst92";
        $oauth_access_token_secret = "jTccUfKNjD2cN9LoKJXfPXmPOPYOMRNvMrnVqmJvXU";
        $consumer_key = "7OhsnWsThIvoGg5jRbHqA";
        $consumer_secret = "J1AeHQlwwTQJq8wNeJ3X4LDtvxnCJT8kBSD4q0bGPek";

        $oauth = array('oauth_consumer_key' => $consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $oauth_access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0');

        $base_info = $this->buildBaseString($url, 'GET', $oauth);
        $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;

        // Make Requests
        $header = array($this->buildAuthorizationHeader($oauth), 'Expect:');
        $options = array(CURLOPT_HTTPHEADER => $header,
            //CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HEADER => false,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false);

        $feed = curl_init();
        curl_setopt_array($feed, $options);
        $json = curl_exec($feed);
        curl_close($feed);
        
        return $twitter_data = json_decode($json);
    }

    private function getTwitterRss($username, $locales) {

        $cachedata = $this->getCache();
        if($cachedata == false){ // Hora de renovar o archivo de cache no encontrado
            $source = $this->getTwitterJSON_V1_1();
            $this->saveCache(serialize($source));
        } else { // Archivo de cache reciente. Obtener los datos de allí
            $source = unserialize($cachedata);
        }

        foreach ($source as $key=>$tweet) {
            
            if ($key == $this->tweetsToDisplay) {
                break;
            }
            $description = $this->parse_twitter($tweet->text, $username);
            
            $dateFormat = "%s";
            $guid = $tweet->id_str;
            $pubDate = @strtotime($tweet->created_at);

            setlocale(LC_TIME, $locales);
            $returnEntry[$key]['date'] = $this->timeFromPublish(@strftime($dateFormat, $pubDate));
            $returnEntry[$key]['guid'] = $guid;
            $returnEntry[$key]['replyUrl'] = 'https://twitter.com/intent/tweet?in_reply_to=' . basename($guid);
            $returnEntry[$key]['retweetUrl'] = 'https://twitter.com/intent/retweet?tweet_id=' . basename($guid);
            $returnEntry[$key]['favoriteUrl'] = 'https://twitter.com/intent/favorite?tweet_id=' . basename($guid);
            $returnEntry[$key]['description'] = $description;
        }
       

        return $returnEntry;
    }
    
    
    
    private function saveCache($data){
        $cache_file = Yii::app()->basePath . '/components/widgets/yRssTwitter/assets/tweets.cache'; // Recordar dar permisos a este archivo
        // Escribe el contenido al fichero
        file_put_contents($cache_file, $data); // Si no existe el archivo lo crea
    }
    
    private function getCache(){ 
        $cache_file = Yii::app()->basePath . '/components/widgets/yRssTwitter/assets/tweets.cache'; // Recordar dar permisos a este archivo
        if (file_exists($cache_file)) {
            // Escribe el contenido al fichero
            //file_put_contents($cache_file, 'prueba');
            
            $lastsaved = filemtime($cache_file); // Obtengo la fecha de la última modificación del archivo de cache
            $timenow = date("Y-m-d H:i:s"); // Obtengo la fecha actual
            $hourdiff = round((strtotime($timenow) - $lastsaved)/3600, 1); // Calculo el tiempo en horas desde la última vez que se modificó el archivo de cache
            Yii::log('', CLogger::LEVEL_INFO, $hourdiff);
            if($hourdiff > $this->cachetime){
                return false; // Obtener los tweets desde la API de Twitter
            } else {
                $data = file_get_contents($cache_file); // Obtengo los tweets del archivo de cache en vez de llamar a la api de Twitter
                return $data;
            }
        } else {
            Yii::log('', CLogger::LEVEL_ERROR, "Archivo tweets.cache no encontrado. Creando archivo y obteniendo tweets directamente...");
            //saveCache(''); // Creo el archivo de cache vacío
            return false; // Obtener los tweets desde la API de Twitter
        }
    }

    /**
     * Initialises the widget
     */
    public function init() {

        $this->feed = @$this->getTwitterRss($this->twitterUser, $this->locales);

        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'yRssTwitter.css';
        $imageFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'twitter-' . $this->twitterLogo . '.png';

        // If we are using minscript, we want to have always the same hash, 
        // so we use a true with publish
        $this->cssFile = Yii::app()->getAssetManager()->publish($file, $this->minscript);
        $this->imageFile = Yii::app()->getAssetManager()->publish($imageFile, $this->minscript);


        Yii::app()->getClientScript()->registerCssFile($this->cssFile);
    }

    /**
     * Display the Twitter timeline
     *
     * The cached forecast is used if enabled and not expired
     */
    public function run() {


        Yii::app()->getClientScript()->registerCss('yRssTwitterCss', '
            #yRssTwitter .yRssTwitter-header {
                border: 1px solid ' . $this->color . ';
            }
            
            #yRssTwitter a {
                font-weight: bold;
                color: ' . $this->linkColor . '; 
            }

            #yRssTwitter a:hover {
                color: ' . $this->linkHoverColor . ';
                cursor: pointer;
            }

            #yRssTwitter .yRssTwitter-header-left {
                background: ' . $this->color . ';
            }
            
            #yRssTwitter .yRssTwitter-footer {
                background-color: ' . $this->color . ';
            }

        ', 'screen', CClientScript::POS_HEAD);

        if ($this->display == true) {
            $this->render('yRssTwitter', array(
                'displayName' => $this->displayName,
                'twitterUser' => $this->twitterUser,
                'feed' => $this->feed,
                'logoStyle' => 'background-image: url(' . $this->imageFile . ')',
                'twitterActions' => $this->twitterActions,
            ));
        }
    }

}

?>
