<?php


namespace ALCales\Docuware;


class DocuwareField
{
    public $FieldName;
    public $Item;
    public $ItemElementName;

    /**
     * Field constructor.
     * @param $FieldName
     * @param $Item
     * @param $ItemElementName
     */
    public function __construct($FieldName, $Item, $ItemElementName)
    {
        $this->FieldName = $FieldName;
        $this->Item = $Item;
        $this->ItemElementName = $ItemElementName;
    }


}
