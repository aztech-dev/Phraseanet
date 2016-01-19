<?php

namespace Alchemy\Phrasea\Databox\Structure;

use Alchemy\Phrasea\Databox\Util\XmlHelper;

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
     * @var XmlHelper
     */
    private $xmlHelper;

    /**
     * @param string $rawStructure
     */
    public function __construct($rawStructure = '')
    {
        $this->xmlHelper = new XmlHelper($rawStructure);
    }

    /**
     * @return string
     */
    public function getRawStructure()
    {
        return $this->xmlHelper->getRawXml();
    }

    /**
     * @return false|\SimpleXMLElement
     */
    public function getSimpleXmlElement()
    {
        return $this->xmlHelper->getSimpleXmlElement();
    }

    /**
     * @return false|\DOMDocument
     */
    public function getDomDocument()
    {
        return $this->xmlHelper->getDomDocument();
    }

    /**
     * @return false|\DOMXPath
     */
    public function getDomXpath()
    {
        return $this->xmlHelper->getDomXpath();
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
