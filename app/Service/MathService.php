<?php
namespace SIGen\Service;

use \Exception;
use \DOMDocument;
use \DOMXpath;
use SIGen\Service\DomHandler;

Class MathService {
    public function __construct() {}

    public function getMaths($file) {
        $maths = [];
        $dom = new DOMDocument('utf-8');
        @$dom->loadHTMLFile($file);
        $xpath = new DOMXpath($dom);
        $list = $xpath->query('//*/math');
        foreach($list as $item) {
            $siName = $this->getStripinName($dom->saveHTML($item));
            $math['mml'] = $dom->saveHTML($item);
            $math['siName'] = $siName;
            $math['equationType'] = $this->getEquationType($item);
            $maths[] = $math;
        }

        return $maths;
    }

    public function getEquationType($node) {
        if ($node) {
            $parent = $node->parentNode;
            $type = $parent->getAttribute('data-html-name');
            if ($type === 'display-formula') {
                return 'display';
            }

            return 'inline';
        }

        return 'display';
    }

    public function setMathData(Array $math, Array $data) {
        $mathWithProperty = [];
        foreach ($math as $item) {
            $mathWithProperty[] = array_merge($item, $data);
        }

        return $mathWithProperty;
    }

    public function getStripinName($content) {
        $dom = new DOMDocument('utf-8');
        @$dom->loadHTML($content);
        $node = $dom->getElementsByTagName('math');
        $node = $node->item(0);
        if ($node->hasAttribute('altimg') === true) {
            return $node->getAttribute('altimg');
        }

        throw new Exception('altimg.not.found');
    }

    public function getMathName($siName) {
        return pathinfo($siName, PATHINFO_FILENAME);
    }

    public function generateStripin(Array $maths, $path) {
        foreach($maths as $item) {
            $mml = urlencode($item['mml']);
            $siName = $this->getMathName($item['siName']);
            $command = sprintf('curl -i -X POST -H "Content-Type:application/x-www-form-urlencoded" -d "mml=%s" -d "fontSize=10pt" -d "maxWidth=210pt" -d "fontFamily=Times New Roman" \'https://mathml2svg.proofcentral.com/mathml2svg\' > %s/%s.svg', $mml, $path, $siName);
            exec($command);
        }
    }

    public function updateMathSrc(
        String $html, String $baseUrl, String $imageDir, String $target
    ) {
            $mathSrcUpdator = new DomHandler($html);
            $mathSrcUpdator->buildMathSrc($baseUrl, $imageDir);
            $mathSrcUpdator->saveAsHtml($target);
    }
}
