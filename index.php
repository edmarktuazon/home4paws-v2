<?php
// (A) PARSE URL PATH INTO AN ARRAY
// (A1) EXTRACT PATH FROM FULL URL
// E.G. http://site.com/foo/bar/ > $path = "/foo/bar/"
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// (A2) REMOVE BASE FOLDER FROM PATH
// E.G. "/foo/bar/" > "foo/bar/"
$base = "/";
if (substr($path, 0, strlen($base)) == $base) {
  $path = substr($path, strlen($base));
}

// (A3) EXPLODE INTO AN ARRAY
// E.G. "foo/bar/" > ["foo", "bar"]
$path = explode("/", rtrim($path, "/"));

// (B) LOAD REQUESTED PAGE ACCORDINGLY
// (B1) FOR "SINGLE SEGMENT" PATH
if (count($path)==1) { 
  $file = $path[0]=="" ? "home.php" : $path[0] . ".php"; 
}

// (B2) FOR "MULTI-SEGMENT" PATH
else { 
  $file = implode($path, "-") . ".php"; 
}

// (B3) LOAD REQUESTED FILE FROM PAGES FOLDER OR SHOW 404 ERROR
$folder = __DIR__ . DIRECTORY_SEPARATOR . "pages" . DIRECTORY_SEPARATOR;
$file = $folder . $file;
if (file_exists($file)) { require $file; }
else {
  http_response_code(404);
  require $folder . "404.php";

}
