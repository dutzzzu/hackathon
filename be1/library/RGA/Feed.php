<?php

class RGA_Feed {

    protected $_title;
    protected $_description;
    protected $_link;
    protected $_feedLink;
    protected $_author;
    protected $_entries;

    public function __construct($title, $description, $link, $feedLink, array $author) {
        $this->_title = $title;
        $this->_description = $description;
        $this->_link = $link;
        $this->_feedLink = $feedLink;
        $this->_author = $author;
        $this->_entries = array();
    }
    /**
    * Array<CmsArticle>
    */
    public function setEntries(array $entries) {
        $this->_entries = $entries;
        return $this;
    }

    public function getFeed($type = 'rss', $serverUrl) {

        $feed = new \Zend_Feed_Writer_Feed();
        $feed->setTitle($this->_title);
        $feed->setDescription($this->_description);
        $feed->setLink($this->_link);
        $feed->setFeedLink($this->_feedLink, $type);
        $feed->addAuthor($this->_author);
        $feed->setDateModified(time());
        $feed->setGenerator(array(
            "name" => "Life Reimagined",
            "version" => "1.0",
            "uri" => $serverUrl
        ));
        foreach ($this->_entries as $article) {
            $item = $article->getFeedData();
            $entry = $feed->createEntry();
            $entry->setTitle( strip_tags($item['title']) );
            $entry->setLink($serverUrl . $item['link']);
            if (@$item['author']) {
                $entry->addAuthor($item['author']);    
            }
            $entry->setDateModified($item['modified']);
            $entry->setDateCreated($item['created']);
            if (@$item['description']) {
                $entry->setDescription($item['description']);
            }
            if (@$item['content']) {
                $entry->setContent($item['content']);
            }
            $feed->addEntry($entry);
        }
        return $feed->export($type);  
    }


}