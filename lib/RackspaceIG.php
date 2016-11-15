<?php

use OpenCloud\Rackspace;

require_once __DIR__ . '/ImageGenerator.php';

class RackspaceIG extends ImageGenerator\ImageGenerator {

    public function __construct($request = NULL) {
        parent::__construct($request);
    }

    /**
     * Instantiate the Rackspace with your Rackspace cdn credentials
     */
    public function initRackspaceClient() {
        $client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, [
            'username' => CDN_USERNAME,
            'apiKey' => CDN_APIKEY
        ]);

        $objectStoreService = $client->objectStoreService(NULL, 'ORD');
        $this->container = $objectStoreService->getContainer(CDN_CONTAINER);
    }

    /**
     * Push local image to Rackspace cloud.
     * @param string $file Path to local image file.
     * @return void
     */
    public function pushRackspace($file, $onTimestamp) {
        $localFileName = $file;
        $remoteFileName = $onTimestamp == TRUE ? strstr($file, 'usr') : substr(strstr($file, 'usr'), 4);
        $handle = fopen($localFileName, 'r');
        $object = $this->container->uploadObject($remoteFileName, $handle);

        if (is_resource($handle)) {
            fclose($handle);
        }

        return $object;
    }

    /**
     * Check if object exists on Rackspace cloud.
     * @param string $object Path of object on Rackspace cloud
     * @return boolean TRUE exists; FALSE not exists.
     */
    public function retrieveRackspace($object) {
        if ($this->container == NULL) {
            $this->initRackspaceClient();
        }

        return $this->container->objectExists($object);
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
        global $rackspace_enable;

        if ($rackspace_enable) {
            $this->initRackspaceClient();
        }

        $this->imageFolder = $saveTo;
        $this->toReplaceWithURL = $toReplaceWithURL;
        if ($onTimestamp) {
            if ($this->buildNamespaceOnTimestamp($saveTo, $structure) && $this->createShortUrl($encode)) {
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

        if ($rackspace_enable) {
            foreach ($this->localFiles as $file) {
                $this->pushRackspace($file, $onTimestamp);
            }
        }

        if ($clean) {
            $this->recursiveRemoveDirectory($this->directory);
        }

        return $this->responseJSON();
    }

    /**
     * Save image to local or Rackspace.
     * @param imagick $image
     * @param string $directory
     * @param string $filename
     * @return boolean
     */
    protected function saveURL($image, $directory, $filename) {
        global $rackspace_enable, $rackspace_https;
        $path = $directory . $filename . '.jpg';

        if (!$image->writeImage($path)) {
            $response = array('success' => FALSE, 'error' => 'Writing ' . $filename . '.jpg failed!!');
            header('Content-Type: application/json');
            echo json_encode($response);
            return FALSE;
        }

        array_push($this->localFiles, str_replace($this->imageFolder, realpath($this->imageFolder), $path));

        if ($rackspace_enable) {
            $cdnContainer = $this->container->getCdn();
            $cdnUri = $rackspace_https ? $cdnContainer->getCdnSslUri() : $cdnContainer->getCdnUri();
            $sub_path = str_replace(array($this->imageFolder), $cdnUri, $path);
        } else {
            $sub_path = BASE_URL . substr($path, strlen($this->toReplaceWithURL));
        }

        return array('name' => $filename, 'path' => $sub_path);
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
        $canvas->annotateImage($draw, $x, $y, $angle, $text);
        $draw->destroy();
    }

}
