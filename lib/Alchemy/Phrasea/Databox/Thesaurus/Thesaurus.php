<?php

namespace Alchemy\Phrasea\Databox\Thesaurus;

class Thesaurus 
{
    public static function createFromDomDocument(\DOMDocument $thesaurusDocument)
    {
        $thesaurusDocument
            ->documentElement
            ->setAttribute("modification_date", $now = date("YmdHis"));

        return new self($thesaurusDocument->saveXML());
    }

    /**
     * @var string
     */
    private $rawThesaurus;

    /**
     * @var \SimpleXMLElement|false|null
     */
    private $simpleXmlElement = null;

    /**
     * @var \DOMDocument|false|null
     */
    private $domDocument = null;

    /**
     * @var \DOMXpath|false|null
     */
    private $domXpath = null;

    /**
     * @param string $rawThesaurus
     */
    public function __construct($rawThesaurus = '')
    {
        $this->rawThesaurus = (string) $rawThesaurus;

        $this->initializeObjects();
    }

    private function initializeObjects()
    {
        $this->simpleXmlElement = null;
        $this->domDocument = null;
        $this->domXpath = null;

        if (trim($this->rawThesaurus) == '') {
            $this->simpleXmlElement = false;
            $this->domDocument = false;
            $this->domXpath = false;
        }
    }

    /**
     * @return string
     */
    public function getRawThesaurus()
    {
        return $this->rawThesaurus;
    }

    /**
     * @return false|\SimpleXMLElement
     */
    public function getSimpleXmlElement()
    {
        if ($this->simpleXmlElement === null) {
            $this->simpleXmlElement = simplexml_load_string($this->rawThesaurus);
        }

        return $this->simpleXmlElement;
    }

    /**
     * @return false|\DOMDocument
     */
    public function getDomDocument()
    {
        if ($this->domDocument === null) {
            $dom = new \DOMDocument();

            $dom->standalone = true;
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;

            $this->domDocument = false;

            if ($dom->loadXML($this->rawThesaurus) !== false) {
                $this->domDocument = $dom;
            }
        }

        return $this->domDocument;
    }

    /**
     * @return false|\DOMXPath
     */
    public function getDomXpath()
    {
        if ($this->domXpath === null) {
            $this->domXpath = false;

            if ($this->getDomDocument() !== false) {
                $this->domXpath = new \DOMXPath($this->getDomDocument());
            }
        }

        return $this->domXpath;
    }
}
