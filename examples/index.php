<?php

require_once __DIR__ . "/image-generator/lib/ImageGenerator.php";
require_once __DIR__ . "/image-generator/lib/RackspaceIG.php";
require_once __DIR__ . "/image-generator/lib/S3IG.php";

$inputs = [
    [
        'name' => "mash",
        'media' => [
            [
                'type' => "image",
                'src' => __DIR__ . "/canvas.jpg",
                'width' => 800,
                'height' => 800,
                'coords' => [0, 0],
                'level' => 0
            ],
            [
                'type' => "draw",
                'font' => "./fonts/leaguegothic-regular-webfont.ttf",
                'size' => 36,
                'color' => '#000000',
                'text' => "Eric",
                'coords' => [475, 65, 0],
                'gravity' => '1',
                'level' => 1
            ],
            [
                'type' => "draw",
                'font' => "./fonts/leaguegothic-regular-webfont.ttf",
                'size' => 36,
                'color' => '#000000',
                'text' => "New York",
                'coords' => [340, 150, 0],
                'gravity' => '1',
                'level' => 1
            ],
            [
                'type' => "draw",
                'font' => "./fonts/leaguegothic-regular-webfont.ttf",
                'size' => 36,
                'color' => '#000000',
                'text' => "TV Presenter",
                'coords' => [350, 335, 0],
                'gravity' => '1',
                'level' => 1
            ],
        ],
    ],
];

$rackspace = new RackspaceIG();
$rackspace->generateImagesTo(__DIR__ . "/images", "/", "", FALSE);

//$rackspace->dbconnect(DB_SERVER, DB_USER, DB_PASS, DB_NAME, DB_TABLE);
//$rackspace->setDbConnection($this->mysqli);
//$rackspace->setDbTable(DB_TABLE);
//$rackspace->insertRow();
//$rackspace->dbclose();

//$s3 = new S3IG();
//$s3->generateImagesTo(__DIR__ . "/image-generator/images", "/");
//$s3->setDbConnection($this->mysqli);
//$s3->setDbTable(DB_TABLE);
//$s3->insertRow();
//$s3->dbclose();
