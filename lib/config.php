<?php
    /*
    |--------------------------------------------------------------------------
    | Base URL and app folder
    |--------------------------------------------------------------------------
    |
    | Site URL for image urls.
    |
    */

    if (defined("BASE_URL")) {
        define("BASE_URL", "http://localhost:8888");
    }
    
    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple 500 page is shown.
    |
    */

    ini_set("display_errors", "1");

    /*
    |--------------------------------------------------------------------------
    | Database Mode
    |--------------------------------------------------------------------------
    |
    | When your application needs database, set $database_enable = 1 and add 
    | database settings on section after; otherwise leave it to 0, other info
    | will be ingored automatically.
    |
    */
    
    $database_enable = FALSE;

    /*
    |--------------------------------------------------------------------------
    | Database detailed setting infomation
    |--------------------------------------------------------------------------
    |
    | Add adapter details in $database using the format like below. The 'schema'
    | is necessary here, define it the same column name with your actual database
    | for all images to generate. This schema will be used in response JSON too.
    | Database schema will have columns: 
    |       'id', 'shortUrl', [IMAGE SCHEMA ADDED BELOW], 'entered'
    |
    */   

    $schema = [
        'sharable'
    ];

    /*
    |--------------------------------------------------------------------------
    | Generatinon assets including images and text
    |--------------------------------------------------------------------------
    |
    | Add common assets for every image generating below, this is different from
    | user direct input, all $inputs will be using on every operation. You will 
    | be asked to use THE SAME format for user JSON input too. Be carefull about 
    | 'level' in madia, define and put each to correct layout. First image will 
    | always be CANVAS, which is the ONLY level 0.
    |
    */
    
    /*
    Example:
 
        $inputs = [
            [
                'name' => "download",
                'media' => [
                    [
                        'type' => "blob",
                        'src' => $blob,
                        'width' => 736,
                        'height' => 378,
                        'coords' => [0, 0], // format: [x, y]
                        'level' => 0
                    ],
                    [
                        'type' => "image",
                        'src' => "./canvas.jpg",
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
                        'coords' => [0, 0, 0],
                        'gravity' => '1',
                        'level' => 1
                    ],
                ],
            ]
        ];
     */
    
    $inputs = [];
    
    /*
    |--------------------------------------------------------------------------
    | Rackspace Mode
    |--------------------------------------------------------------------------
    |
    | When your application needs Rackspace, set $rackspace_enable = 1 and add 
    | Rackspace settings on section after; otherwise leave it to 0, other info
    | will be ingored automatically.
    |
    */
    
    $rackspace_enable = FALSE;
            
    /*
    |--------------------------------------------------------------------------
    | Rackspace settings
    |--------------------------------------------------------------------------
    |
    | Add configure information for Rackspace cloud image.
    |
    */
            
    define("CDN_USERNAME", "");
    define("CDN_APIKEY", "");
    define("CDN_CONTAINER", "");

    /*
    |--------------------------------------------------------------------------
    | S3 Mode
    |--------------------------------------------------------------------------
    |
    |
    */
    
    $S3_enable = FALSE;
            
    /*
    |--------------------------------------------------------------------------
    | S3 settings
    |--------------------------------------------------------------------------
    |
    | Add configure information for S3 cloud image.
    |
    */
    
    define("S3_BUCKET", "");
    define("S3_ACCESS_KEY", "");
    define("S3_SECRET_KEY", "");
    define("S3_SITE_BUCKET", "");