<?php
include_once 'include/QueryClass.php';
include_once 'include/utils.php';

require_once 'include/config.php';
require_once 'include/bdd.php';

global $pdo;

// Instantiate blog post object
$post = new QueryClass($pdo);

$json = file_get_contents('php://input');
//printf("DEBUG: JSON en entrée:\n%s\n", $json);

// Receive JSON file and converts it into a PHP object
$data = json_decode($json, true);


// Verify valid JSON format
if (json_last_error() !== JSON_ERROR_NONE) {
    raise_http_error("Invalid JSON format \n", 400);
}
try {
// Verify and parse request
    $post->parse_request($data);

// Verify token
    $post->verify_token($data);

// Execute query
    $response = null;
    switch ($post->get_code()) {

        case 1:
            // verify query
            verify_create_query($post->get_content());

            // execute query
            $response = $post->createArticle();
            break;

        case 3:
            // verify query
            verify_warehouses_query($post->get_content());

            // execute query
            $response = $post->getWarehouses();
            break;

        case 5:
            // verify query
            verify_products_query($post->get_content());


            // execute query
            $response = $post->getProducts();
            break;

        case 7:
            // verify query
            verify_update_query($post->get_content());

            // execute query
            $response = $post->update();
            break;

        case 9:
            // verify query
            verify_create_space($post->get_content());


            // execute query
            $response = $post->create_space();

            break;

        case 11:
            // verify query

            verify_get_product($post->get_content());

            // execute query

            $response = $post->get_product_information();
            break;

        default:
            break;
    }

// Json Encoding
    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode($response);

// success
    die(0);
} catch (Exception $e) {
    raise_http_error($e->getMessage(), is_int($e->getCode()) && $e->getCode() != 0 ? $e->getCode() : 403);
} catch (\Throwable $e) {
    raise_http_error($e->getMessage(), 403);
}
?>
