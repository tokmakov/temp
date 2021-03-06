<?php
/**
 * Класс Article_Frontend_Model для работы со статьями, взаимодействует
 * с базой данных, общедоступная часть сайта
 */
class Article_Frontend_Model extends Frontend_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Возвращает массив всех статей (во всех категориях)
     */
    public function getAllArticles($start = 0) {

        $query = "SELECT
                      `a`.`id` AS `id`, `a`.`name` AS `name`,
                      `a`.`excerpt` AS `excerpt`,
                      DATE_FORMAT(`a`.`added`, '%d.%m.%Y') AS `date`,
                      DATE_FORMAT(`a`.`added`, '%H:%i:%s') AS `time`,
                      `b`.`id` AS `ctg_id`, `b`.`name` AS `ctg_name`,
                      `b`.`parent` AS `parent`,
                      (SELECT `c`.`id` FROM `article_categories` `c` WHERE `c`.`id` = `b`.`parent`) AS `root_id`,
                      (SELECT `d`.`name` FROM `article_categories` `d` WHERE `d`.`id` = `b`.`parent`) AS `root_name`
                  FROM
                      `article_items` `a`
                      INNER JOIN `article_categories` `b` ON `a`.`category` = `b`.`id`
                  WHERE
                      1
                  ORDER BY
                      `a`.`added` DESC
                  LIMIT
                      :start, :limit";

        $articles = $this->database->fetchAll(
            $query,
            array(
                'start' => $start,
                'limit' => $this->config->pager->frontend->article->perpage
            )
        );

        // добавляем в массив статей информацию об URL статьи, картинки, категории
        foreach($articles as $key => $value) {
            $articles[$key]['url']['item'] = $this->getURL('frontend/article/item/id/' . $value['id']);
            if (is_file('files/article/thumb/' . $value['id'] . '.jpg')) {
                $articles[$key]['url']['image'] = $this->config->site->url . 'files/article/thumb/' . $value['id'] . '.jpg';
            } else {
                $articles[$key]['url']['image'] = $this->config->site->url . 'files/article/thumb/default.jpg';
            }
            // URL категории статьи
            $articles[$key]['url']['category'] = $this->getURL('frontend/article/category/id/' . $value['ctg_id']);
            // URL корневой категории статьи
            if (!empty($articles[$key]['parent'])) {
                $articles[$key]['url']['root'] = $this->getURL('frontend/blog/category/id/' . $value['root_id']);
                unset($articles[$key]['parent']);
            } else {
                unset($articles[$key]['parent'], $articles[$key]['root_id'], $articles[$key]['root_name']);
            }
        }

        return $articles;

    }

    /**
     * Возвращает общее количество статей (во всех категориях)
     */
    public function getCountAllArticles() {
        $query = "SELECT COUNT(*) FROM `article_items` WHERE 1";
        return $this->database->fetchOne($query);
    }

    /**
     * Возвращает массив статей категории с уникальным идентификатором $id
     */
    public function getCategoryArticles($id, $start) {

        $query = "SELECT
                      `a`.`id` AS `id`, `a`.`name` AS `name`,
                      `a`.`excerpt` AS `excerpt`,
                      DATE_FORMAT(`a`.`added`, '%d.%m.%Y') AS `date`,
                      DATE_FORMAT(`a`.`added`, '%H:%i:%s') AS `time`,
                      `b`.`id` AS `ctg_id`, `b`.`name` AS `ctg_name`,
                      `b`.`parent` AS `parent`,
                      (SELECT `c`.`id` FROM `article_categories` `c` WHERE `c`.`id` = `b`.`parent`) AS `root_id`,
                      (SELECT `d`.`name` FROM `article_categories` `d` WHERE `d`.`id` = `b`.`parent`) AS `root_name`
                  FROM
                      `article_items` `a`
                      INNER JOIN `article_categories` `b` ON `a`.`category` = `b`.`id`
                  WHERE
                      `a`.`category` = :id OR `a`.`category` IN
                      (SELECT `b`.`id` FROM `article_categories` `b` WHERE `b`.`parent` = :parent)
                  ORDER BY
                      `a`.`added` DESC
                  LIMIT
                      :start, :limit";

        $articles = $this->database->fetchAll(
            $query,
            array(
                'id' => $id,
                'parent' => $id,
                'start' => $start,
                'limit' => $this->config->pager->frontend->blog->perpage,
            )
        );

        // добавляем в массив статей информацию об URL статьи, картинки, категории
        foreach($articles as $key => $value) {
            $articles[$key]['url']['item'] = $this->getURL('frontend/article/item/id/' . $value['id']);
            if (is_file('files/article/thumb/' . $value['id'] . '.jpg')) {
                $articles[$key]['url']['image'] = $this->config->site->url . 'files/article/thumb/' . $value['id'] . '.jpg';
            } else {
                $articles[$key]['url']['image'] = $this->config->site->url . 'files/article/thumb/default.jpg';
            }
            // URL категории статьи
            $articles[$key]['url']['category'] = $this->getURL('frontend/article/category/id/' . $value['ctg_id']);
            // URL корневой категории статьи
            if (!empty($articles[$key]['parent'])) {
                $articles[$key]['url']['root'] = $this->getURL('frontend/blog/category/id/' . $value['root_id']);
                unset($articles[$key]['parent']);
            } else {
                unset($articles[$key]['parent'], $articles[$key]['root_id'], $articles[$key]['root_name']);
            }
        }

        return $articles;

    }

    /**
     * Возвращает количество статей в категории с уникальным идентификатором $id
     */
    public function getCountCategoryArticles($id) {
        $query = "SELECT
                      COUNT(*)
                  FROM
                      `article_items` `a`
                  WHERE
                      `a`.`category` = :id OR `a`.`category` IN
                      (SELECT `b`.`id` FROM `blog_categories` `b` WHERE `b`.`parent` = :parent)";
        return $this->database->fetchOne($query, array('id' => $id, 'parent' => $id));
    }

    /**
     * Возвращает информацию о статье с уникальным идентификатором $id
     */
    public function getArticle($id) {

        $query = "SELECT
                      `a`.`name` AS `name`, `a`.`source` AS `source`,
                      `a`.`keywords` AS `keywords`,
                      `a`.`description` AS `description`,
                      `a`.`excerpt` AS `excerpt`, `a`.`body` AS `body`,
                      DATE_FORMAT(`a`.`added`, '%d.%m.%Y') AS `date`,
                      DATE_FORMAT(`a`.`added`, '%H:%i:%s') AS `time`,
                      `b`.`id` AS `ctg_id`, `b`.`name` AS `ctg_name`,
                      `b`.`parent` AS `parent`
                  FROM
                      `article_items` `a` INNER JOIN `article_categories` `b` ON `a`.`category` = `b`.`id`
                  WHERE
                      `a`.`id` = :id";
        $article = $this->database->fetch($query, array('id' => $id));
        // если статья не найдена
        if ( ! $article) {
            return false;
        }
        // получаем корневую категорию статьи
        if ($article['parent']) {
            $query = "SELECT
                          `id`, `name`
                      FROM
                          `article_categories`
                      WHERE
                          `id` = :parent";
            $parent = $this->database->fetch($query, array('parent' => $article['parent']));
            $article['root_id'] = $parent['id'];
            $article['root_name'] = $parent['name'];
        }
        // подсвечиваем код
        $article['body'] = $this->highlightCodeBlocks($article['body']);
        return $article;

    }

    /**
     * Возвращает массив всех категорий статей в виде дерева
     */
    public function getCategories() {

        $query = "SELECT
                      `id`, `name`, `parent`
                  FROM
                      `article_categories`
                  WHERE
                      1
                  ORDER BY
                      `sortorder`";
        $data = $this->database->fetchAll($query);
        // добавляем в массив информацию об URL категорий
        foreach($data as $key => $value) {
            $data[$key]['url'] = $this->getURL('frontend/article/category/id/' . $value['id']);
        }

        // строим дерево
        $tree = $this->makeTree($data);
        return $tree;

    }

    /**
     * Возвращает информацию о категории с уникальным идентификатором $id
     */
    public function getCategory($id) {

        $query = "SELECT
                      `name`, `parent`, `description`, `keywords`
                  FROM
                      `article_categories`
                  WHERE
                      `id` = :id";
        $category = $this->database->fetch($query, array('id' => $id));
        // получаем родительскую категорию
        if ($category['parent']) {
            $query = "SELECT
                          `id`, `name`
                      FROM
                          `article_categories`
                      WHERE
                          `id` = :parent";
            $parent = $this->database->fetch($query, array('parent' => $category['parent']));
            $category['root_id'] = $parent['id'];
            $category['root_name'] = $parent['name'];
        }
        return $category;

    }

}