<?php
// detectDisease.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// Check if a file is uploaded
if (!isset($_FILES['file'])) {
    echo json_encode(["error" => "No file uploaded"]);
    exit;
}





//api codes kapag ayaw gumana bwisit
//Fx0YVpt1wVVhidRmZC9i


// ✅ Updated model info
$api_key = "lty0TJAy6einUxkz4XEd";
$model_id = "plants-diseases-detection-and-classification/12";
$api_url = "https://serverless.roboflow.com/$model_id?api_key=$api_key";

// Read image and encode in Base64
$image_path = $_FILES['file']['tmp_name'];
$image_data = base64_encode(file_get_contents($image_path));

// Prepare POST request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $image_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute and handle response
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

// Return the Roboflow API response
echo $response;
?>
