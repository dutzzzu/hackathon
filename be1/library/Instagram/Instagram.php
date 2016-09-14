<?php

class Instagram_Instagram {

    private $params=array();


    private function getToken(){

        return false;
    }
    public function setParam($key,$value){
        $this->params[$key]=$value;
        return true;
    }
    public function getParam($key){
        return isset($this->params[$key]) ? $this->params[$key] : false;
    }

    public function getRecentMedia($noCache=false) {

        if(!isset($this->params['user_id'])) return array("error"=>"Please set an user id in order to get data!");
        if(!isset($this->params['client_id'])) return array("error"=>"Please set an client id in order to get data!");

        $cache = Zend_Registry::get('cache');
        $cacheKey = md5("last100instagram");

        $filepath=APPLICATION_PATH."/../data/instagram_media_".$this->params['user_id'].".json";

        if ($noCache || ($body = $cache->load($cacheKey)) === false ) {

// the instagram recent media by curl
            $user_id=$this->params['user_id'];
            unset($this->params['user_id']);
            $service_url = 'https://api.instagram.com/v1/users/'.urlencode($user_id).'/media/recent?'.http_build_query($this->params);
            $curl = curl_init($service_url);

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //IMP if the url has https and you don't want to verify source certificate

            $curl_response = curl_exec($curl);


            $response = json_decode($curl_response);
            //error_log($response->pagination->next_max_id);
            curl_close($curl);

            $body = $this->parsePosts($response->data);


            if (file_exists($filepath)) {
                $currentPostsJSON = file_get_contents($filepath);
            }

            $current_insta_media_json= !empty($currentPostsJSON) ? $currentPostsJSON : json_encode(array());


            $currentPosts = json_decode($current_insta_media_json, true);

            $newPosts=$body;

            // merge new posts with current posts
            // first remove duplicate posts
            if (!empty($currentPosts)) {
                foreach ($body AS $key => $newPost) {
                    if (self::searchForId($newPost['id'], $currentPosts)) {
                        unset($body[$key]);
                    }
                }

                $newPosts = array_merge($body, $currentPosts);
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
            $cache->save($body, $cacheKey, array('instagram'.$user_id),(24*60*60));
        }

        if (file_exists($filepath)) {
            $currentPostsJSON = file_get_contents($filepath);
        }

        return json_decode($currentPostsJSON);

    }


    /**
     * Loop through returned posts from Instagram and retrieve specific data.
     * @param  array $posts
     * @return array
     */
    private function parsePosts($posts)
    {
        $parsedPosts = array();

        foreach ($posts AS $post) {

            if (!empty($post->tags) AND in_array(strtolower('renewyear'), $post->tags)) {

                // get images
                $images = array();

                if(!empty($post->images->low_resolution->url)) {
                    $images['small'] = $post->images->low_resolution->url;
                }

                // if(!empty($post->images->thumbnail->url)) {
                //     $images['thumbnail'] = $post->images->thumbnail->url;
                // }

                if(!empty($post->images->standard_resolution->url)) {
                    $images['standard'] = $post->images->standard_resolution->url;
                }

                $parsedPosts[] = array(
                    'source'      => 'instagram',
                    'id'          => $post->id,
                    'timestamp'   => $post->created_time,
                    'date'        => date('g:i a n/j/Y', $post->created_time),
                    'link'        => !empty($post->link) ? $post->link : null,
                    'full_name'   => $post->user->full_name,
                    'username'    => $post->user->username,
                    'avatar'      => !empty($post->user->profile_picture) ? $post->user->profile_picture : null,
                    'captionText' => !empty($post->caption->text) ? $this->convertURLsToLinks($post->caption->text) : null,
                    'tags'        => $post->tags,
                    'images'      => $images,
                );
            }
        }

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
        $post = preg_replace("/@(\w+)/", "<a href=\"http://instagram.com/\\1\" target=\"_blank\">@\\1</a>", $post);
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