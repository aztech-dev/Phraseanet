<?php

namespace Alchemy\Phrasea\Databox\Util;

class XmlHelper 
{

    private $rawXml = '';

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

    public function __construct($rawXml = '')
    {
        $this->rawXml = (string) $rawXml;

        $this->initializeObjects();
    }

    public function setRawXml($rawXml)
    {
        $this->rawXml = (string) $rawXml;

        $this->initializeObjects();
    }

    /**
     * @return string
     */
    public function getRawXml()
    {
        return $this->rawXml;
    }

    /**
     * @return false|\SimpleXMLElement
     */
    public function getSimpleXmlElement()
    {
        if ($this->simpleXmlElement === null) {
            $this->simpleXmlElement = simplexml_load_string($this->rawXml);
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

            if (@$dom->loadXML($this->rawXml) !== false) {
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

    private function initializeObjects()
    {
        $this->simpleXmlElement = null;
        $this->domDocument = null;
        $this->domXpath = null;

        if (trim($this->rawXml) == '') {
            $this->domDocument = false;
            $this->domXpath = false;
        }
    }
}
