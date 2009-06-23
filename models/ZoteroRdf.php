<?php
class ZoteroRdf
{
    protected $_simpleXml;
    
    public $items = array();
    
    /**
     * Construct the object.
     * 
     * @var string $pathToExportDir The path to the export directory.
     * @return void
     */
    public function __construct($pathToExportDir)
    {
        
        // Set the export RDF SimpleXML object.
        $this->_simpleXml = new SimpleXMLElement($this->getPathToExportRdf($pathToExportDir), null, true);
        
        // Set the items. Only get the items that have the z:itemType attribute.
        $items = $this->_simpleXml->xpath('bib:*[@z:itemType]');
        
        // Iterate the items objects, building the items array.
        foreach ($items as $item) {
            $this->items[] = $this->getItemMetadata($item);
        }
    }
    
    public function getSimpleXml()
    {
        return $this->_simpleXml;
    }
    
    /**
     * Get all the item's metadata.
     * 
     * @var SimpleXMLElement $item The item object.
     * @return array
     */
    public function getItemMetadata(SimpleXMLElement $item)
    {
        $itemMetadata = array();
        
        // Set the bib:* type.
        $itemMetadata['bib'] = $item->getName();
        
        // Set the item's attributes
        $itemMetadata['@dc:title'] = $this->getAttribute($item, 'dc', 'title');
        $itemMetadata['@z:shortTitle'] = $this->getAttribute($item, 'z', 'shortTitle');
        $itemMetadata['@dc:date'] = $this->getAttribute($item, 'dc', 'date');
        $itemMetadata['@dc:itentifier'] = $this->getAttribute($item, 'dc', 'identifier');
        $itemMetadata['@z:itemType'] = $this->getAttribute($item, 'z', 'itemType');
        $itemMetadata['@RDF:about'] = $this->getAttribute($item, 'RDF', 'about');
        $itemMetadata['@RDF:ID'] = $this->getAttribute($item, 'RDF', 'ID');
        
        // Set the rest of the item's metadata.
        $itemMetadata['bib:authors'] = $this->getItemAuthors($item);
        $itemMetadata['bib:contributors'] = $this->getItemContributors($item);
        $itemMetadata['dcterms:isPartOf'] = $this->getItemIsPartOf($item);
        $itemMetadata['dc:publisher'] = $this->getItemPublisher($item);
        $itemMetadata['dc:identifier'] = $this->getItemIdentifier($item);
        $itemMetadata['dc:subject'] = $this->getItemSubject($item);
        
        // Get the item's attachments.
        $itemMetadata['link:link'] = $this->getItemLink($item);
        
        return $itemMetadata;
    }
    
    /**
     * Get the item's authors' surnames and given names.
     * 
     * @var SimpleXMLElement $item The item object.
     * @return array
     */
    public function getItemAuthors(SimpleXMLElement $item)
    {
        // Select the RDF:resource attribute of the first bib:authors child 
        // element of the item that has a RDF:resource attribute.
        // VERBOSE: child::bib:authors[attribute::RDF:resource][position()=1]/attribute::RDF:resource
        // ABBREVIATION: bib:authors[@RDF:resource][1]/@RDF:resource
        // This does not work, but it fucking should.
        // See: http://bugs.php.net/bug.php?id=45553
        //$resource = $item->xpath('bib:authors[@RDF:resource][1]/@RDF:resource');
        
        $authors = array();
        // bib:authors
        if ($bibAuthors = $item->xpath('bib:authors[@RDF:resource][1]')) {
            $bibAuthorsRdfResource = $this->getAttribute($bibAuthors[0], 'RDF', 'resource');
            // RDF:Seq
            $rdfLi = $this->_simpleXml->xpath("RDF:Seq[@RDF:about='$bibAuthorsRdfResource']/RDF:li");
            foreach ($rdfLi as $li) {
                $rdfLiRdfResource = $this->getAttribute($li[0], 'RDF', 'resource');
                // foaf:Person
                $foafPerson = $this->_simpleXml->xpath("foaf:Person[@RDF:about='$rdfLiRdfResource']");
                foreach ($foafPerson as $person) {
                    $surname = $this->getAttribute($person, 'foaf', 'surname');
                    $givenname = $this->getAttribute($person, 'foaf', 'givenname');
                    $authors[] = array('surname' => $surname, 'givenname' => $givenname);
                }
            }
        }
        return $authors;
    }
    
