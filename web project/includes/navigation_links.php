<?php

function get_back_link($default_url = 'index.php') {
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        $current_page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
        if ($_SERVER['HTTP_REFERER'] !== $current_page_url  ) {
           
            if (basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) !== 'login.php') {
                 return htmlspecialchars($_SERVER['HTTP_REFERER']);
            }
        }
    }
    return htmlspecialchars($default_url);
}
?>