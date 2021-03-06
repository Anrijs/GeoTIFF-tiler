<?php
include "config/config.php";
include "lib/tifftools.php";

function has_prefix($string, $prefix) {
    return ((substr($string, 0, strlen($prefix)) == $prefix) ? true : false);
}

$target_dir = $_R["maps"];

$dir = uniqid();
while(file_exists($_R["maps"] . $dir)) {
  $dir = uniqid();
}

$target_dir = $target_dir . $dir . "/";

$fname = basename($_FILES["fileToUpload"]["name"]);
$mapname = $_POST['mapName'];

if (strlen(trim($mapname)) == 0) {
    $mapname = $fname;
}

$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);


$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
}

// Check if file already exists
if (file_exists($target_file)) {
    echo "Sorry, file already exists.";
    $uploadOk = 0;
}
// Check file size
if ($_FILES["fileToUpload"]["size"] > 500000000) { // GeoTIFF can be heavy.  Allow 500MB max
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
}

// Allow certain file formats
if($imageFileType != "tif" && $imageFileType != "tiff") {
    echo "Sorry, only TIFF files are allowed. (" . $imageFileType . " uploaded)";
    echo "<br>fname: " . $fname . "<br>";
    echo "<br>target_file: " . $target_file . "<br>";

    $uploadOk = 0;
}
// Check if $uploadOk isset(var) set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if(mkdir($target_dir) == 0) {
        echo "Failed to upload map. Maps directory (".$target_dir.") not writeable.";
    }
    $namefile = fopen($target_dir . "name", "w");
    fwrite($namefile, $mapname);
    fclose($namefile);
    
    $target_file = str_replace(".tiff", ".tif", $target_file);

    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        //echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";

        # make png preview

        $target_file_512_png = str_replace(".tif", ".512.png", $target_file);
        $target_file_64_png = str_replace(".tif", ".64.png", $target_file);
        $cmd = "convert " . $target_file . " -resize \"64^>\" " . $target_file_64_png;
        shell_exec($cmd);
        $cmd = "convert " . $target_file . " -resize \"512^>\" " . $target_file_512_png;
        shell_exec($cmd . ' > /dev/null 2>/dev/null &');

        // GeoTIFF info
        $tiff_info = getGdalInfo($target_file);
   
        $coordfile = fopen($target_dir . "coord", "w");
        fwrite($coordfile, $tiff_info["center"][0] . "," . $tiff_info["center"][1]);
        fclose($coordfile);

        $infofile = fopen($target_dir . "info", "w");
        fwrite($infofile, $tiff_info["p"] . "," . $tiff_info["s"]); // preimeter, area
        fclose($infofile);

        header("Location: maps.php");
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>
