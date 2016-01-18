<?php

namespace Alchemy\Phrasea\Databox\Process\SetStructure;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Databox\DataboxService;

class FeedMetaFieldsStep implements SetStructureStep
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @var DataboxService
     */
    private $databoxService;

    public function __construct(Application $application, DataboxService $databoxService)
    {
        $this->application = $application;
        $this->databoxService = $databoxService;
    }

    public function execute(\databox $databox, \SplFileInfo $dataTemplate, $documentPath)
    {
        $structure = $databox->getStructure();
        $sxe = $structure->getSimpleXmlElement();
        $dom_struct = $structure->getDomDocument();
        $xp_struct = $structure->getDomXpath();

        foreach ($sxe->description->children() as $fname => $field) {
            $fname = (string) $fname;
            $src = trim(isset($field['src']) ? str_replace('/rdf:RDF/rdf:Description/', '', $field['src']) : '');
            $meta_id = isset($field['meta_id']) ? $field['meta_id'] : null;

            if ( ! is_null($meta_id))
                continue;

            $nodes = $xp_struct->query('/record/description/' . $fname);

            if ($nodes->length > 0) {
                $nodes->item(0)->parentNode->removeChild($nodes->item(0));
            }

            $type = isset($field['type']) ? $field['type'] : 'string';
            $type = in_array($type, [
                \databox_field::TYPE_DATE,
                \databox_field::TYPE_NUMBER,
                \databox_field::TYPE_STRING,
                \databox_field::TYPE_TEXT
            ]) ? $type : \databox_field::TYPE_STRING;

            $multi = isset($field['multi']) ? (Boolean) (string) $field['multi'] : false;

            $meta_struct_field = \databox_field::create($this->application, $databox, $fname, $multi);
            $meta_struct_field
                ->set_readonly(isset($field['readonly']) ? (string) $field['readonly'] : 0)
                ->set_indexable(isset($field['index']) ? (string) $field['index'] : '1')
                ->set_separator(isset($field['separator']) ? (string) $field['separator'] : '')
                ->set_required((isset($field['required']) && (string) $field['required'] == 1))
                ->set_business((isset($field['business']) && (string) $field['business'] == 1))
                ->set_aggregable((isset($field['aggregable']) ? (string) $field['aggregable'] : 0))
                ->set_type($type)
                ->set_tbranch(isset($field['tbranch']) ? (string) $field['tbranch'] : '')
                ->set_thumbtitle(isset($field['thumbtitle']) ? (string) $field['thumbtitle'] : (isset($field['thumbTitle']) ? (string) $field['thumbTitle'] : '0'))
                ->set_report(isset($field['report']) ? (string) $field['report'] : '1')
                ->save();

            try {
                $meta_struct_field->set_tag(\databox_field::loadClassFromTagName($src))->save();
            } catch (\Exception $e) {

            }
        }

        $this->databoxService->replaceDataboxStructure($databox, $dom_struct);
    }
}
