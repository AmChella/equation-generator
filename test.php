<?php
require_once __DIR__ . "/./vendor/autoload.php";

use SIGen\App;

Class SiTool {
    private $app;

    public function __construct($app) { 
        $this->app = $app;
    }

    public function createStripin($maths) {
        $callback = $this;
        $siService = $this->app->getApp('siGen')();
        foreach($maths as $item) {
            $siService->process($item, $callback);
        }
    }

    public function generate($htmlPath) {
        $mathService = $this->app->getApp('mathService')();

        foreach($htmlPath as $item) {
            $file = sprintf("%s/main.html", $item);
            $maths = $mathService->getMaths($file);
            $maths = $mathService->setMathData($maths, [
                'fontFamily' => 'Times New Roman', 'maxWidth' => '210pt', 'destination' => "$item", 'fontSize' => '10pt'
            ]);
            $this->createStripin($maths);
        }
    }

    public function onComplete($source, $target) {
        $data = file_get_contents($source);
        file_put_contents($target, $data);
    }

    public function updateMathSrc($paths) {
        $mathService = $this->app->getApp('mathService')();
        foreach($paths as $item) {
            $file = sprintf("%s/main.html", $item);
            $target = sprintf("%s/updated.html", $item);
            $basepath = pathinfo($item, PATHINFO_BASENAME);
            $httpUri = sprintf("http://pgc-dev-test.s3.amazonaws.com/falcon/qa/live/%s/stripins", $basepath);
            print $httpUri . "\n";
            $mathService->updateMathSrc($file, $httpUri, $item, $target);
        }
    }

    public function copySource($path, $file) {
        $basepath = pathinfo($path, PATHINFO_BASENAME);
        $s3Path = sprintf("s3://pgc-dev-test/falcon/qa/live/%s", $basepath);
        $siList = glob("$path/*.svg");
        foreach ($siList as $item) {
            print $item . "\n";
            $basename = pathinfo($item, PATHINFO_BASENAME);
            exec("aws s3 cp $path/$basename $s3Path/stripins/$basename --acl public-read");
        }

        exec("aws s3 cp $file $s3Path/main.html");
    }

    public function copyUpdatedSource($paths) {
        foreach($paths as $item) {
            $file = sprintf("%s/updated.html", $item);
            $this->copySource($item, $file);
        }
    }
}

$htmlPath = [
'/data/pc-issues'
];
try {
    $object = new SiTool(new App());
    $object->generate($htmlPath);
    // $object->updateMathSrc($htmlPath);
    // $object->copyUpdatedSource($htmlPath);
}
catch(Exception $e) {
    print $e->getMessage();
}