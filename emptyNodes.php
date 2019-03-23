<?php

$xml = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16pt" height="12pt" viewBox="0 0 16 12" version="1.1">
<defs>
<image id="image7" width="14" height="11" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAALCAAAAABq7uO+AAAAAmJLR0QA/4ePzL8AAAAfSURBVAiZY2T4z4AAjEwMKIASLgOyyf9J08uCopkBADy0BBP4Jj+jAAAAAElFTkSuQmCC"/>
<mask id="mask0">
<use xlink:href="#image7"/>
</mask>
<image id="image6" width="14" height="11" xlink:href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAALCAIAAADA5ys1AAAABmJLR0QA/wD/AP+gvaeTAAAAOElEQVQYlWP4//8/AxHg////TMSog4DBojQ+Pl5BQQEXCVHD+P//f0ZGRvzhAFEw4N5igbuGoFIAeQ8Wgb8dCSsAAAAASUVORK5CYII="/>
</defs>
<g id="surface1">
<use xlink:href="#image6" mask="url(#mask0)" transform="matrix(1,0,0,1,1,0.802)"/>
</g>
</svg>';
$xml_empty = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="131.507pt" height="792pt" viewBox="0 0 131.507 792" version="1.1">
<g id="surface1">
</g>
</svg>';

class Validator extends DOMDocument {
    protected $mml;

    public function validate($mml = null) {
        libxml_use_internal_errors(true);
        self::loadXML($mml);
        self::checkDomHasError();
    }

    private function checkDomHasError() {
        $errorMsg = [];
        $errors = libxml_get_errors();
        libxml_clear_errors();
        foreach ($errors as $error) {
            if (self::getErrorLevel($error->level) === 'FATAL')  {
                $msg = json_encode([
                    "message" => $error->message,
                    "code" => $error->code,
                    "line" => $error->line,
                    "column" => $error->column,
                    "file" => $error->file
                ], true);

                throw new Exception($msg);
            }
        }
    }

    private function getErrorLevel($error) {
        switch ($error) {
            case LIBXML_ERR_WARNING:
                return 'WARNING';
            case LIBXML_ERR_ERROR:
                return 'ERROR';
            case LIBXML_ERR_FATAL:
                return 'FATAL';
        }
    }

    private function getLength($len, $node) {
        if ($len === 1 && $node->hasChildNodes() === true) {
            foreach ($node->childNodes as $c) {
                if ($c->nodeType == XML_ELEMENT_NODE) {

                    return 2;
                }
            }
        }

        return 1;
    }

    public function getSvgGraphLength($svg, $xml = null) {
        if (empty($svg) === true) {
            throw new Exception('empty.svg.input');
        }

        libxml_use_internal_errors(true);
        $xmlNode = self::loadXML($svg);
        self::checkDomHasError();
        if (is_null($xml) === false) {
            $len = $xmlNode->getElementsByTagName('g')->length;
            $node = $xmlNode->getElementsByTagName('g')->item(0);

            return $this->getLength($len, $node);
        }

        $svgLength = self::getElementsByTagName('g')->length;
        $node = self::getElementsByTagName('g')->item(0);

        return $this->getLength($svgLength, $node);
    }
}


$object = new Validator();
print $object->getSvgGraphLength($xml);