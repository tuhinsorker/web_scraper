<?php
// URL containing the XML data

$xmlUrl = "https://yourpetpa.com.au/sitemap_products_1.xml?from=6963849068726&to=7760949379254";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $xmlUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');


$response = curl_exec($ch);


// Check for cURL errors
if ($response === false) {
    echo "cURL Error: " . curl_error($ch);
    exit;
}

// Close cURL session
curl_close($ch);

// Parse XML data
$xml = simplexml_load_string($response);

// Extract URLs
$productdetails = [];
$key = 0;
foreach ($xml->url as $url) {
    if ($key == 50) break;
    if ($key != 0) {
        $html = fetchHTMLContent($url->loc);
        $productdetails[] = scrapeProductInfo($html);
    }
    $key++;
}

makeCsv($productdetails);
// Print the list of product URLs

// Function to fetch HTML content from a URL
function fetchHTMLContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Function to scrape product information
function scrapeProductInfo($htmlContent)
{
    $productInfo = array();

    // Regex pattern to match product titles
    $titlePattern = '/<h3 class="product-detail__title heading-font-5">(.*?)<\/h3>/s';
    preg_match($titlePattern, $htmlContent, $titleMatches);
    $productInfo['titles'] = $titleMatches[1];

    // Regex pattern to match image URLs
    $imagePattern = '/<div class="product-detail__images-container">(.*?)<\/div>/s';
    // $imagePattern = '/<img (.*?)src="(.*?)"/s';
    preg_match($imagePattern, $htmlContent, $imageMatches);
    if ($imageMatches[0]) {
        // Regex pattern to match the image links inside the product detail images container
        $imageLinkPattern = '/<div class="product-detail__image">\s*<a href="(.*?)"/s';
        preg_match($imageLinkPattern, $imageMatches[0], $imageLinks);
        $productInfo['imageUrls'] = $imageLinks[1] ?? null;
    } else {
        $productInfo['imageUrls'] = null;
    }

    // Regex pattern to match prices
    $pricePattern = '/<span class="theme-money heading-font-4">(.*?)<\/span>/s';
    preg_match($pricePattern, $htmlContent, $priceMatches);
    $productInfo['prices'] = $priceMatches[1];
    // Regex pattern to match image URLs
    $pattern = '/<div class="product__description_full--width">(.*?)<\/div>/s';
    preg_match($pattern, $htmlContent, $description);
    $productInfo['description'] = $description[1] ? trim(strip_tags($description[1])) : null;
    // Regex pattern to match the second list item
    $pattern = '/<a class="breadcrumbs-list__link" (.*?)>(.*?)<\/a>/s';
    preg_match_all($pattern, $htmlContent, $matches);
    if (count($matches[2]) > 2) {
        $productInfo['category']  = trim(strip_tags($matches[2][1]));
    } else {
        $productInfo['category']  = '';
    }
    // Extract the text content of the second list item
    return $productInfo;
}



function makeCsv($data)
{
    // Open a file for writing
    $csvFile = fopen('D:\wamp64\www\inter\output.csv', 'w');

    // Write header
    fputcsv($csvFile, [
        'titles',
        'image urls',
        'prices',
        'description',
        'category'
    ]);

    // Write data
    foreach ($data as $row) {
        fputcsv($csvFile, $row);
    }

    // Close the file
    fclose($csvFile);

    echo "CSV file created successfully.\n";
}
