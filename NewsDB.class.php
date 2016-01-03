<?php
require_once 'INewsDB.class.php';

class NewsDB implements INewsDB{
    
    private $_db;
    const DB_NAME = 'news.db';
    const RSS_NAME = 'rss.xml';
    const RSS_TITLE = 'Последние новости';
    const RSS_LINK = "htt://localhost/level3/news/news.php";
    
    function __get($name) {
        if($name == "db") {
            return $this->_db;
        }
        throw new Exception("Unknown property!");
    }
       
    function __construct() {
        $this->_db = new SQLite3(self::DB_NAME);
        if (is_file(self::DB_NAME) and filesize(self::DB_NAME) == 0) {    
          try{
            $sql = "CREATE TABLE msgs(
	                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                title TEXT,
                                category INTEGER,
                                description TEXT,
                                source TEXT,
                                datetime INTEGER)";
            if(!$this->_db->exec($sql))
                throw new Exception($this->_db->lastErrorMsg());

            $sql = "CREATE TABLE category(
                                id INTEGER,
                                name TEXT)";
            if(!$this->_db->exec($sql))
                throw new Exception($this->_db->lastErrorMsg());
            
            $sql = "INSERT INTO category(id, name)
                    SELECT 1 as id, 'Политика' as name
                    UNION SELECT 2 as id, 'Культура' as name
                    UNION SELECT 3 as id, 'Спорт' as name";
            if(!$this->_db->exec($sql))
                throw new Exception($this->_db->lastErrorMsg());
          } catch(Exception $e){
              //$e->getMessage();
              return false;
          }
        } 
    }
    
    function __destruct() {
        unset($this->_db);
    }
      
    function clearStr($data) {
        $data = trim(strip_tags($data));
        return $this->_db->escapeString($data);
    }
    
    function clearInt($data) {
        return abs((int)$data);
    }
    
    function saveNews($title, $category, $description, $source) {
        try{
        $dt = time(); 
        $sql = "INSERT INTO msgs(title, category, description, source, datetime) VALUES (:title, :category, :description, :source, :dt)";
        $stmt = $this->_db->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':source', $source);
        $stmt->bindParam(':dt', $dt);
        $result = $stmt->execute();
        if(!$result){
                throw new Exception($this->_db->lastErrorMsg());
            }
            $this->createRss();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    protected function dbToArr($data) {
        $arr = array();
        while($row = $data->fetchArray(SQLITE3_ASSOC)) {
            $arr[] = $row;
        }
        return $arr;
    }
            
    function getNews(){
        try {
            $sql = "SELECT msgs.id as id, title, category.name as category, description, source, datetime
                    FROM msgs, category WHERE category.id = msgs.category ORDER BY msgs.id DESC";
            $result = $this->_db->query($sql);
            if(!is_object($result)) {
                throw new Exception($this->_db->lastErrorMsg());
            }
            return $this->dbToArr($result);
        } catch (Exception $e) {
            //$e->getMessage();
            return false;
        }
    }
    function deleteNews($id){
        try{
            $sql = "DELETE FROM msgs WHERE id=$id";
            $result = $this->_db->exec($sql);
            if(!$result){
                throw new Exception($this->_db->lastErrorMsg());
            }
            return true;
        }  catch (Exception $e) {
            //$e->getMessage();
            return false;
        }
    }
    
    private function createRss() {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        
        $rss = $dom->createElement('rss');
        $dom->appendChild($rss);
        
        $version = $dom->createAttribute("version");
        $version->value = '2.0';
        $rss->appendChild($version);
        
        $channel = $dom->createElement('channel');
        $rss->appendChild($channel);
        
        $title = $dom->createElement('title', self::RSS_TITLE);
        $channel->appendChild($title);
        
        $link = $dom->createElement('link', self::RSS_LINK);
        $channel->appendChild($link);
        
        $lenta = $this->getNews();
        if(!$lenta) return false;
        foreach ($lenta as $news) {
            $item = $dom->createElement('item');
            $title = $dom->createElement('title', $news['title']);
            $category = $dom->createElement('category', $news['category']);
            
            $description = $dom->createElement('description');
            $cdata = $dom->createCDATASection($news['description']);
            $description->appendChild($cdata);
            
            $txt =self::RSS_LINK.'?id='.$news['id'];
            $link = $dom->createElement('link', $txt);
            
            $dt = date('r', $news['datetime']);
            $pubDate = $dom->createElement('pubDate', $dt);
            
            $item->appendChild($title);
            $item->appendChild($link);
            $item->appendChild($description);
            $item->appendChild($pubDate);
            $item->appendChild($category);
            
            $channel->appendChild($item);
        }
        $dom->save(self::RSS_NAME); 
    }
}
