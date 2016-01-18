<?php

namespace Alchemy\Phrasea\Databox\Structure;

class Structure 
{

    public static function createFromDomDocument(\DOMDocument $structureDocument)
    {
        $structureDocument
            ->documentElement
            ->setAttribute("modification_date", $now = date("YmdHis"));

        return new self($structureDocument->saveXML());
    }

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
        $this->rawStructure = (string) $rawStructure;

        $this->initializeObjects();
    }

    public function __sleep()
    {
        return [
            'rawStructure'
        ];
    }

    public function __wakeup()
    {
        $this->initializeObjects();
    }

    private function initializeObjects()
    {
        if (trim($this->rawStructure) == '') {
            $this->simpleXmlElement = false;
            $this->domDocument = false;
            $this->domXpath = false;
        }
    }

    /**
     * @return string
     */
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

    public function getModificationDate()
    {
        return $this->getDomDocument()
            ->documentElement
            ->getAttribute("modification_date");
    }

    public function isRegistrationEnabled()
    {
        if (! $this->getSimpleXmlElement()) {
            return false;
        }

        foreach ($this->getSimpleXmlElement()->xpath('/record/caninscript') as $canRegister) {
            if ((bool) ((string) $canRegister)) {
                return true;
            }
        }

        return false;
    }
}
