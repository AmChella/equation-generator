<?php
namespace SIGen;

use Tnq\Euclid\Util\CliContainer as Container;
use \Exception;
use SIGen\Service\MathService;

Class App {
    public $app;

    public function __construct() {
        $this->app = $this->setUp();
    }

    public function getApp(String $key): callable {
        if (isset($this->app[$key]) === false) {
            throw new Exception("$key.doesn't.not.exist");
        }

        return $this->app[$key];
    }

    public function setUp() {
        $app['siGen'] = function() {
            $eqn = Container::getAppContext(
                __DIR__ . '/../Config/commands.yaml'
            );

            return $eqn['StripinGenerator'];
        };

        $app['mathService'] = function() {
            return new MathService();
        };

        $app['domHandler'] = function() {
            return new DomHandler();
        };

        return $app;
    }
}