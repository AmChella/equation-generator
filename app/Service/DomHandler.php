<?php
namespace SIGen\Service;

class DomHandler {
    private $htmlPath;
    private $outPath;
    private $dom;
    const MaxWidth = 550;
    const ImageExtension = ['svg', 'gif', 'png', 'jpeg', 'jpg'];

    public function __construct($htmlPath) {
        if (\file_exists($htmlPath) === false) {
            throw new Exception("Html file not exists", 1);

        }

        $this->htmlPath = $htmlPath;
        try {
            $domDocument = new \DOMDocument();
            @$domDocument->loadHTMLFile($this->htmlPath);

        } catch(\Exception $e) {
            throw new \Exception("HTML load problem", 1);
        }
        $this->dom = $domDocument;
    }

    private function getFileList($imageDirectory) {
        $imageNames = [];
        if (\is_dir($imageDirectory) === false) {
            return $imageNames;
        }

        $imageFiles = \scandir($imageDirectory);
        $imageFiles = array_diff($imageFiles,  ['..', '.']);
        foreach ($imageFiles as $key => $value) {
           $nameArray =  \explode('.', $value);
           if (\count($nameArray) === 2 &&
            \in_array($nameArray[1], self::ImageExtension) === true
           ) {
                $imageNames[$nameArray[0]] = $value;
           }
        }

        return $imageNames;
    }

    public function getPitValue($domContent, $xpath_query) {

        $domValue = $this->loadHtml($domContent);
        $xpath = new \DOMXpath($this->dom);
        $elements = $xpath->query($xpath_query);
        foreach ($elements as $element) {
            $nodeVal = $element->getAttribute('data-docsubtype');
        }

        return $nodeVal;
    }

    private function getValueByQuery($query) {

        $xpath = new \DomXpath($this->dom);
        $nodes = $xpath->query($query);
        foreach ($nodes as $node) {
            $nodeContent = $node->textContent;
        }

        return $nodeContent;
    }

    public function getXpathValues(
        $query, $imgAttr, $mathAttr, $innerXpath
    ) {
        $xpath = new \DomXpath($this->dom);
        $nodes = $xpath->query($query);
        $nodeContents = [];
        $maths = [];
        foreach ($nodes as $node) {
            $childElement = $node->childNodes;
            $math = [];
            foreach ($childElement as $child) {
                if ($child->nodeName === 'img') {
                    $math['img_path'] = $child->getAttribute($imgAttr);
                }

                if ($child->nodeName === 'span') {
                    $innerChild = $xpath->query($innerXpath, $child);
                    foreach ($innerChild as  $value) {
                        $math['math_name'] = $value->getAttribute($mathAttr);
                    }
                }
            }
            $maths[$math['math_name']] = $math['img_path'];
        }

        return $maths;
    }

    private function loadHtml($domContent)
    {
        if (empty($domContent) === true) {
            throw new Exception("dom.content.is.empty");
        }

        @$this->dom = new \DOMDocument('UTF-8');
        @$this->dom->loadHtml($domContent);

        return $this->dom;
    }


    public function saveAsHtml($outPath) {
        $this->dom->saveHTMLFile($outPath);
    }

    public function calculateDimensions($width, $height){
        $newHeight = $height;
        $newWidth = $width;
        if ($width > self::MaxWidth) {
            if($width != $height) {
                    $newWidth = self::MaxWidth;
                    $newHeight = floor(($newWidth * $height)/$width);
            } else {
                    $newWidth = $newHeight = self::MaxWidth;
            }
        }

        return [
                'height' => $newHeight,
                'width' => $newWidth
            ];
    }

    public function buildGlyphPath() {
        $xpath  = new \DOMXpath($this->dom);
        $nodes = $xpath->query('//*/div[@data-html-name="glyph"]');      
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                $glyphName = $node->getAttribute('data-name');
                $img = $this->dom->createDocumentFragment();
                $content = sprintf('<span data-html-name="glyph" data-name="%s"><img src="http://pgc-dev-test.s3.amazonaws.com/falcon/glyph/%s.png" data-html-name="glyph" /></span>', $glyphName, $glyphName);
                $img->appendXML($content);
                $parentNode = $node->parentNode;
                $parentNode->replaceChild($img, $node);
            }
        }     
        
    }

    public function buildImageSrc($serverPath = '', $imageDirectory = '') {
        $this->buildGlyphPath();
        if (empty($imageDirectory) === true){
            $imageDirectory = \dirname($this->htmlPath);
            $imageDirectory = \sprintf('%s/%s', $imageDirectory, 'images');
        }

        $images = $this->dom->getElementsByTagName('img');
        $imageNames = $this->getFileList($imageDirectory);
        foreach ($images as $key => $image) {
            $imageName = $image->getAttribute('src');
            if (array_key_exists($imageName, $imageNames) === false) {
                continue;
            }

            $imageFile = sprintf(
                '%s/%s', $imageDirectory, $imageNames[$imageName]
            );
            list($originalWidth, $originalHeight) = getimagesize($imageFile);
            $dimension = $this->calculateDimensions(
                $originalWidth, $originalHeight
            );
            $image->setAttribute('height', $dimension['height']);
            $image->setAttribute('width', $dimension['width']);
            $imagePath = sprintf(
                '%s/%s', $serverPath, $imageNames[$imageName]
            );
            $image->setAttribute('src', $imagePath);
        }
    }

    public function getStripinsList($dir) {
        $pattern = sprintf("%s/*.*", $dir);
        $svglist = glob($pattern);
        $list = [];
        foreach ($svglist as $value) {
            $list[] = basename($value);
        }

        return $list;
    }

    public function buildMathSrc(String $serverPath, $imageDirectory = '') {
        if (empty($imageDirectory) === true) {
            $imageDirectory = dirname($this->htmlPath);
            $imageDirectory = sprintf('%s/%s', $imageDirectory, 'images');
            $stripinImageDirectory = sprintf(
                '%s/%s', $imageDirectory, 'image_proof'
            );
        }

        $maths = $this->dom->getElementsByTagName('math');
	$imageNames = $this->getStripinsList($imageDirectory);
	foreach ($maths as $key => $math) {
            $imageName = $math->getAttribute('data-altimg');
            $imageBaseName = explode('.', $imageName)[0];
            $searchImg = sprintf("%s.svg", $imageBaseName);
            $searchHtml = sprintf("%s.html", $imageBaseName);
            if (in_array($searchImg, $imageNames) === true) {
                $imagePath = sprintf('%s/%s', $serverPath, $searchImg);
                $math->parentNode->setAttribute(
                    'data-stripin-url', $imagePath
                );
            }
            elseif(in_array($searchHtml, $imageNames) === true) {
                $span = $this->dom->createDocumentFragment();
                $reduceHtml = file_get_contents($imageDirectory . "/" . $searchHtml);
                $content = sprintf("<span class='reduce-mathml'>%s</span>", $reduceHtml);
                $span->appendXML($content);
                $math->parentNode->appendChild($span);
            }
            else {
                $imagePath = sprintf('%s/%s', $serverPath, $imageName);
                $math->parentNode->setAttribute(
                    'data-stripin-url', $imagePath
                );
	    }
        }
    }
}