    public function getItemContributors(SimpleXMLElement $item)
    {
        $contributors = array();
        // bib:authors
        if ($bibContributors = $item->xpath('bib:contributors[@RDF:resource][1]')) {
            $bibContributorsRdfResource = $this->getAttribute($bibContributors[0], 'RDF', 'resource');
            // RDF:Seq
            $rdfLi = $this->_simpleXml->xpath("RDF:Seq[@RDF:about='$bibContributorsRdfResource']/RDF:li");
            foreach ($rdfLi as $li) {
                $rdfLiRdfResource = $this->getAttribute($li[0], 'RDF', 'resource');
                // foaf:Person
                $foafPerson = $this->_simpleXml->xpath("foaf:Person[@RDF:about='$rdfLiRdfResource']");
                foreach ($foafPerson as $person) {
                    $surname = $this->getAttribute($person, 'foaf', 'surname');
                    $givenname = $this->getAttribute($person, 'foaf', 'givenname');
                    $contributors[] = array('surname' => $surname, 'givenname' => $givenname);
                }
            }
        }
        return $contributors;
    }
    
    /**
     * Get the item of which the item is a part.
     * 
     * @var SimpleXMLElement $item The item object.
     * @return string|null
     */
    public function getItemIsPartOf(SimpleXMLElement $item)
    {
        // dcterms:isPartOf/@RDF:resource
        // bib:*/@RDF:about
        if ($dctermsIsPartOf = $item->xpath('dcterms:isPartOf')) {
            $dctermsIsPartOfRdfResource = $this->getAttribute($dctermsIsPartOf[0], 'RDF', 'resource');
            $bib = $this->_simpleXml->xpath("bib:*[@RDF:about='$dctermsIsPartOfRdfResource']");
            return $this->getItemMetadata($bib[0]);
        }
        return null;
        
    }
    
    /**
     * Get the name of the item's publisher.
     * 
     * @var SimpleXMLElement $item The item object.
     * $return string|null
     */
    public function getItemPublisher(SimpleXMLElement $item)
    {
        // dc:publisher/@RDF:resource
        // foaf:Organization/@RDF:about
        if ($dcPublisher = $item->xpath('dc:publisher')) {
            $dcPublisherRdfResource = $this->getAttribute($dcPublisher[0], 'RDF', 'resource');
            $foafOrganization = $this->_simpleXml->xpath("foaf:Organization[@RDF:about='$dcPublisherRdfResource']");
            return $this->getAttribute($foafOrganization[0], 'foaf', 'name');
        }
        return null;
    }
    
    /**
     * Get the item's URI.
     * 
     * @var SimpleXMLElement $item The item object.
     * $return string|null
     */
    public function getItemIdentifier(SimpleXMLElement $item)
    {
        // dc:identifier/@RDF:resource
        // dcterms:URI/@RDF:about
        if ($dcIdentifier = $item->xpath('dc:identifier')) {
            $dcIdentifierRdfResource = $this->getAttribute($dcIdentifier[0], 'RDF', 'resource');
            $dctermsUri = $this->_simpleXml->xpath("dcterms:URI[@RDF:about='$dcIdentifierRdfResource']");
            return $this->getAttribute($dctermsUri[0], 'RDF', 'value');
        }
        return null;
    }
    
    public function getItemSubject(SimpleXMLElement $item)
    {
        // dc:subject/@RDF:resource
        // z:AutomaticTag/@RDF:about
        $subjects = array();
        if ($dcSubject = $item->xpath('dc:subject')) {
            foreach ($dcSubject as $subject) {
                $subjectRdfResource = $this->getAttribute($subject[0], 'RDF', 'resource');
                $zAutomaticTag = $this->_simpleXml->xpath("z:AutomaticTag[@RDF:about='$subjectRdfResource']");
                $subjects[] = $this->getAttribute($zAutomaticTag[0], 'RDF', 'value');
            }
        }
        return $subjects;
    }
    
