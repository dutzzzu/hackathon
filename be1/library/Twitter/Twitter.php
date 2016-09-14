<?php

class Twitter_Twitter {

    private $params=array();


    private function getToken(){

        $twiiterConfig = Zend_Registry::get("config");

        //Twitter auth
        $authToken = base64_encode($twiiterConfig['twiiter']['consumer_key'].":".$twiiterConfig['twiiter']['consumer_secret']);

        //get the token first with curl;

        $service_url = 'https://api.twitter.com/oauth2/token';
        $curl = curl_init($service_url);
        $headr = array();
        $headr[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
        $headr[] = 'Authorization: Basic '.$authToken;

        curl_setopt($curl, CURLOPT_HTTPHEADER,$headr);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //IMP if the url has https and you don't want to verify source certificate

        $curl_response = curl_exec($curl);
        $response = json_decode($curl_response);
        curl_close($curl);

        return $response->access_token;
    }
    public function setParam($key,$value){
        $this->params[$key]=$value;
        return true;
    }
    public function getParam($key){
        return isset($this->params[$key]) ? $this->params[$key] : false;
    }

    public function getTweets($noCache = false) {

        if(!isset($this->params['q'])) return array("error"=>"Query parameter required, please check twitter API documentation!");
        if(!isset($this->params['screen_name'])) return array("error"=>"screen_name parameter required, please check twitter API documentation!");

        $cache = Zend_Registry::get('cache');
        $cacheKey = md5("last100tweets_".$this->params['screen_name']);
        $filepath=APPLICATION_PATH."/../data/twitter_tweets_".$this->params['screen_name'].".json";

        if (($body = $cache->load($cacheKey)) === false || $noCache) {

// get the tweets by curl

            $service_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json?'.http_build_query($this->params);
            $curl = curl_init($service_url);
            $headr = array();
            $headr[] = 'Authorization: Bearer '.$this->getToken();

            curl_setopt($curl, CURLOPT_HTTPHEADER,$headr);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //IMP if the url has https and you don't want to verify source certificate

            $curl_response = curl_exec($curl);

            $response = json_decode($curl_response);

            //var_dump($response);die();

            curl_close($curl);


            $renewyear_tweets=$this->parsePosts($response);

            if (file_exists($filepath)) {
                $currentPostsJSON = file_get_contents($filepath);
            }

            $current_tweets_json= !empty($currentPostsJSON) ? $currentPostsJSON : json_encode(array());


            $currentPosts = json_decode($current_tweets_json, true);

            $newPosts=$renewyear_tweets;

            // merge new posts with current posts
            // first remove duplicate posts
            if (!empty($currentPosts)) {
                foreach ($renewyear_tweets AS $key => $newPost) {
                    if (self::searchForId($newPost['id'], $currentPosts)) {
                        unset($renewyear_tweets[$key]);
                    }
                }

                $newPosts = array_merge($renewyear_tweets, $currentPosts);
            }

            // sort by date desc
            if($newPosts)
            usort($newPosts, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            // truncate posts
            if (count($newPosts) > 30) {
                $newPosts = array_slice($newPosts, 0, 30);
            }

            file_put_contents($filepath, json_encode($newPosts));
            chmod($filepath, 0777);

            $body = "timetest";
            $cache->save($body, $cacheKey, array('twitter'.$this->params['screen_name']),(24*60*60));
        }

        if (file_exists($filepath)) {
            $currentPostsJSON = file_get_contents($filepath);
        }

        return json_decode($currentPostsJSON);
    }

    /**
     * Loop through returned posts from Twitter and retrieve specific data.
     * @param  array $posts
     * @return array
     */
    public function parsePosts($posts)
    {
        $parsedPosts = array();

        foreach ($posts AS $post) {
           // $last_id=$post->id;
            $tweet=serialize($post);
            if(strpos($tweet,"ReNewYear") !== false || strpos($tweet,"renewyear") !== false){

            $tags   = array();
            $images = array();

            // get tags
            if (!empty($post->entities->hashtags)) {
                foreach ($post->entities->hashtags AS $tag) {
                    $tags[] = $tag->text;
                }
            }

            // get images
            if (!empty($post->entities->media) AND !empty($post->entities->media[0]->media_url)) {
                $images['small'] = $post->entities->media[0]->media_url . ':small';
                $images['standard'] = $post->entities->media[0]->media_url;
            }

            $parsedPosts[] = array(
                'source'      => 'twitter',
                'id'          => $post->id_str,
                'timestamp'   => strtotime($post->created_at),
                'date'        => date('g:i a n/j/Y', strtotime($post->created_at)),
                'link'        => 'https://twitter.com/' . $post->user->screen_name . '/status/' . $post->id_str,
                'full_name'   => $post->user->name,
                'username'    => $post->user->screen_name,
                'avatar'      => !empty($post->user->profile_image_url) ? $post->user->profile_image_url : null,                
                'captionText' => !empty($post->text) ? $this->convertURLsToLinks($post->text) : null,
                'tags'        => $tags,
                'images'      => $images,
            );
        }
        }
       // error_log($last_id);

        return $parsedPosts;
    }

     /**
     * Make url links clickable
     * @param  string $post
     * @return string
     */
    private function convertURLsToLinks($post) {
        $post = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $post);
        $post = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $post);
        $post = preg_replace("/@(\w+)/", "<a href=\"https://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $post);
        $post = preg_replace("/#(\w+)/", "<a href=\"https://twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $post);
        return $post;
    }
    static private function searchForId($id, $posts)
    {
        foreach ($posts as $post) {
            if ($post['id'] === $id) {
                return true;
            }
        }

        return false;
    }
}