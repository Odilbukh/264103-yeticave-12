<?php
require_once "init.php";
require_once "helpers.php";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $str_search = trim(get_value("search"));

    if (!empty($str_search)) {
        $str_search = $str_search."*";
        list($count_lots, $page_count) = compute_pagination_offset_and_limit(
            $con,
            "SELECT COUNT(id) AS 'count' FROM `lots`
                WHERE MATCH(name, description) AGAINST(? IN BOOLEAN MODE)
                AND `end_date` > NOW()",
            $str_search
        );
        $current_page = get_page_value();
        $offset = get_offset_items($current_page,COUNT_ITEMS);
        // информация по лотам на странице в количестве 9 штук и со смещением
        $query_search_lot = "SELECT 
            lots.*, 
            categories.name AS category_name 
        FROM `lots` AS lots
        JOIN `categories` AS categories
        ON categories.id = lots.category_id
        WHERE MATCH(lots.name, lots.description) AGAINST(? IN BOOLEAN MODE)
        AND lots.end_date > NOW() 
        ORDER BY lots.dt_add DESC 
        LIMIT " . COUNT_ITEMS . " OFFSET " . $offset;
        $stmt = db_get_prepare_stmt($con, $query_search_lot, [$str_search]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $search_text = get_escape_string($con, get_value("search"));

        if ($count_lots !== 0) {
            $lots = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $content = include_template("search-page.php", [
                "str_search" => $search_text,
                "lots" => $lots,
                "count_lots" => $count_lots,
                "page_count" => $page_count,
                "current_page" => $current_page,
            ]);
        } else {
            $content = include_template("search-page.php", [
                "empty_search" => "Ничего не найдено по вашему запросу",
            ]);
        }
    } else {
        $content = include_template("search-page.php", [
            "empty_search" => "Ничего не найдено по вашему запросу",
        ]);
    }
}

$layout_content = include_template("layout.php", [
    "content" => $content,
    "title_page" => "Страница регистрации",
    "user_name" => get_value_from_user_session("name"),
    "categories" => $categories,
]);

print($layout_content);