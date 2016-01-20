<?php

namespace Alchemy\Phrasea\Databox\CandidateTerms;

use Alchemy\Phrasea\Databox\Util\XmlHelper;

class CandidateTerms
{
    public static function createFromDomDocument(\DOMDocument $candidateTermsDom)
    {
        $candidateTermsDom
            ->documentElement
            ->setAttribute("modification_date", $now = date("YmdHis"));

        return new self($candidateTermsDom->saveXML());
    }

    /**
     * @var XmlHelper
     */
    private $xmlHelper;

    /**
     * @param string $rawTerms
     */
    public function __construct($rawTerms = '')
    {
        $this->xmlHelper = new XmlHelper($rawTerms);
    }

    /**
     * @return string
     */
    public function getRawTerms()
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
        return $this->getDomXpath();
    }
}
