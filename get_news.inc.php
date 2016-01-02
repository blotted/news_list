<?php
if(!is_array($result = $news->getNews())) {
    $errMsg = "Произошла ошибка при выводе новостей.";
} else {
    echo '<p>Всего последних новостей: ' . count($result);
    foreach($result as $item){
        $id = $item['id'];
        $title = $item['title'];
        $category = $item['category'];
        $description = nl2br($item['description']);
        $source = $item['source'];
        $dt = date('d-m-Y H:i:s', $item['datetime']);

        echo <<<_END
        <hr>
        <h3>$title</h3>
        <p>$description<br><br>[$category] @ $dt<br>Источник: $source</p>
        <p align='right'>
            <a href='news.php?del=$id'>Удалить</a>
        </p> 
_END;
}
}