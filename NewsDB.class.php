<?php
require_once 'INewsDB.class.php';

class NewsDB implements INewsDB{
    
    private $_db;
    const DB_NAME = 'news.db';
    
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
}
