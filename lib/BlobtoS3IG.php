<?php

use Aws\S3\S3Client;

require_once __DIR__ . '/ImageGenerator.php';

class BlobtoS3IG extends ImageGenerator\ImageGenerator {

    protected $s3;
    protected $blobs = array();

    public function __construct($request = NULL) {
        parent::__construct($request);
    }

    /**
     * Instantiate the S3 client with your AWS credentials
     */
    public function initS3Client() {
        $credentials = new Aws\Credentials\Credentials(S3_ACCESS_KEY, S3_SECRET_KEY);

        $this->s3 = S3Client::factory(
                        array(
                            'credentials' => $credentials,
                            'region' => 'us-east-1',
                            'version' => 'latest',
                        )
        );
    }
    
    /**
     * Push local image blob to S3 cloud.
     * @parem string $blob Blob of image file.
     * @param string $file Path to local image file.
     * @return void
     */
    public function pushS3Blob($blob, $file) {
        try {
            $remoteFileName = strstr($file, 'usr');
            // Upload data.
            $result = $this->s3->putObject(array(
                'Bucket' => S3_BUCKET,
                'Key' => $remoteFileName,
                'Body' => $blob,
                'ACL' => 'public-read',
                'ContentType' => 'image/jpeg'
            ));

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }

    /**
     * Main parser to generate directory and images. Will echo JSON response.
     * @param stirng $saveTo Realpath to storage folder.
     * @param string $structure Folder structure under target folder.
     * @param string $encode Encode string.
     * @param bool $clean Whether clean file after generation.
     * @return void
     */
    public function generateImagesTo($saveTo, $structure = NULL, $encode = NULL, $clean = FALSE) {
        $this->imageFolder = $saveTo;
        global $S3_enable;

        if ($S3_enable) {
            $this->initS3Client();
        }

        if ($this->buildNamespace($saveTo, $structure) && $this->createShortUrl($encode)) {
            foreach ($this->inputs as $input) {
                $this->generate($input);
            }
        }

        if ($S3_enable) {
            foreach ($this->blobs as $blob) {
                $this->pushS3Blob($blob[0], $blob[1]);
            }
        }

        if ($clean) {
//            $this->recursiveRemoveDirectory($this->directory);
        }

        return $this->responseJSON();
    }
    

    /**
     * Save image to local or S3.
     * @param imagick $image
     * @param string $directory
     * @param string $filename
     * @return boolean
     */
    protected function saveURL($image, $directory, $filename) {
        global $S3_enable;
        $path = $directory . $filename . '.jpg';
        array_push($this->blobs, array($image->getimageblob(), str_replace($this->imageFolder, realpath($this->imageFolder), $path)));
        
        if ($S3_enable) {
            $sub_path = str_replace(array($this->imageFolder), S3_SITE_BUCKET, $path);
        } else {
            $sub_path = str_replace(array('..'), BASE_URL, $path);
        }

        return array('name' => $filename, 'path' => $sub_path);
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

        return $this->directory;
    }

}
