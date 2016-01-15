<?php

namespace Alchemy\Phrasea\Databox\Structure;

class Structure 
{

    /**
     * @var string
     */
    private $rawStructure;

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
     * @param string $rawStructure
     */
    public function __construct($rawStructure)
    {
        $this->rawStructure = $rawStructure;

        if (trim($this->rawStructure) == '') {
            $this->simpleXmlElement = false;
            $this->domDocument = false;
            $this->domXpath = false;
        }
    }

    public function getRawStructure()
    {
        return $this->rawStructure;
    }

    /**
     * @return false|\SimpleXMLElement
     */
    public function getSimpleXmlElement()
    {
        if ($this->simpleXmlElement === null) {
            $this->simpleXmlElement = simplexml_load_string($this->rawStructure);
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

            if ($dom->loadXML($this->rawStructure) !== false) {
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
