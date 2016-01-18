<?php

namespace Alchemy\Phrasea\Databox\CandidateTerms;

class CandidateTerms 
{
    /**
     * @var string
     */
    private $rawTerms;

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

    public function __construct($rawTerms = '')
    {
        $this->rawTerms = (string) $rawTerms;

        $this->initializeObjects();
    }

    private function initializeObjects()
    {
        if (trim($this->rawTerms) == '') {
            $this->simpleXmlElement = false;
            $this->domDocument = false;
            $this->domXpath = false;
        }
    }

    /**
     * @return string
     */
    public function getRawTerms()
    {
        return $this->rawTerms;
    }

    /**
     * @return false|\SimpleXMLElement
     */
    public function getSimpleXmlElement()
    {
        if ($this->simpleXmlElement === null) {
            $this->simpleXmlElement = simplexml_load_string($this->rawTerms);
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

            if ($dom->loadXML($this->rawTerms) !== false) {
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
