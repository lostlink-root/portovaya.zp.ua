<?php
include 'config.php';
$mysqli = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if ($mysqli->connect_errno) {
    echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
if (!($mysqli->query("DROP table if exists oc_hotline_feed;") && $mysqli->query("
CREATE table oc_hotline_feed as
SELECT 
name,
concat('https://portovaya.zp.ua/index.php?route=product/product&product_id=', product_id) as product_url,
sku,
coalesce(filter_name, manufacturer_name, 'Portovaya') as brand_name,
if(image = '', 'https://portovaya.zp.ua/image/placeholder-600x600.png', concat('https://portovaya.zp.ua/image/', image)) as image_url,
quantity,
price,
category_name
FROM `oc_product` op
JOIN `oc_product_description` opd using (product_id)
JOIN (
	SELECT product_id,max(category_id) as category_id FROM oc_product_to_category oct
	GROUP BY product_id 
) cat using (product_id)
JOIN (
	SELECT category_id, name as category_name FROM oc_category_description ocd WHERE language_id = 7
) cat_names using (category_id)
LEFT JOIN (
	SELECT product_id, option_value_id FROM oc_filter_option_value_to_product 
	WHERE option_id = 45
) filters using (product_id)
LEFT JOIN (
	SELECT option_value_id, name as filter_name FROM oc_filter_option_value_description
	WHERE language_id = 7
) filter_names using (option_value_id)
LEFT JOIN (
	SELECT manufacturer_id, name as manufacturer_name FROM oc_manufacturer
) man_names using (manufacturer_id)
;

"))) echo "Не удалось создать таблицу: (" . $mysqli->errno . ") " . $mysqli->error;

else {
     $sql_query = "SELECT * FROM oc_hotline_feed";

    $result = $mysqli->query($sql_query);

    $f = fopen('php://temp', 'wt');
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            fputcsv($f, array_keys($row));
            $first = false;
        }
        fputcsv($f, $row);
    } // end while
    
    $mysqli->close();
    
    $size = ftell($f);
    rewind($f);
    $date1 = substr(date(DATE_ATOM), 0, 10);
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Length: $size");
    // Output to browser with appropriate mime type, you choose ;)
    header("Content-type: text/x-csv");
    header("Content-type: text/csv");
    header("Content-type: application/csv");
    header("Content-Disposition: attachment; filename=feed_$date1.csv");
    fpassthru($f);
}
  
?>