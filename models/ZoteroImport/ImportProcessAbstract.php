<?php
abstract class ZoteroImport_ImportProcessAbstract extends ProcessAbstract
{
    protected $_id;
    protected $_userId;
    protected $_username;
    protected $_password;
    
    abstract public function import();
    
    public function run($args)
    {
        $this->_id       = $args['id'];
        $this->_userId   = $args['user_id'];
        $this->_username = $args['username'];
        $this->_password = $args['password'];
        
        $this->import();
    }
    
    protected function _fieldMap($fieldName)
    {
        // Map to Dublin Core.
        switch ($fieldName) {
            
            // Source
            case 'reporterVolume':
            case 'firstPage': // ???
            case 'codeVolume':
            case 'codePages': // ???
            case 'pages': // ???
            case 'series':
            case 'seriesNumber': // concatenate with series?
            case 'volume':
            case 'numberOfVolumes': // concatenate with volume?
            case 'edition':
            case 'issue':
            case 'section':
            case 'dictionaryTitle':
            case 'encyclopediaTitle':
            case 'proceedingsTitle':
            case 'conferenceName':
            case 'meetingName':
            case 'seriesTitle':
            case 'bookTitle':
            case 'websiteTitle':
            case 'publicationTitle':
            case 'forumTitle':
            case 'blogTitle':
            case 'episodeNumber':
               $elementName = 'Source';
                break;
            
            // Identifier
            case 'url':
            case 'ISBN':
            case 'ISSN':
            case 'callNumber':
            case 'DOI':
            case 'reportNumber':
            case 'billNumber':
            case 'docketNumber':
            case 'documentNumber':
            case 'publicLawNumber':
            case 'codeNumber':
            case 'patentNumber':
            case 'applicationNumber':
            case 'priorityNumbers':
            case 'code':
                $elementName = 'Identifier';
                break;
            
            // Publisher
            case 'publisher':
            case 'place': // concatenate with publisher?
            case 'repository':
            case 'archiveLocation':
            case 'system':
            case 'distributor':
            case 'network':
            case 'university':
            case 'institution':
            case 'journalAbbreviation':
            case 'studio':
            case 'label':
                 $elementName = 'Publisher';
                break;
            
            // Type
            case 'thesisType':
            case 'letterType':
            case 'manuscriptType':
            case 'videoRecordingType':
            case 'websiteType':
            case 'reportType':
            case 'audioRecordingType':
            case 'presentationType':
            case 'postType':
            case 'mapType':
            case 'audioFileType':
                $elementName = 'Type';
                break;
            
            // Format
            case 'programmingLanguage':
            case 'interviewMedium':
            case 'numPages':
            case 'runningTime':
            case 'artworkMedium':
            case 'artworkSize':
            case 'scale':
                $elementName = 'Format';
                break;
            
            // Creator
            case 'company':
            case 'legislativeBody':
            case 'court':
            case 'committee':
            case 'session':
                $elementName = 'Creator';
                break;
            
            // Date
            case 'date':
            case 'accessDate':
            case 'dateDecided':
            case 'dateEnacted':
            case 'issueDate':
                $elementName = 'Date';
                break;
            
            // Description
            case 'extra':
            case 'abstractNote':
            case 'seriesText':
            case 'history':
            case 'legalStatus':
                $elementName = 'Description';
                break;
            
            // Title
            case 'title':
            case 'shortTitle':
            case 'subject': // email subject
            case 'caseName':
            case 'nameOfAct':
                $elementName = 'Title';
                break;
            
            // Contributor
            case 'reporter':
            case 'assignee':
                $elementName = 'Contributor';
                break;
            
            // Relation
            case 'version':
            case 'references':
                $elementName = 'Relation';
                break;
            
            // Rights
            case 'rights':
                $elementName = 'Rights';
                break;
            
            // Language
            case 'language':
                $elementName = 'Language';
                break;
            
            // [unknown]
            default:
                $elementName = false;
                break;
        }
        
        return $elementName;
    }
}