<?php

use Aws\S3\S3Client;

require_once __DIR__ . '/ImageGenerator.php';

class S3IG extends ImageGenerator\ImageGenerator {

    protected $s3;

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
     * Push local image to S3 cloud.
     * @param string $file Path to local image file.
     * @return void
     */
    public function pushS3($file, $onTimestamp) {
        $localFileName = $file;
        $remoteFileName = $onTimestamp == TRUE ? strstr($file, 'usr') : substr(strstr($file, 'usr'), 4);
        $handle = fopen($localFileName, 'r');

        try {
            // Upload data.
            $result = $this->s3->putObject(array(
                'Bucket' => S3_BUCKET,
                'Key' => $remoteFileName,
                'Body' => $handle,
                'ACL' => 'public-read'
            ));

            // Print the URL to the object.
            if (is_resource($handle)) {
                fclose($handle);
            }

            return $result;
        } catch (S3Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Check if object exists on S3 cloud.
     * @param string $object Path of object on S3 cloud
     * @return boolean TRUE exists; FALSE not exists.
     */
    public function retrieveS3($object) {
        if ($this->s3 == NULL) {
            $this->initS3Client();
        }

        return $this->s3->doesObjectExist(S3_BUCKET, $object);
    }

    /**
     * Main parser to generate directory and images. Will echo JSON response.
     * @param stirng $saveTo Realpath to storage folder.
     * @param string $structure Folder structure under target folder.
     * @param string $encode Encode string.
     * @param bool $clean Whether clean file after generation.
     * @return void
     */
    public function generateImagesTo($saveTo, $toReplaceWithURL, $onTimestamp = TRUE, $structure = NULL, $encode = NULL, $clean = FALSE) {
        global $S3_enable;

        if ($S3_enable) {
            $this->initS3Client();
        }

        $this->imageFolder = $saveTo;
        $this->toReplaceWithURL = $toReplaceWithURL;

        if ($onTimestamp) {
            if ($this->buildNamespace($saveTo, $structure) && $this->createShortUrl($encode)) {
                foreach ($this->inputs as $input) {
                    $this->generate($input);
                }
            }
        } else {
            if ($this->buildNamespaceOnShortUrl($saveTo) && $this->createShortUrl($encode)) {
                foreach ($this->inputs as $input) {
                    $this->generate($input);
                }
            }
        }

        if ($S3_enable) {
            foreach ($this->localFiles as $file) {
                $this->pushS3($file, $onTimestamp);
            }
        }

        if ($clean) {
            $this->recursiveRemoveDirectory($this->directory);
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

        if (!$image->writeImage($path)) {
            $response = array('success' => FALSE, 'error' => 'Writing ' . $filename . '.jpg failed!!');
            header('Content-Type: application/json');
            echo json_encode($response);
            return FALSE;
        }

        array_push($this->localFiles, str_replace($this->imageFolder, realpath($this->imageFolder), $path));
        if ($S3_enable) {
            $sub_path = str_replace(array($this->imageFolder), S3_SITE_BUCKET, $path);
        } else {
            $sub_path = BASE_URL . substr($path, strlen($this->toReplaceWithURL));
        }

        return array('name' => $filename, 'path' => $sub_path);
    }

}