    /**
     * Get the item's attachments.
     * 
     * @var SimpleXMLElement $item The item object.
     * @return array
     */
    public function getItemLink(SimpleXMLElement $item)
    {
        // link:link/@RDF:resource (prepended with #)
        // z:Attachment/@RDF:ID (not prepended with #)
        $attachment = array();
        if ($linkLink = $item->xpath('link:link')) {
            foreach ($linkLink as $link) {
                $linkRdfResource = str_replace('#', '', $this->getAttribute($link, 'RDF', 'resource'));
                $zAttachment = $this->_simpleXml->xpath("z:Attachment[@RDF:ID='$linkRdfResource']");
                
                $attachment[] = array(
                    // Set the attachment's attributes.
                    '@RDF:ID' => $this->getAttribute($zAttachment[0], 'RDF', 'ID'), 
                    '@z:itemType' => $this->getAttribute($zAttachment[0], 'z', 'itemType'), 
                    '@dc:title' => $this->getAttribute($zAttachment[0], 'dc', 'title'), 
                    '@dcterms:dateSubmitted' => $this->getAttribute($zAttachment[0], 'dcterms', 'dateSubmitted'), 
                    '@link:type' => $this->getAttribute($zAttachment[0], 'link', 'type'), 
                    '@link:charset' => $this->getAttribute($zAttachment[0], 'link', 'charset'), 
                    
                    // Set the rest of the attachment's metadata.
                    'RDF:resource' => $this->getAttachmentResource($zAttachment[0]), 
                    'dc:identifier' => $this->getAttachmentIdentifier($zAttachment[0])
                );
            }
        }
        return $attachment;
    }
    
    /**
     * Get the path to attachment's file.
     * 
     * @var SimpleXMLElement $attachment The attachment object.
     * @return string|null
     */
    public function getAttachmentResource(SimpleXMLElement $attachment)
    {
        // RDF:resource/@RDF:resource
        if ($rdfResource = $attachment->xpath('RDF:resource')) {
            return $this->getAttribute($rdfResource[0], 'RDF', 'resource');
        }
        return null;
    }
    
    /**
     * Get the attachment's URI identifier.
     * 
     * @var SimpleXMLElement $attachment The attachment object.
     * @return string|null
     */
    public function getAttachmentIdentifier(SimpleXMLElement $attachment)
    {
        // dc:identifier/@RDF:resource
        // dcterms:URI/@RDF:about
        if ($dcIdentifier = $attachment->xpath('dc:identifier')) {
            $dcIdentifierRdfResource = $this->getAttribute($dcIdentifier[0], 'RDF', 'resource');
            $dctermsUri = $this->_simpleXml->xpath("dcterms:URI[@RDF:about='$dcIdentifierRdfResource']");
            return $this->getAttribute($dctermsUri[0], 'RDF', 'value');
        }
        return null;
    }
    
    /**
     * Get an attribute.
     * 
     * @var SimpleXMLElement $element The element from which to get an attribute.
     * @var string $prefix The attribute's namespace prefix.
     * @var string $name The attribute's name.
     * @return string|null
     */
    public function getAttribute(SimpleXMLElement $element, $prefix, $name)
    {
        foreach ($element->attributes($prefix, true) as $attributeName => $attributeValue) {
            if ($attributeName == $name) {
                return (string) $attributeValue;
            }
        }
        return null;
    }
    
    /**
     * Get the path to the export RDF file.
     * 
     * @var $pathToExportDir The path to the export directory.
     * @return string
     */
    private function getPathToExportRdf($pathToExportDir)
    {
        // Assumes that the only regular file in the directory is the export RDF.
        foreach (new DirectoryIterator($pathToExportDir) as $fileInfo) {
            if ($fileInfo->isFile()) {
                return $fileInfo->getPathname();
            }
        }
    }
}