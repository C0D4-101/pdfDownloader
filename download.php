<?php
error_reporting(E_ALL);

function runDownloader($directory, $sourceUrl){

    echo "[*] Fetching eBooks from ".$sourceUrl."\n\n";

    $source = file_get_contents($sourceUrl);
    $pdfs = extractUrls($source);

    foreach($pdfs AS $key => $url){
        //fetch ebook based on URL
        //split url on "/"
        $splitUrl = explode("/", $url);
        //file name will be the last portion of the url
        $name = end($splitUrl);
        //check file exists locally
        if(!file_exists($directory."\\".$name)){
            //download ebook
            echo "[*] Downloading ".$name; 

            $data = downloadBooks($url);
            if($data){
                echo " - ".formatBytes($data['size'])."\n"; 
                //save the book to file with name from url
                $book = saveBooks($directory, $name, $data['data']);
            }
            else
                echo " - File not found \n"; 
        }
        else
            echo "[X] File ".$name." - Already exists.\n"; 
    }
}

function extractUrls($sourceData){
    $url = array();
    $pdfs = array();
    // extract all <a href> tags and add to match Array
    // this will product a multi-dimension array with top indexes
    preg_match_all('/href="([^\s"]+)/', $sourceData, $match);
    
    //loop through both indexes and build a complete list in the url array
    foreach($match[0] AS $index => $link){
        $url[] = str_replace("href=\"", "", $link);
    }
    
    foreach($match[1] AS $index => $link){
        $url[] = $link;
    }

    //find all urls matched on file extenion,
    //you can extend the regex to accept other extension (pdf|png|doc)
    foreach($url AS $key => $value){
        preg_match("/.*\.(pdf)/", $value, $matches, PREG_OFFSET_CAPTURE, 0);
        if(isset($matches[0][0]))
            $pdfs[] = $matches[0][0];
    }

    return $pdfs;
}

function downloadBooks($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    if($info['http_code'] != 200 || $info['content_type'] != "application/pdf")
        $data = false;
    else
        $data = array("data" => $result, "size" => $info['size_download']);

    return $data;
}

function saveBooks($directory, $name, $data){
    $fp = fopen($directory."\\".$name,"wa+");
    fwrite($fp, $data);
    fclose($fp);
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 


//check for any CLI arguments and add them to the $_GET global
//* Available CLI arguments
//* dir : local directory to save files to 
//* url : webpage to scrape links from
if($argc > 1)
    parse_str( implode( '&', array_slice( $argv, 1) ), $_GET);

//check for the dir argument, also have a defualt location
if(isset($_GET['dir']) && trim($_GET['dir']) != "")
    $directory = $_GET['dir'];
else
    $directory = ""; //Configure this to a local directory

//check for the url argument, also have a defualt url
if(isset($_GET['url']) && trim($_GET['url']) != "")
    $sourceUrl = $_GET['url'];
else
    $sourceUrl = "https://github.com/EbookFoundation/free-programming-books/blob/master/free-programming-books.md"; //Default URL to download files from.

if(empty($directory) || empty($sourceUrl))
    exit("[X] Error: A URL and Directory are required");
//lets do some magic
runDownloader($directory, $sourceUrl);

?>
