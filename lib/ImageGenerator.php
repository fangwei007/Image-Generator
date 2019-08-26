<?php

namespace ImageGenerator;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

class ImageGenerator {

    public $mysqli;
    public $table;
    public $inputs;
    public $imageFolder;
    public $shortUrl;
    public $directory;
    public $response = array();
    public $localFiles = array();
    public $toReplaceWithURL;

    /**
     * Constructor
     * Set up Request object.
     * @global array $inputs Common assets to use.
     * @param Request $request optional. User JSON input (the same format rule with $inputs).
     * @return void
     */
    public function __construct($request = NULL) {
        global $inputs;
        if (is_string($request)) {
            $request = json_decode($request, TRUE);
        }
        if (!empty($request)) {
            array_push($inputs, $request);
        }
        $this->inputs = $inputs;
    }

    /**
     * Connect to database.
     * @global boolean $database_enable. Flag to control usage of db.
     * @global array $database. Detailed settings for database.
     * @return boolean
     */
    public function dbconnect($db_host, $db_user, $db_pass, $db_name, $db_table) {
        global $database_enable;
        if (TRUE == $database_enable && !isset($this->mysqli)) {
            $this->mysqli = new \mysqli($db_host, $db_user, $db_pass, $db_name);
            $this->table = $db_table;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Connect to database.
     * @param \mysqli $mysqli
     */
    public function setDbConnection(\mysqli $mysqli) {
        $this->mysqli = $mysqli;
    }

    /**
     * Set table name.
     * @param type $tablename
     */
    public function setDbTable($tablename) {
        $this->table = $tablename;
    }

    /**
     * Close database connection.
     * @return void
     */
    public function dbclose() {
        $this->mysqli->close();
    }

    /**
     * Set image folder to store generated images.
     * @param string $imageFolder Path to image folder.
     * @return void
     */
    protected function setImageFolder($imageFolder) {
        $this->imageFolder = $imageFolder;
    }

    /**
     * Main parser to generate directory and images. Will echo JSON response.
     * @return void
     */
    protected function generateImages() {
        if ($this->buildNamespace($this->imageFolder) && $this->createShortUrl()) {
            foreach ($this->inputs as $input) {
                $this->generate($input);
            }
        }

        return $this->responseJSON();
    }

    /**
     * Main parser to generate directory and images. Will echo JSON response.
     * @param string $saveTo Destination folder to store images.
     * @param string $structure The tree structure under destination folder.
     * @param string $encode The encode string.
     * @return void
     */
    public function generateImagesTo($saveTo, $toReplaceWithURL, $structure = NULL, $encode = NULL) {
        $this->toReplaceWithURL = $toReplaceWithURL;
        $this->imageFolder = $saveTo;
        if ($this->buildNamespace($saveTo, $structure) && $this->createShortUrl($encode)) {
            foreach ($this->inputs as $input) {
                $this->generate($input);
            }
        }

        return $this->responseJSON();
    }

    /**
     * Retrieving image form database. Under database enabled mode.
     * @param string $shortUrl Unique short url for everything image.
     * @return boolean
     */
    public function isStored($shortUrl) {
        $stmt = $this->mysqli->prepare("SELECT id FROM $this->table WHERE shortUrl = ?");
        $stmt->bind_param("s", $shortUrl);
        if (!$stmt->execute()) {
            error_log($this->mysqli->error());
        }
        $stmt->bind_result($id);
        $stmt->fetch();
        $stmt->close();
        return $id == NULL;
    }

    /**
     * Get all image urls from database of specified short url.
     * @global array $schema. Db columns for images.
     * @param string $shortUrl. Unique short url for everything image.
     * @return array. Return images info.
     */
    public function getImageUrls($shortUrl) {
        global $schema;
        $statement = "SELECT ";
        foreach ($schema as $col) {
            $statement .= $col . ",";
        }
        $statement = substr($statement, 0, strrpos($statement, ','));
        $statement .= " FROM $this->table WHERE shortUrl = '$shortUrl'";

        $stmt = $this->mysqli->prepare($statement);
        if (!$stmt->execute()) {
            error_log($this->mysqli->error());
        }

        $stmt->store_result();
        $result = $this->fetchAssocStatement($stmt);
        $stmt->close();

        return $result;
    }

    /**
     * Helper func to fetch database data into array.
     * @param mysqli_prepare $stmt
     * @return mixed
     */
    protected function fetchAssocStatement($stmt) {
        if ($stmt->num_rows > 0) {
            $result = array();
            $md = $stmt->result_metadata();
            $params = array();
            while ($field = $md->fetch_field()) {
                $params[] = &$result[$field->name];
            }
            call_user_func_array(array($stmt, 'bind_result'), $params);
            if ($stmt->fetch())
                return $result;
        }
        return NULL;
    }

    /**
     * Build directory structure for local images saving.
     * @param string $directory Path to target directory.
     * @param string $structure Structure under target directory.
     * @return boolean
     */
    public function buildNamespaceOnTimestamp($directory, $structure = NULL) {
        list($year, $month, $day) = explode('-', date('Y-m-d'));
        umask(0);

        if ($structure != NULL) {
            $this->directory = "$directory/$structure/" . time() . rand(1, 10000) . '/';
        } else {
            // Build name space synced to date and timestamp
            $this->directory = "$directory/usr/$year/$month/$day/" . time() . rand(1, 10000) . '/';
        }

        // Create dir
        if (!file_exists($this->directory)) {
            if (!mkdir($this->directory, 0777, TRUE)) {
                return FALSE;
            }
        }

        return $this->directory;
    }

    /**
     * Build directory structure for local images saving.
     * @param string $directory Path to target directory.
     * @param string $structure Structure under target directory.
     * @return boolean
     */
    public function buildNamespaceOnShortUrl($directory) {
        umask(0);
        $this->directory = "$directory/usr/$this->shortUrl/";

        // Create dir
        if (!file_exists($this->directory)) {
            if (!mkdir($this->directory, 0777, TRUE)) {
                return FALSE;
            }
        }

        return $this->directory;
    }

    /**
     * Create unique short url for user's images recursively.
     * @return boolean Return TRUE when succeeds.
     */
    public function createShortUrl($encode) {
        $encode = $encode == NULL ? "Image Generator" : $encode;
        // Create the hashid class object
        $hashids = new \Hashids\Hashids($encode);

        // Encode one number 0 - 10000000
        $this->shortUrl = $hashids->encode(rand(0, 10000000)) . $hashids->encode(time());

        // Check if the shortUrl already exists, if does, regenerate
        if (!$this->isValidShortUrl($this->shortUrl)) {
            $this->createShortUrl();
        }
        // shortUrl is always a string
        return TRUE;
    }

    /**
     * Find whether a short url is unique or not. 
     * @param string $shortUrl
     * @return boolean
     */
    protected function isValidShortUrl($shortUrl) {
        $id = NULL;
        if ($this->mysqli) {
            $stmt = $this->mysqli->prepare("SELECT id FROM $this->table WHERE shortUrl=?");
            $stmt->bind_param("s", $shortUrl);
            if (!$stmt->execute()) {
                $response = array('success' => FALSE, 'error' => 'Retrieving failed!');
                header('Content-Type: application/json');
                echo json_encode($response);
            }
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();
        }
        return $id == NULL;
    }

    /**
     * Main generating function.
     * @param array $options All images detailed informations for generating.
     * @return void
     */
    public function generate($options) {
        $this->sortImage($options['media']);
        if ($options['media'][0]['type'] !== "blob") {
            $canvas = new \Imagick($options['media'][0]['src']);
        } else {
            $canvas = new \Imagick();
            $canvas->readImageBlob($options['media'][0]['src']);
        }
        $canvas->cropimage($options['media'][0]['width'], $options['media'][0]['height'], ($canvas->getimagewidth() - $options['media'][0]['width']) / 2, ($canvas->getimageheight() - $options['media'][0]['height']) / 2);
        array_shift($options['media']);

        foreach ($options['media'] as $medium) {
            if ($medium['type'] == 'blob') {
                $top = new \Imagick();
                $top->readImageBlob($medium['src']);
                $top->adaptiveresizeimage($medium['width'], $medium['height']);
                $this->composite($canvas, $top, $medium['coords'][0], $medium['coords'][1]);
                $top->destroy();
            } else if ($medium['type'] == 'image') {
                $top = new \Imagick($medium['src']);
                $top->adaptiveresizeimage($medium['width'], $medium['height']);
                $this->composite($canvas, $top, $medium['coords'][0], $medium['coords'][1]);
                $top->destroy();
            } else {
                $this->annotate($canvas, $medium['font'], $medium['size'], $medium['color'], $medium['text'], $medium['coords'][0], $medium['coords'][1], $medium['coords'][2], $medium['gravity']);
            }
        }

        // Set compression quality to low
        $canvas->setCompression(\Imagick::COMPRESSION_JPEG);
        $canvas->setCompressionQuality(88);

        // Save image to S3 (or locally) and prepare JSON response
        if (!$url = $this->saveURL($canvas, $this->directory, $options['name'])) {
            $response = array('success' => false, 'error' => $options['name'] . 'Image generation failed');
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            array_push($this->response, $url);
        }

        $canvas->destroy();
    }

    /**
     * Save image to local or S3.
     * @param imagick $image
     * @param string $directory
     * @param string $filename
     * @return boolean
     */
    protected function saveURL($image, $directory, $filename) {
        $path = $directory . $filename . '.jpg';
        // Image saved to the specified file location <dir tree>
        if (!$image->writeImage($path)) {
            $response = array('success' => FALSE, 'error' => 'Generating ' . $filename . '.jpg failed !');
            header('Content-Type: application/json');
            echo json_encode($response);
            return FALSE;
        }

        array_push($this->localFiles, str_replace($this->imageFolder, realpath($this->imageFolder), $path));
        $sub_path = BASE_URL . substr($path, strlen($this->toReplaceWithURL));

        return array('name' => $filename, 'path' => $sub_path);
    }

    /**
     * Composite an image to canvas.
     * @param imagick $canvas
     * @param imagick $top
     * @param integer $x
     * @param integer $y
     * @return boolean
     */
    protected function composite(&$canvas, $top, $x, $y) {
        return $canvas->compositeimage($top, \imagick::COMPOSITE_OVER, $x, $y);
    }

    /**
     * Annotate text onto canvas.
     * @param imagick $canvas
     * @param imagickdraw $font
     * @param integer $size
     * @param array $colorcode
     * @param string $text
     * @param integer $x
     * @param integer $y
     * @param integer $angle
     * @param integer $gravity
     * @return void
     */
    protected function annotate(&$canvas, $font, $size, $colorcode, $text, $x, $y, $angle, $gravity = 1) {
        $draw = new \ImagickDraw();
        $color = new \ImagickPixel($colorcode);
        $draw->setFont($font);
        $draw->setFontSize($size);
        $draw->setFillOpacity(1);
        $draw->setFillColor($color);
        $draw->setGravity($gravity);
//        $draw->settextkerning(2); // 左右间距
//        $draw->settextinterlinespacing(8); // 上下行间距

        $canvas->annotateImage($draw, $x, $y, $angle, $text);

        $draw->destroy();
    }

    /**
     * Sort image inputs by their levels.
     * @param array $array. All inputs.
     * @return void
     */
    protected function sortImage(&$array) {
        usort($array, function($a, $b) {
            if ($a['level'] == $b['level']) {
                return 0;
            }
            return ($a['level'] < $b['level']) ? -1 : 1;
        });
    }

    /**
     * Insert image info into database.
     * @return void
     */
    public function insertRow() {
        global $schema;
        date_default_timezone_set("America/New_York");
        $datetime = date('Y-m-d H:i:s');

        $statement = "INSERT INTO $this->table (shortUrl,";
        $marks = "VALUES ('$this->shortUrl',";
        $id = 0;
        foreach ($schema as $col) {
            $statement .= $col . ",";
            $marks .="'" . $this->response[$id++]['path'] . "',";
        }
        $statement .= 'entered) ';
        $marks .= '"' . $datetime . '")';
        $statement .= $marks;

        // Prepared statement for inserting row
        $stmt = $this->mysqli->prepare($statement);
        if (!$stmt->execute()) {
            $response = array('success' => FALSE, 'error' => 'Inserting failed!');
            header('Content-Type: application/json');
            echo json_encode($response);
            error_log($this->mysqli->error());
        }
        $stmt->close();
    }

    /**
     * Response with JSON data.
     * @return boolean
     */
    protected function responseJSON() {
        $response = array('success' => TRUE, 'shortUrl' => $this->shortUrl, 'images' => $this->response);
        header('Content-Type: application/json');
        echo json_encode($response);
        return TRUE;
    }

    /**
     * Recursively clean local files.
     * @param string $directory The url to usr directory
     * @return void
     */
    protected function recursiveRemoveDirectory($directory) {
        foreach (glob("{$directory}/{,.}*", GLOB_BRACE) as $file) {
            if ($file === "{$directory}/." || $file === "{$directory}/..")
                continue;
            if (is_dir($file)) {
                $this->recursiveRemoveDirectory($file);
            } else {
                unlink($file);
            }
        }
        rmdir($directory);
    }

}
